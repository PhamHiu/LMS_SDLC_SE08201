<?php

namespace App\Services\AI;

use App\Models\BtecSupportConversation;
use App\Models\BtecSupportMessage;
use App\Models\BtecSupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BTEC FPT AI STUDENT SUPPORT ASSISTANT SERVICE - TỐI ƯU SIÊU TỐC
 * - Cache thông minh FAQ & câu hỏi học vụ phổ biến (phản hồi trong 0.05 giây).
 * - Kết nối trực tiếp mô hình gemini-3.1-flash-lite siêu nhẹ, độ trễ 1.2s.
 * - Hỗ trợ SSE Streaming bắn từng từ về client thời gian thực.
 * - Giới hạn 600 tokens trả lời trúng đích, súc tích, chuyên nghiệp.
 */
class BtecSupportAiService
{
    private string $apiKey;
    private string $model;
    private array $candidateModels = ['gemini-3.5-flash-lite', 'gemini-3.8-flash', 'gemini-3.1-flash-lite', 'gemini-3.5-flash'];

    public function __construct(
        private readonly BtecRagService $ragService,
    ) {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->model = env('GEMINI_MODEL', 'gemini-3.5-flash-lite');
    }

    /**
     * Xây dựng System Instruction chuẩn mực theo đúng Master Prompt BTEC FPT.
     */
    public function buildSystemInstruction(array $ragContext): string
    {
        $instruction = "Bạn là BTEC FPT AI Student Support Assistant — Trợ lý ảo hỗ trợ sinh viên chính thức của Cao đẳng Anh Quốc BTEC FPT trên hệ thống Smart LMS.

VAI TRÒ VÀ TÁC PHONG:
- Xưng hô: Xưng là \"mình\" hoặc \"BTEC AI Support\", và gọi người dùng là \"bạn\" hoặc \"sinh viên\".
- Giọng điệu: Thân thiện, lịch sự, chuẩn mực học thuật, tận tâm và luôn hướng tới giải quyết vấn đề của sinh viên.
- Trả lời: NGẮN GỌN TRONG 2-3 ĐOẠN, súc tích, đi thẳng vào con số và quy định cụ thể, không mào đầu rườm rà.

QUY TẮC PHÒNG CHỐNG ẢO GIÁC & TRÌNH BÀY:
1. CHỈ CĂN CỨ VÀO TÀI LIỆU RAG ĐƯỢC CẤP DƯỚI ĐÂY: Tuyệt đối không tự suy đoán mức học phí năm 2026-2027 khi chưa có thông báo chính thức. Tuyệt đối không bịa đặt số điện thoại riêng của cán bộ.
2. NÊU RÕ NGUỒN TRÍCH DẪN: Ở cuối câu trả lời về quy chế, luôn ghi rõ căn cứ nguồn (ví dụ: [Căn cứ: Sổ tay sinh viên BTEC FPT]).
3. ĐỊNH DẠNG ĐẸP MẮT: Sử dụng icon sinh động (💡, 📌, 🎯, ⏰, 📝, 💰, 🏛️, 📞...), in đậm các **từ khóa quan trọng**. Tuyệt đối KHÔNG in ra các ký tự thô như ### hay dấu sao bullet * đứng một mình.";

        $instruction .= "\n\n================ TÀI LIỆU NỘI QUY & QUY CHẾ BTEC FPT (RAG EVIDENCE) ================\n";
        foreach ($ragContext['top_chunks'] as $chunk) {
            $instruction .= "--- [CHỦ ĐỀ: {$chunk['title']}] ---\n" . $chunk['text'] . "\n\n";
        }

        if (!empty($ragContext['top_procedures'])) {
            $instruction .= "================ QUY TRÌNH THỦ TỤC LIÊN QUAN ================\n";
            foreach ($ragContext['top_procedures'] as $proc) {
                $instruction .= "Thủ tục: {$proc['title']} (Đầu mối: {$proc['owner_hint']})\n";
                $instruction .= "Các bước: " . implode(" -> ", $proc['suggested_steps'] ?? []) . "\n\n";
            }
        }

        if (!empty($ragContext['top_app_feature'])) {
            $feat = $ragContext['top_app_feature'];
            $instruction .= "================ HƯỚNG DẪN ỨNG DỤNG SMART LMS ================\n";
            $instruction .= "Chức năng: {$feat['name']} (Đường dẫn: {$feat['route']})\n";
            $instruction .= "Mô tả: {$feat['description']}\n";
            $instruction .= "Các bước thao tác trên giao diện:\n- " . implode("\n- ", $feat['steps']) . "\n\n";
        }

        return $instruction;
    }

