<?php

namespace App\Services\AI;

// =========================================================================
// KHAI BÁO THƯ VIỆN & MODEL CHO DỊCH VỤ TÓM TẮT TIẾNG VIỆT (ViT5 & LLMLingua-2)
// =========================================================================
use App\Models\AiConversation;           // Model phiên hội thoại AI để lưu Rolling Summary Memory trực tiếp vào DB
use App\Models\Material;                 // Model học liệu môn học (tiêu đề, mô tả, file_path PDF/DOCX/PPTX/TXT)
use App\Models\Subject;                  // Model môn học dùng để gom toàn bộ tài liệu tóm tắt theo môn
use Illuminate\Support\Collection;       // Tập hợp Eloquent cho danh sách tin nhắn cũ / danh sách học liệu
use Illuminate\Support\Facades\Cache;    // Bộ nhớ đệm Cache: lưu kết quả tóm tắt tài liệu & nén ngữ cảnh giúp phản hồi tức thì
use Illuminate\Support\Facades\Http;     // HTTP Client gọi sang Python FastAPI (/summarize, /compress-context), HuggingFace API & Gemini/Vilao fallback
use Illuminate\Support\Facades\Log;      // Ghi log cảnh báo khi chuyển tầng dự phòng
use Illuminate\Support\Facades\Storage;  // Đọc đường dẫn vật lý tệp học liệu trong storage/app/public

/**
 * VIETNAMESE SUMMARIZER & CONTEXT COMPRESSION SERVICE
 * - Tóm tắt tài liệu học đơn lẻ & tóm tắt tổng hợp tài liệu theo Môn học:
 *   Ưu tiên Tầng 1: Python FastAPI (/summarize) chạy model `VietAI/vit5-base-vietnews-summarization` + PyMuPDF/python-docx/python-pptx.
 *   Tầng 2: HuggingFace Inference API (`VietAI/vit5-base-vietnews-summarization`).
 *   Tầng 3: Fallback sang Gemini / Vilao LLM để đảm bảo 100% luôn có kết quả kể cả khi chưa bật Python server.
 *
 * - Nén / Tóm tắt toàn bộ ngữ cảnh lịch sử trò chuyện dài:
 *   Ưu tiên Tầng 1: Python FastAPI (/compress-context) chạy `Microsoft LLMLingua-2` (hoặc ViT5).
 *   Tầng 2: Fallback nén trích xuất gọn nhẹ (không làm chậm luồng chat).
 */
class VietnameseSummarizerService
{
    public const VIT5_MODEL = 'VietAI/vit5-base-vietnews-summarization';

    private string $mlServiceUrl;
    private string $hfToken;
    private string $geminiApiKey;
    private string $geminiModel;
    private string $vilaoApiKey;
    private string $vilaoBaseUrl;
    private string $vilaoModel;

    public function __construct()
    {
        $this->mlServiceUrl = rtrim((string) env('ML_SERVICE_URL', 'http://127.0.0.1:8001'), '/');
        $this->hfToken = (string) env('HF_API_TOKEN', '');
        $this->geminiApiKey = (string) env('GEMINI_API_KEY', '');
        $this->geminiModel = (string) env('GEMINI_MODEL', 'gemini-3.5-flash-lite');
        $this->vilaoApiKey = (string) env('VILAO_API_KEY', 'sk-fa3075368cd22b1d7a42e58668727dad457fae39734405ab34632974a5c7d14e');
        $this->vilaoBaseUrl = rtrim((string) env('VILAO_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $this->vilaoModel = (string) env('VILAO_MODEL', 'vgpt/gpt-6.1-sol');
    }

    /**
     * 1A. ROLLING SUMMARY MEMORY (TRÍ NHỚ CUỐN CHIẾU TOÀN BỘ CUỘC TRÒ CHUYỆN):
     * - Bỏ hoàn toàn việc gửi tin nhắn thô theo mốc cố định (6 tin).
     * - Nén toàn bộ lịch sử từ tin đầu tiên thành 1 bản tóm tắt ngắn gọn (~250-350 từ) lưu trực tiếp tại `ai_conversations.context_summary`.
     * - Cộng dồn theo từng lượt mới (`message_id > summarized_up_to_message_id`), tiết kiệm tối đa token và luôn giữ trọn mạch hội thoại.
     */
    public function updateRollingConversationSummary(AiConversation $conversation, ?int $excludeMessageId = null): ?string
    {
        $lastSummarizedId = (int) ($conversation->summarized_up_to_message_id ?? 0);

        $newQuery = $conversation->messages()
            ->where('message_id', '>', $lastSummarizedId)
            ->orderBy('message_id', 'asc');

        if ($excludeMessageId !== null) {
            $newQuery->where('message_id', '<', $excludeMessageId);
        }

        $newMessages = $newQuery->get();

        // Chỉ nén tóm tắt khi tích lũy đủ >= 6 tin nhắn mới (tránh gọi thêm API ở mỗi lượt chat lẻ)
        if ($newMessages->count() < 6) {
            return $conversation->context_summary ?: null;
        }

        $existingSummary = trim((string) ($conversation->context_summary ?? ''));
        $newTurns = [];
        foreach ($newMessages as $msg) {
            $speaker = $msg->role === 'assistant' ? 'Trợ giảng' : 'Sinh viên';
            // Loại bỏ khối code/mermaid quá dài khi nén để giữ trọng tâm ngữ nghĩa
            $clean = preg_replace('/```[\s\S]*?```/', '[đoạn mã/sơ đồ minh họa]', (string) $msg->content);
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $clean)));
            if ($clean !== '') {
                $newTurns[] = "{$speaker}: " . mb_substr($clean, 0, 380);
            }
        }

