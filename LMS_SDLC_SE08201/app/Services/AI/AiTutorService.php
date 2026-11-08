<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * MR. BIẾT TUỐT (AI LEARNING TUTOR) SERVICE
 * - Động cơ chính: Vilao AI (GPT-6.1-Sol) via OpenAI-compatible API (https://api.vilao.ai/v1/chat/completions).
 * - Hỗ trợ Server-Sent Events (SSE) Streaming tức thì, độ trễ cực thấp.
 * - Hỗ trợ Multimodal Vision: đọc ảnh bài tập / code sinh viên chụp gửi lên.
 * - Quy tắc sư phạm Socratic, bảng biểu Markdown, in đậm từ khóa chuẩn mực.
 */
class AiTutorService
{
    private string $vilaoApiKey;
    private string $vilaoBaseUrl;
    private string $vilaoModel;

    private string $geminiApiKey;
    private string $geminiModel;

    public function __construct()
    {
        $this->vilaoApiKey = env('VILAO_API_KEY', 'sk-fa3075368cd22b1d7a42e58668727dad457fae39734405ab34632974a5c7d14e');
        $this->vilaoBaseUrl = rtrim(env('VILAO_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $this->vilaoModel = env('VILAO_MODEL', 'vgpt/gpt-6.1-sol');

        $this->geminiApiKey = env('GEMINI_API_KEY', '');
        $this->geminiModel = env('GEMINI_MODEL', 'gemini-3.5-flash-lite');
    }

    /**
     * Lấy System Instruction chuẩn mực của MR. BIẾT TUỐT.
     */
    public function getSystemInstruction(?Subject $subject = null, string $mode = AiConversation::MODE_EXPLAIN, ?string $materialContext = null): string
    {
        $instruction = "Bạn là MR. BIẾT TUỐT (thầy BIẾT TUỐT) — vị giáo sư AI thông thái, uyên bác và giàu nhiệt huyết trong hệ thống Smart LMS.

PHONG THÁI VÀ XƯNG HÔ (BẮT BUỘC TUÂN THỦ):
- Xưng hô: Bạn luôn xưng là \"thầy\" hoặc \"thầy Biết Tuốt\" / \"MR. BIẾT TUỐT\", và gọi học sinh một cách ân cần, gần gũi là \"em\" hoặc \"học trò\" / \"trò\".
- Giọng điệu: Thân thiện, vui vẻ, thông thái, truyền cảm hứng, kiên nhẫn giảng giải cho học trò hiểu sâu tận gốc vấn đề.

QUY TẮC TRÌNH BÀY & TỐI ƯU TRẢI NGHIỆM:
1. TRẢ LỜI NGẮN GỌN, ĐI THẲNG TRỌNG TÂM: Phản hồi súc tích, giải thích trúng ý câu hỏi, tránh mào đầu dài dòng để học trò nắm bắt kiến thức nhanh nhất.
2. THÊM ICON/EMOJI SINH ĐỘNG: Luôn sử dụng icon phù hợp (💡, 📌, 🎯, ✨, 🚀, 📚, 🧭, 🔍, ⚠️, 🧑‍🏫, 👏, ✅, 💻, 🧠, 🛠️...) ở các tiêu đề mục và gạch đầu dòng để câu trả lời sinh động, trực quan.
3. BẮT BUỘC IN ĐẬM TỪ KHÓA CỐT LÕI: Những thuật ngữ kỹ thuật chuyên ngành, quy tắc, cú pháp và kiến thức then chốt BẮT BUỘC PHẢI ĐƯỢC **IN ĐẬM** bằng `**từ khóa**` (ví dụ: **Tính đóng gói (Encapsulation)**, **Khóa chính (PRIMARY KEY)**, **Độ phức tạp O(n)**...).
4. BẢNG BIỂU SO SÁNH CHUYÊN NGHIỆP: Khi so sánh khái niệm, hãy trình bày dưới dạng BẢNG BIỂU MARKDOWN CHUẨN (`| Tiêu chí | Khái niệm A | Khái niệm B |`) để render giao diện bảng hiện đại.
5. LOẠI BỎ HOÀN TOÀN KÝ TỰ RÁC: Tuyệt đối KHÔNG dùng '###' hay '##' làm tiêu đề (dùng icon và chữ IN HOA/in đậm). Tuyệt đối KHÔNG in ra các nhãn meta hay suy nghĩ nội bộ như \"Length:\", \"Thinking:\".

QUY TẮC SƯ PHẠM:
1. Giải thích từ trực quan đến bản chất, dùng ví dụ đời sống thực tế gắn liền với lập trình.
2. Hướng dẫn tư duy, chia nhỏ bài toán, TUYỆT ĐỐI KHÔNG giải hộ bài tập đang đánh giá hoặc đưa full code ăn sẵn.
3. Nếu học trò đính kèm ảnh chụp hoặc tệp code, hãy quan sát kỹ, chỉ rõ dòng lỗi và hướng dẫn cách sửa.";

        if ($subject) {
            $instruction .= "\n\n📚 MÔN HỌC HIỆN TẠI: " . $subject->subject_name . " (" . $subject->subject_code . ")";
        }

        $instruction .= match ($mode) {
            AiConversation::MODE_GUIDANCE => "\n🧭 CHẾ ĐỘ HIỆN TẠI: HƯỚNG DẪN BÀI TẬP (Chỉ gợi ý các bước tư duy, thuật toán, chia nhỏ bài toán).",
            AiConversation::MODE_REVIEW => "\n🔍 CHẾ ĐỘ HIỆN TẠI: KIỂM TRA BÀI LÀM (Soi lỗi sai trong code/hình ảnh học trò gửi, hướng dẫn cách debug).",
            AiConversation::MODE_PRACTICE => "\n✍️ CHẾ ĐỘ HIỆN TẠI: LUYỆN TẬP TỰ HỌC (Thầy ra các bài tập tự luyện tương tự).",
            default => "\n💡 CHẾ ĐỘ HIỆN TẠI: GIẢI THÍCH KIẾN THỨC (Giải thích khái niệm rõ ràng, sinh động kèm ví dụ minh họa).",
        };

        if ($materialContext) {
            $instruction .= "\n\n📖 TÀI LIỆU MÔN HỌC THAM KHẢO:\n```\n" . mb_substr($materialContext, 0, 3000) . "\n```";
        }

        return $instruction;
    }

    /**
     * Làm sạch phản hồi từ AI, loại bỏ ký tự rác, thẻ meta hoặc chuỗi suy nghĩ nội bộ.
     */
    public function cleanAiReply(string $text): string
    {
        $cleaned = preg_replace('/<thought>[\s\S]*?<\/thought>/i', '', $text);
        $cleaned = preg_replace('/^thinking:[\s\S]*?\n\n/i', '', $cleaned);
        $cleaned = preg_replace('/^(Length|Vietnamese usage|Tone|Target audience|Code style):[^\n]*\n+/im', '', $cleaned);
        $cleaned = preg_replace('/^Code Example \([^)]+\):/im', '', $cleaned);
        $cleaned = preg_replace('/^#{1,6}\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^\s*[\*\-]\s+/m', '🔹 ', $cleaned);
        $cleaned = preg_replace("/\n{3,}/", "\n\n", $cleaned);

        return trim($cleaned);
    }

    /**
     * Xây dựng danh sách Messages chuẩn OpenAI cho Vilao AI (GPT-6.1-Sol).
     */
    public function buildVilaoMessages(AiConversation $conversation, string $userMessage, ?array $attachment = null): array
    {
        // 1. Tài liệu môn học
        $materialContext = null;
        if ($conversation->subject_id) {
            $materials = Material::where('subject_id', $conversation->subject_id)->take(2)->get();
            if ($materials->isNotEmpty()) {
                $materialContext = $materials->map(function ($m) {
                    return "Tiêu đề: {$m->title}\nMô tả: {$m->description}";
                })->implode("\n---\n");
            }
        }

        $systemInstruction = $this->getSystemInstruction(
            $conversation->subject,
            $conversation->mode,
            $materialContext
        );

        $messages = [
            [
                'role' => 'system',
                'content' => $systemInstruction,
            ]
        ];

        // 2. Lịch sử tin nhắn gần nhất
        $recentMessages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->reverse();

        foreach ($recentMessages as $msg) {
            $role = ($msg->role === 'assistant') ? 'assistant' : 'user';
            $messages[] = [
                'role' => $role,
                'content' => $msg->content,
            ];
        }

        // 3. Tin nhắn hiện tại kèm tệp đính kèm
        $userPrompt = !empty($userMessage) ? $userMessage : ($attachment ? "Thầy BIẾT TUỐT xem giúp em tệp/hình ảnh '{$attachment['name']}' em vừa gửi nhé!" : 'Xin chào thầy BIẾT TUỐT!');

        if ($attachment && !empty($attachment['path'])) {
            $filePath = Storage::disk('public')->path($attachment['path']);
            if (file_exists($filePath)) {
                $mime = $attachment['mime'] ?? '';
                $fileName = $attachment['name'] ?? 'file';

                if (str_starts_with($mime, 'image/')) {
                    $fileBytes = file_get_contents($filePath);
                    $base64Image = 'data:' . $mime . ';base64,' . base64_encode($fileBytes);

                    $messages[] = [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $userPrompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $base64Image]],
                        ]
                    ];
                    return $messages;
                } else {
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $textContent = file_get_contents($filePath);
                    $truncatedContent = mb_substr($textContent, 0, 15000);
                    $fileBlock = "\n\n📎 [HỌC TRÒ ĐÍNH KÈM TỆP: {$fileName}]\n```{$ext}\n{$truncatedContent}\n```\n";
                    $userPrompt = $fileBlock . "\n" . $userPrompt;
                }
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt,
        ];

        return $messages;
    }

    /**
     * Gửi tin nhắn đồng bộ thông thường (qua Vilao GPT-6.1-Sol).
     */
    public function sendMessage(AiConversation $conversation, string $userMessage, ?array $attachment = null): AiMessage
    {
        $displayContent = $userMessage;
        if (empty($displayContent) && $attachment) {
            $displayContent = '📎 [Đã gửi tệp: ' . ($attachment['name'] ?? 'Tệp đính kèm') . ']';
        }

        $userMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'content' => $displayContent,
            'structured_output' => $attachment ? ['attachment' => $attachment] : null,
        ]);

        $aiReplyText = '';
        $messages = $this->buildVilaoMessages($conversation, $userMessage, $attachment);

        // Gọi Vilao API (GPT-6.1-Sol)
        try {
            $url = $this->vilaoBaseUrl . '/chat/completions';
            $response = Http::timeout(25)->withHeaders([
                'Authorization' => 'Bearer ' . $this->vilaoApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => $this->vilaoModel,
                'messages' => $messages,
                'max_tokens' => 1024,
                'temperature' => 0.5,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (!empty($content)) {
                    $aiReplyText = $this->cleanAiReply($content);
                }
            } else {
                Log::warning('Vilao API error: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Vilao API exception: ' . $e->getMessage());
        }

        if (empty($aiReplyText)) {
            $aiReplyText = "Chào học trò! Thầy Biết Tuốt đã tiếp nhận câu hỏi của em: \"{$userMessage}\". Thầy đang kiểm tra kết nối với hệ thống AI tri thức, em đợi thầy một lát rồi hỏi lại nhé! ☕✨";
        }

        $assistantMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'content' => $aiReplyText,
        ]);

        $this->updateConversationTitle($conversation, $userMessage, $attachment);

        return $assistantMsgRecord;
    }

    /**
     * STREAMING DÒNG DỮ LIỆU TỨC THÌ (Server-Sent Events) qua Vilao API (GPT-6.1-Sol).
     * - Bắn từng chữ về màn hình ngay khi AI sinh ra (Time to First Token cực thấp).
     * - Tự động ghi lại tin nhắn đầy đủ vào CSDL khi stream hoàn tất.
     */
    public function streamMessage(AiConversation $conversation, string $userMessage, ?array $attachment = null, ?callable $onChunk = null): AiMessage
    {
        $displayContent = $userMessage;
        if (empty($displayContent) && $attachment) {
            $displayContent = '📎 [Đã gửi tệp: ' . ($attachment['name'] ?? 'Tệp đính kèm') . ']';
        }

        AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'content' => $displayContent,
            'structured_output' => $attachment ? ['attachment' => $attachment] : null,
        ]);

        $messages = $this->buildVilaoMessages($conversation, $userMessage, $attachment);
        $payload = [
            'model' => $this->vilaoModel,
            'messages' => $messages,
            'max_tokens' => 1200,
            'temperature' => 0.5,
            'stream' => true,
        ];
        $payloadJson = json_encode($payload);

        $totalText = '';
        $streamSuccess = false;

        $url = $this->vilaoBaseUrl . '/chat/completions';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->vilaoApiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$totalText, $onChunk) {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if ($line === 'data: [DONE]') continue;

                if (str_starts_with($line, 'data: ')) {
                    $jsonStr = substr($line, 6);
                    $json = json_decode($jsonStr, true);
                    $delta = $json['choices'][0]['delta']['content'] ?? '';
                    if ($delta !== '') {
                        $totalText .= $delta;
                        if ($onChunk) {
                            $onChunk($delta);
                        }
                    }
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($totalText)) {
            $streamSuccess = true;
        } else {
            Log::warning("Vilao streaming failed with HTTP $httpCode");
        }

        if (!$streamSuccess || empty($totalText)) {
            $totalText = "Chào học trò! Thầy Biết Tuốt đã tiếp nhận câu hỏi của em: \"{$userMessage}\". Hiện tại kết nối đến cổng tri thức của thầy đang được tối ưu, em thử bấm gửi lại nhé! ☕✨";
            if ($onChunk) {
                $onChunk($totalText);
            }
        } else {
            $totalText = $this->cleanAiReply($totalText);
        }

        $assistantMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'content' => $totalText,
        ]);

        $this->updateConversationTitle($conversation, $userMessage, $attachment);

        return $assistantMsgRecord;
    }

    private function updateConversationTitle(AiConversation $conversation, string $userMessage, ?array $attachment = null): void
    {
        if ($conversation->title === 'Cuộc trò chuyện mới') {
            $rawTitle = !empty($userMessage) ? $userMessage : ($attachment['name'] ?? 'Hỏi thầy Biết Tuốt');
            $shortTitle = mb_substr($rawTitle, 0, 45);
            if (mb_strlen($rawTitle) > 45) {
                $shortTitle .= '...';
            }
            $conversation->update(['title' => $shortTitle]);
        } else {
            $conversation->touch();
        }
    }
}