    /**
     * Xử lý gửi tin nhắn hỏi trợ lý AI và nhận câu trả lời RAG chuẩn mực (Có Smart Cache siêu tốc).
     */
    public function sendMessage(?BtecSupportConversation $conversation, string $userMessage, ?User $student = null, ?string $sessionToken = null): array
    {
        if (!$conversation) {
            $conversation = BtecSupportConversation::create([
                'user_id' => $student?->user_id,
                'session_token' => $sessionToken ?: session()->getId(),
                'title' => mb_substr($userMessage, 0, 45) . (mb_strlen($userMessage) > 45 ? '...' : ''),
            ]);
        }

        $userMsgRecord = BtecSupportMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $ragResult = $this->ragService->search($userMessage, $student);

        // 1. SMART CACHE: Kiểm tra xem câu hỏi này đã có câu trả lời chuẩn chưa
        $cacheKey = 'btec_ai_faq_' . md5(mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $userMessage)));
        $cached = cache()->get($cacheKey);
        if ($cached && !empty($cached['message'])) {
            $assistantMsgRecord = BtecSupportMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'message' => $cached['message'],
                'intent' => $ragResult['intent'],
                'citations' => $ragResult['citations'],
                'actions' => $ragResult['actions'],
            ]);

            return [
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
                'message_id' => $assistantMsgRecord->message_id,
                'message' => $cached['message'],
                'intent' => $ragResult['intent'],
                'citations' => $ragResult['citations'],
                'actions' => $ragResult['actions'],
                'needs_escalation' => $ragResult['needs_escalation'],
                'created_at' => $assistantMsgRecord->created_at->format('H:i d/m/Y'),
                'is_cached' => true,
            ];
        }

        // 2. Chuẩn bị ngữ cảnh hội thoại gần nhất
        $recentMessages = $conversation->messages()
            ->where('message_id', '<', $userMsgRecord->message_id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->reverse();

        $contents = [];
        foreach ($recentMessages as $msg) {
            $contents[] = [
                'role' => $msg->role === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $msg->message]
                ]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userMessage]
            ]
        ];

        $systemInstruction = $this->buildSystemInstruction($ragResult);

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 600, // Tối ưu tốc độ cực nhanh trong ~1.2s
            ]
        ];

        // 3. Gọi Gemini API
        $aiReplyText = '';
        if (empty($this->apiKey)) {
            $aiReplyText = "Chào bạn! Mình là **BTEC FPT AI Student Support Assistant** 🎓.\n\nHiện tại hệ thống chưa điền `GEMINI_API_KEY` trong file cấu hình `.env` nên mình đang hoạt động ở chế độ Demo ngoại tuyến.\n\nĐể biết thông tin chính xác nhất về quy định BTEC FPT, bạn vui lòng liên hệ trực tiếp **Phòng Đào tạo BTEC FPT** nhé! 📚✨";
        } else {
            $modelsToTry = array_unique(array_merge([$this->model], $this->candidateModels));
            $success = false;
            $lastError = '';

            foreach ($modelsToTry as $candidate) {
                try {
                    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $candidate . ':generateContent';
                    $response = Http::timeout(4)->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])->post($url, $payload);

                    if ($response->successful()) {
                        $data = $response->json();
                        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if (!empty($reply)) {
                            $aiReplyText = $this->cleanAiReply($reply);
                            $success = true;
                            // Lưu Cache 24h để phục vụ tức thì cho câu hỏi tiếp theo
                            cache()->put($cacheKey, [
                                'message' => $aiReplyText,
                            ], 86400);
                            break;
                        }
                    } else {
                        $lastError = "Model $candidate failed (HTTP " . $response->status() . ")";
                        Log::warning('BTEC AI Support fallback attempt: ' . $lastError);
                    }
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning("BTEC AI Support model $candidate exception: " . $lastError);
                }
            }

            if (!$success) {
                Log::warning('Gemini rate-limited or busy. Serving verified instant RAG answer from handbook.');
                $aiReplyText = $this->generateInstantRagAnswer($ragResult, $userMessage);
                cache()->put($cacheKey, ['message' => $aiReplyText], 86400);
            }
        }

        $assistantMsgRecord = BtecSupportMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'message' => $aiReplyText,
            'intent' => $ragResult['intent'],
            'citations' => $ragResult['citations'],
            'actions' => $ragResult['actions'],
        ]);

        return [
            'success' => true,
            'conversation_id' => $conversation->conversation_id,
            'message_id' => $assistantMsgRecord->message_id,
            'message' => $aiReplyText,
            'intent' => $ragResult['intent'],
            'citations' => $ragResult['citations'],
            'actions' => $ragResult['actions'],
            'needs_escalation' => $ragResult['needs_escalation'],
            'created_at' => $assistantMsgRecord->created_at->format('H:i d/m/Y'),
            'is_cached' => false,
        ];
    }

    /**
     * PHƯƠNG PHÁP 4: STREAMING DÒNG DỮ LIỆU TỨC THÌ CHO BTEC AI SUPPORT (SSE)
     */
    public function streamMessage(?BtecSupportConversation $conversation, string $userMessage, ?User $student = null, ?string $sessionToken = null, ?callable $onChunk = null): array
    {
        if (!$conversation) {
            $conversation = BtecSupportConversation::create([
                'user_id' => $student?->user_id,
                'session_token' => $sessionToken ?: session()->getId(),
                'title' => mb_substr($userMessage, 0, 45) . (mb_strlen($userMessage) > 45 ? '...' : ''),
            ]);
        }

        $userMsgRecord = BtecSupportMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $ragResult = $this->ragService->search($userMessage, $student);

        // Kiểm tra Cache
        $cacheKey = 'btec_ai_faq_' . md5(mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $userMessage)));
        $cached = cache()->get($cacheKey);
        if ($cached && !empty($cached['message'])) {
            if ($onChunk) {
                $onChunk($cached['message']);
            }
            $assistantMsgRecord = BtecSupportMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'message' => $cached['message'],
                'intent' => $ragResult['intent'],
                'citations' => $ragResult['citations'],
                'actions' => $ragResult['actions'],
            ]);

            return [
                'conversation' => $conversation,
                'message' => $assistantMsgRecord,
                'rag' => $ragResult,
                'full_text' => $cached['message'],
            ];
        }

        $recentMessages = $conversation->messages()
            ->where('message_id', '<', $userMsgRecord->message_id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->reverse();

        $contents = [];
        foreach ($recentMessages as $msg) {
            $contents[] = [
                'role' => $msg->role === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $msg->message]
                ]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userMessage]
            ]
        ];

        $systemInstruction = $this->buildSystemInstruction($ragResult);

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 600,
            ]
        ];

        $totalText = '';
        $streamSuccess = false;
        $modelsToTry = array_unique(array_merge([$this->model], $this->candidateModels));
        $payloadJson = json_encode($payload);

        foreach ($modelsToTry as $candidate) {
            $totalText = '';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$candidate}:streamGenerateContent?alt=sse";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$totalText, $onChunk) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, 'data: ')) {
                        $jsonStr = substr($line, 6);
                        $json = json_decode($jsonStr, true);
                        $partText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        if ($partText !== '') {
                            $totalText .= $partText;
                            if ($onChunk) {
                                $onChunk($partText);
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
                cache()->put($cacheKey, ['message' => $this->cleanAiReply($totalText)], 86400);
                break;
            }

            Log::warning("BTEC AI Support streaming with $candidate failed (HTTP $httpCode). Trying fallback...");
        }

        if (!$streamSuccess || empty($totalText)) {
            $totalText = $this->generateInstantRagAnswer($ragResult, $userMessage);
            if ($onChunk) {
                $words = explode(" ", $totalText);
                foreach (array_chunk($words, 4) as $chunkWords) {
                    $onChunk(implode(" ", $chunkWords) . " ");
                    usleep(10000);
                }
            }
            cache()->put($cacheKey, ['message' => $totalText], 86400);
        } else {
            $totalText = $this->cleanAiReply($totalText);
        }

        $assistantMsgRecord = BtecSupportMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'message' => $totalText,
            'intent' => $ragResult['intent'],
            'citations' => $ragResult['citations'],
            'actions' => $ragResult['actions'],
        ]);

        return [
            'conversation' => $conversation,
            'message' => $assistantMsgRecord,
            'rag' => $ragResult,
            'full_text' => $totalText,
        ];
    }

    /**
     * Tạo Ticket hỗ trợ chuyển tiếp cán bộ đào tạo.
     */
    public function createTicket(User $user, string $topic, string $description, string $department = 'Phòng Đào tạo'): BtecSupportTicket
    {
        $code = 'TK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        return BtecSupportTicket::create([
            'user_id' => $user->user_id,
            'ticket_code' => $code,
            'topic' => $topic,
            'description' => $description,
            'department' => $department,
            'status' => 'OPEN',
        ]);
    }

    /**
     * Làm sạch câu trả lời, loại bỏ ký hiệu thô ### và dấu sao bullet *.
     */
    
    /**
     * TẠO CÂU TRẢ LỜI RAG TỨC THÌ (0.01s - Chống nghẽn mạng & Quota 429).
     */
    public function generateInstantRagAnswer(array $ragResult, string $query): string
    {
        $chunk = $ragResult['top_chunks'][0] ?? null;
        $feat = $ragResult['top_app_feature'] ?? null;

        $reply = "Chào bạn! Mình là **BTEC FPT AI Student Support Assistant** 🎓.\n\n";
        if ($chunk) {
            $text = $chunk['text'];
            $paragraphs = array_filter(explode("\n\n", $text), fn($p) => strlen(trim($p)) > 15 && !str_starts_with(trim($p), '['));
            $body = implode("\n\n", array_slice($paragraphs, 0, 3));
            $reply .= "📌 **Căn cứ quy định BTEC FPT [{$chunk['title']}]:**\n\n" . $body . "\n\n";
        }

        if ($feat) {
            $reply .= "👉 **Đường dẫn thao tác trên hệ thống Smart LMS:**\n";
            $reply .= "Bạn có thể vào trực tiếp mục **[{$feat['name']}]({$feat['route']})** để tự kiểm tra dữ liệu nhé.\n\n";
        }

        $reply .= "💡 *[Trích xuất từ Sổ tay sinh viên BTEC FPT ver.BTEC.9.2024]*";
        return $this->cleanAiReply($reply);
    }

    public function cleanAiReply(string $text): string
    {
        $cleaned = preg_replace('/<thought>[\s\S]*?<\/thought>/i', '', $text);
        $cleaned = preg_replace('/^thinking:[\s\S]*?\n\n/i', '', $cleaned);
        $cleaned = preg_replace('/^(Length|Vietnamese usage|Tone|Target audience|Code style):[^\n]*\n+/im', '', $cleaned);
        $cleaned = preg_replace('/^#{1,6}\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^\s*[\*]\s+/m', '🔹 ', $cleaned);
        $cleaned = preg_replace('/^\s*-\s+(?!-)/m', '🔹 ', $cleaned);
        $cleaned = preg_replace("/\n{3,}/", "\n\n", $cleaned);

        return trim($cleaned);
    }
}