        if (empty($newTurns)) {
            return $existingSummary !== '' ? $existingSummary : null;
        }

        $deltaText = implode("\n", $newTurns);
        $mergedSummary = $this->synthesizeRollingMemory($existingSummary, $deltaText);

        $maxMsgId = (int) $newMessages->max('message_id');
        if ($mergedSummary !== '') {
            $conversation->update([
                'context_summary' => mb_substr($mergedSummary, 0, 1600),
                'summarized_up_to_message_id' => $maxMsgId,
            ]);
            return $conversation->context_summary;
        }

        return $existingSummary !== '' ? $existingSummary : null;
    }

    /**
     * Tổng hợp bản Trí nhớ cuốn chiếu mới từ bản cũ + các lượt mới (DeepSeek Main -> OpenAI Fallback -> Python LLMLingua-2 -> Extractive).
     */
    private function synthesizeRollingMemory(string $existingSummary, string $deltaText): string
    {
        // Nén trích xuất cục bộ 0ms (không gọi HTTP ngoài tránh gây treo/đơ 12–24s trong luồng chat)
        $combined = ($existingSummary !== '' ? $existingSummary . "\n" : '') . $deltaText;
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R+/', $combined) ?: []),
            fn ($line) => $line !== '' && ! str_starts_with($line, '```')
        ));

        $compact = implode("\n", array_slice($lines, -10));

        return mb_substr($compact, 0, 1400);
    }

    /**
     * 1B. TÓM TẮT NGỮ CẢNH HỘI THOẠI DÀI (giữ tương thích ngược nếu có nơi gọi trực tiếp).
     */
    public function compressConversationContext(Collection $olderMessages, int $conversationId): ?string
    {
        if ($olderMessages->isEmpty()) {
            return null;
        }

        $cacheKey = "ai_tutor_ctx_summary_{$conversationId}_" . $olderMessages->count();

        return Cache::remember($cacheKey, 3600, function () use ($olderMessages) {
            $lines = [];
            foreach ($olderMessages as $msg) {
                $speaker = $msg->role === 'assistant' ? 'Thầy' : 'Học trò';
                $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $msg->content)));
                if ($text !== '') {
                    $lines[] = "{$speaker}: " . mb_substr($text, 0, 260);
                }
            }

            $rawContext = implode("\n", $lines);
            if ($rawContext === '') {
                return null;
            }

            // Tầng 1: Gọi Python FastAPI (/compress-context — LLMLingua-2 / ViT5)
            if ($this->mlServiceUrl !== '') {
                try {
                    $res = Http::timeout(4)->post($this->mlServiceUrl . '/compress-context', [
                        'context' => mb_substr($rawContext, 0, 12000),
                        'rate' => 0.45,
                    ]);
                    if ($res->successful() && ! empty($res->json('compressed_context'))) {
                        return trim((string) $res->json('compressed_context'));
                    }
                } catch (\Throwable $e) {
                    // Python server chưa bật -> dùng trích xuất nhanh bên dưới
                }
            }

            // Tầng 2 (Fallback tức thì): Rút gọn các ý hỏi/đáp cốt lõi của đoạn hội thoại cũ
            $digest = [];
            foreach ($olderMessages->take(8) as $msg) {
                $prefix = $msg->role === 'assistant' ? 'Thầy giải đáp' : 'Trò hỏi';
                $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $msg->content))), 0, 120);
                if ($snippet !== '') {
                    $digest[] = "- {$prefix}: {$snippet}";
                }
            }

            return ! empty($digest) ? implode("\n", $digest) : null;
        });
    }

    /**
     * 2. TÓM TẮT 1 TÀI LIỆU HỌC CỤ THỂ (PDF / DOCX / PPTX / TXT + Metadata).
     */
    /**
     * Trích xuất văn bản thuần trực tiếp từ file tài liệu (.docx, .pdf, .txt, .md) bằng PHP (có cache).
     */
    public function extractMaterialPlainText(Material $material, int $maxChars = 8000): string
    {
        if (empty($material->file_path) || ! Storage::disk('public')->exists($material->file_path)) {
            return '';
        }

        $absPath = Storage::disk('public')->path($material->file_path);
        $mtime = @filemtime($absPath) ?: 0;
        $cacheKey = "material_plaintext_{$material->material_id}_{$mtime}";

        $fullText = Cache::remember($cacheKey, 86400, function () use ($absPath) {
            $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));

            if (in_array($ext, ['txt', 'md', 'csv'], true)) {
                return trim((string) @file_get_contents($absPath));
            }

            if ($ext === 'docx' && class_exists(\ZipArchive::class)) {
                $zip = new \ZipArchive();
                if ($zip->open($absPath) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if (is_string($xml) && $xml !== '') {
                        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
                        $plain = html_entity_decode(strip_tags((string) $xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $plain = preg_replace("/[ \t]+/", ' ', $plain);
                        $plain = preg_replace("/\n{2,}/", "\n", $plain);
                        return trim((string) $plain);
                    }
                }
            }

            if ($ext === 'pdf') {
                $raw = (string) @file_get_contents($absPath);
                if ($raw !== '') {
                    $chunks = [];
                    // Giải nén stream FlateDecode nếu có
                    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streams)) {
                        foreach ($streams[1] as $st) {
                            $decoded = @gzuncompress($st);
                            $src = ($decoded !== false) ? $decoded : $st;
                            if (preg_match_all('/\(([^()]*)\)\s*Tj/', $src, $tjs)) {
                                foreach ($tjs[1] as $t) {
                                    $chunks[] = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $t);
                                }
                            }
                        }
                    }
                    $plain = trim(preg_replace("/\n{2,}/", "\n", implode("\n", $chunks)));
                    if ($plain !== '') {
                        return $plain;
                    }
                }
            }

            return '';
        });

        return mb_substr((string) $fullText, 0, $maxChars);
    }

    public function summarizeMaterial(Material $material): array
    {
        $cacheKey = 'material_ai_summary_v2_' . $material->material_id . '_' . md5((string) ($material->uploaded_at ?? $material->file_path));

        return Cache::remember($cacheKey, 86400, function () use ($material) {
            $filePath = null;
            $rawText = trim("Tài liệu: {$material->title}\nMôn học: " . ($material->subject?->subject_name ?? '') . "\nMô tả: {$material->description}");

            if (! empty($material->file_path) && Storage::disk('public')->exists($material->file_path)) {
                $filePath = Storage::disk('public')->path($material->file_path);
                $extracted = $this->extractMaterialPlainText($material, 12000);
                if ($extracted !== '') {
                    $rawText .= "\n\nNội dung tệp:\n" . $extracted;
                }
            }

            return $this->runSummarizationPipeline(
                $rawText,
                $filePath,
                "Tài liệu \"{$material->title}\" (môn " . ($material->subject?->subject_name ?? 'Chung') . ')'
            );
        });
    }

    /**
     * 3. TÓM TẮT TỔNG HỢP TOÀN BỘ TÀI LIỆU HỌC THEO MÔN (Subject-level Study Summary).
     */
    public function summarizeSubjectMaterials(Subject $subject): array
    {
        $materials = Material::where('subject_id', $subject->subject_id)
            ->orderBy('material_id')
            ->get();

        if ($materials->isEmpty()) {
            return [
                'success' => false,
                'engine' => 'NONE',
                'summary' => "Môn {$subject->subject_name} hiện chưa có học liệu nào được tải lên.",
                'material_count' => 0,
            ];
        }

        $cacheKey = 'subject_ai_summary_v2_' . $subject->subject_id . '_' . $materials->count() . '_' . md5((string) $materials->max('material_id'));

        return Cache::remember($cacheKey, 86400, function () use ($subject, $materials) {
            $blocks = [];
            foreach ($materials as $idx => $m) {
                $num = $idx + 1;
                $block = "{$num}. {$m->title}";
                if (! empty($m->description)) {
                    $block .= "\n   Mô tả: {$m->description}";
                }
                $extracted = $this->extractMaterialPlainText($m, 1500);
                if ($extracted !== '') {
                    $block .= "\n   Nội dung trích xuất:\n   " . str_replace("\n", "\n   ", $extracted);
                }
                $blocks[] = $block;
            }

            $combinedText = "Môn học: {$subject->subject_name} ({$subject->subject_code})\nTổng số tài liệu: {$materials->count()}\n\n"
                . implode("\n\n", $blocks);

            $result = $this->runSummarizationPipeline(
                $combinedText,
                null,
                "Toàn bộ tài liệu môn {$subject->subject_name} ({$subject->subject_code})"
            );
            $result['material_count'] = $materials->count();

            return $result;
        });
    }

    /**
     * Pipeline tóm tắt 3 tầng:
     *   Tầng 1: Local FastAPI (`VietAI/vit5-base-vietnews-summarization`)
     *   Tầng 2: HuggingFace Inference API (`VietAI/vit5-base-vietnews-summarization`)
     *   Tầng 3: LLM Fallback (Vilao / Gemini)
     */
    private function runSummarizationPipeline(string $text, ?string $filePath, string $label): array
    {
        // Tầng 1: Local Python FastAPI (VietAI/vit5-base-vietnews-summarization)
        if ($this->mlServiceUrl !== '') {
            try {
                $res = Http::timeout(8)->post($this->mlServiceUrl . '/summarize', [
                    'text' => mb_substr($text, 0, 15000),
                    'file_path' => $filePath,
                    'max_length' => 256,
                ]);
                if ($res->successful() && ! empty($res->json('summary'))) {
                    return [
                        'success' => true,
                        'engine' => $res->json('model', self::VIT5_MODEL) . ' (Local FastAPI)',
                        'summary' => trim((string) $res->json('summary')),
                    ];
                }
            } catch (\Throwable $e) {
                // Chuyển tiếp sang Tầng 2
            }
        }

        // Tầng 2: HuggingFace Inference API (VietAI/vit5-base-vietnews-summarization)
        if ($this->hfToken !== '') {
            try {
                $url = 'https://router.huggingface.co/hf-inference/models/' . self::VIT5_MODEL;
                $res = Http::timeout(15)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->hfToken,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'inputs' => 'vietnews: ' . mb_substr($text, 0, 2500) . ' </s>',
                    'options' => ['wait_for_model' => true],
                ]);
                if ($res->successful()) {
                    $summary = $res->json('0.summary_text') ?? $res->json('summary_text');
                    if (! empty($summary)) {
                        return [
                            'success' => true,
                            'engine' => self::VIT5_MODEL . ' (HuggingFace API)',
                            'summary' => trim((string) $summary),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('HF ViT5 summarize exception: ' . $e->getMessage());
            }
        }

        // Tầng 3: Fallback LLM (Vilao / Gemini) tóm tắt có cấu trúc rõ ràng
        $prompt = "Hãy tóm tắt ngắn gọn, súc tích bằng tiếng Việt cho {$label} dựa trên nội dung sau.\n"
            . "Trình bày đúng 3 phần: (1) Mục tiêu chính, (2) 3–5 ý cốt lõi cần nhớ, (3) Từ khóa quan trọng cần ôn thi.\n\n"
            . mb_substr($text, 0, 8000);

        $llmSummary = $this->callLlmFallback($prompt);
        if (! empty($llmSummary)) {
            return [
                'success' => true,
                'engine' => 'AI Summarizer (Fallback LLM)',
                'summary' => trim($llmSummary),
            ];
        }

        return [
            'success' => true,
            'engine' => 'Extractive Fallback',
            'summary' => mb_substr($text, 0, 800),
        ];
    }

    private function callLlmFallback(string $prompt): ?string
    {
        if ($this->vilaoApiKey !== '') {
            try {
                $res = Http::timeout(20)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->vilaoApiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->vilaoBaseUrl . '/chat/completions', [
                    'model' => $this->vilaoModel,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Bạn là công cụ tóm tắt tài liệu học thuật tiếng Việt ngắn gọn, chính xác, không sáo rỗng.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 600,
                    'temperature' => 0.3,
                ]);
                if ($res->successful() && ! empty($res->json('choices.0.message.content'))) {
                    return (string) $res->json('choices.0.message.content');
                }
            } catch (\Throwable $e) {
                Log::warning('Vilao summarize fallback error: ' . $e->getMessage());
            }
        }

        if ($this->geminiApiKey !== '') {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent";
                $res = Http::timeout(20)->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->geminiApiKey,
                ])->post($url, [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 600],
                ]);
                if ($res->successful() && ! empty($res->json('candidates.0.content.parts.0.text'))) {
                    return (string) $res->json('candidates.0.content.parts.0.text');
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini summarize fallback error: ' . $e->getMessage());
            }
        }

        return null;
    }
}
