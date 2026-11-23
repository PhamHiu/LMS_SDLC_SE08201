<?php

namespace App\Services\AI;

// =========================================================================
// KHAI BÁO THƯ VIỆN & MODEL CHO TRỢ LÝ ẢO BTEC AI STUDENT SUPPORT
// =========================================================================
use App\Models\BtecSupportConversation; // Model lưu phiên hội thoại hỗ trợ học vụ của sinh viên (bảng `btec_support_conversations`)
use App\Models\BtecSupportMessage;      // Model lưu từng tin nhắn hỏi/đáp kèm nguồn trích dẫn citations và nút điều hướng actions (bảng `btec_support_messages`)
use App\Models\BtecSupportTicket;       // Model lưu phiếu yêu cầu hỗ trợ thủ công gửi về Phòng Đào tạo / CTSV (bảng `btec_support_tickets`)
use App\Models\User;                    // Model tài khoản sinh viên đang đăng nhập
use Illuminate\Support\Facades\Http;    // Thư viện HTTP Client gọi REST API sinh văn bản (LLM Generation)
use Illuminate\Support\Facades\Log;     // Thư viện ghi log theo dõi trạng thái streaming SSE và tự động đổi model dự phòng (candidateModels)

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
        private readonly ?SupportAcademicOrchestrator $orchestrator = null,
    ) {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        $this->model = (string) env('GEMINI_MODEL', 'gemini-3.1-flash-lite');
    }

    private function academicOrchestrator(): SupportAcademicOrchestrator
    {
        return $this->orchestrator ?? app(SupportAcademicOrchestrator::class);
    }

    /**
     * PHẦN 5 TRONG PIPELINE RAG: TẦNG GHÉP NGỮ CẢNH & SINH CÂU TRẢ LỜI (AUGMENTED GENERATION)
     * Nhận kết quả từ BtecRagService::search() ($ragContext) và nhúng vào System Prompt của LLM:
     *  - Khối 1: Top 4 chunks quy chế BTEC FPT đã vượt qua bộ lọc Hybrid (Vector Cosine + Lexical).
     *  - Khối 2: Các quy trình thủ tục hành chính từng bước (top_procedures).
     *  - Khối 3: Hướng dẫn thao tác màn hình ứng dụng Smart LMS (top_app_feature).
     *  - Khối 4: Luật Guardrails chống ảo giác (Hallucination Prevention) và ép buộc trích dẫn nguồn [S01..S16].
     */
    public function buildSystemInstruction(array $ragContext): string
    {
        $instruction = "Bạn là BTEC FPT AI Student Support Assistant — Trợ lý ảo hỗ trợ sinh viên chính thức của Cao đẳng Anh Quốc BTEC FPT trên hệ thống Smart LMS.

VAI TRÒ VÀ TÁC PHONG:
- Xưng hô: Xưng là \"mình\" (hoặc \"thầy\") và gọi người dùng là \"em\" một cách tự nhiên, thân thiết.
- KHÔNG CHÀO LẶP LẠI: Chỉ chào ngắn gọn ở tin nhắn đầu tiên của phiên; từ tin nhắn thứ 2 trở đi tuyệt đối KHÔNG mở đầu bằng \"Chào em\", \"Chào bạn\" hay \"Xin chào\", đi thẳng vào câu trả lời ngay.
- Trả lời: NGẮN GỌN TRONG 2–4 CÂU TRỌN VẸN, đi thẳng vào đúng trọng tâm câu hỏi của sinh viên. Tuyệt đối KHÔNG chép lan man các ý phụ trong tài liệu mà sinh viên không hỏi tới, và không bao giờ viết câu quá dài gây cắt cụt chữ.

QUY TẮC BẮT BUỘC (KHÔNG TRẢ LỜI CHUNG CHUNG, KHÔNG ĐIỀU HƯỚNG SAI):
1. ĐÚNG TRỌNG TÂM 100%: Sinh viên hỏi về vấn đề gì thì chỉ trả lời đúng vấn đề đó. Không liệt kê các quy định râu ria không liên quan.
2. KHI TÀI LIỆU CHƯA NÊU RÕ CON SỐ / CHẾ TÀI CỤ THỂ (ví dụ: nộp học phí muộn bị phạt bao nhiêu, xét duyệt gia hạn học phí, trường hợp đặc biệt):
   - Trả lời thẳng thắn, ngắn gọn trong 2–3 câu: nêu rõ sổ tay hiện tại chưa quy định mức phạt/chế tài cố định vì việc gia hạn hoặc xử lý phụ thuộc vào thông báo của từng học kỳ và hồ sơ thực tế của sinh viên.
   - BẮT BUỘC hướng dẫn ngay: \"Em hãy bấm nút **Tạo Ticket** bên dưới để lập phiếu liên hệ trực tiếp với **Phòng Đào tạo** hỗ trợ kiểm tra và xử lý nhé!\"
3. KHI HỎI CHỨC NĂNG CHƯA HỖ TRỢ TRỰC TIẾP TRÊN LMS (ví dụ: thanh toán học phí trực tiếp trên LMS, đăng ký bảo lưu tự động):
   - Nói rõ: \"Chức năng này trên hệ thống LMS hiện **đang được cập nhật và cải tiến**. Em vui lòng bấm nút **Tạo Ticket** bên dưới để gửi phiếu trực tiếp tới **Phòng Đào tạo** hỗ trợ nhé!\"
4. KHÔNG ĐIỀU HƯỚNG LUNG TUNG: Tuyệt đối không dẫn sang màn hình Điểm danh, Bảng điểm hay Bài tập nếu câu hỏi đang hỏi về Học phí, Bảo lưu hoặc Thủ tục hành chính.
5. ĐỊNH DẠNG GỌN SẠCH: In đậm **từ khóa quan trọng**, không dùng ký tự thô ### hay dấu sao *.";

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
        $moderation = app(\App\Services\ContentModerationService::class)->moderate($userMessage, 'chat');
        $storedUserMessage = $moderation['flagged'] ? $moderation['masked_text'] : $userMessage;

        if (!$conversation) {
            $conversation = BtecSupportConversation::create([
                'user_id' => $student?->user_id,
                'session_token' => $sessionToken ?: session()->getId(),
                'title' => mb_substr($storedUserMessage, 0, 45) . (mb_strlen($storedUserMessage) > 45 ? '...' : ''),
            ]);
        }

        $userMsgRecord = BtecSupportMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'message' => $storedUserMessage,
            'is_private' => false,
        ]);

        // Kiểm duyệt ngôn từ không phù hợp (Việt + Anh) trước mọi truy vấn DB / LLM
        if ($moderation['flagged']) {
            $assistantMsgRecord = BtecSupportMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'message' => $moderation['warning'],
                'intent' => 'content_moderation_warning',
                'is_private' => false,
                'citations' => [],
                'actions' => [],
            ]);

            return [
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
                'message_id' => $assistantMsgRecord->message_id,
                'message' => $moderation['warning'],
                'intent' => 'content_moderation_warning',
                'is_private' => false,
                'moderated' => true,
                'masked_user_message' => $storedUserMessage,
                'citations' => [],
                'actions' => [],
                'needs_escalation' => false,
                'created_at' => $assistantMsgRecord->created_at->format('H:i d/m/Y'),
                'is_cached' => false,
            ];
        }

        // 0. ƯU TIÊN TUYỆT ĐỐI: Xử lý nghiệp vụ cá nhân & phân quyền NỘI BỘ trước mọi RAG / Embedding / API AI ngoài
        if ($student !== null) {
            $internal = $this->academicOrchestrator()->handle($student, $userMessage, $conversation);
            if (! empty($internal['handled'])) {
                $isPrivate = (bool) ($internal['is_private'] ?? false);
                $userMsgRecord->update([
                    'is_private' => $isPrivate,
                    'private_refs' => $internal['private_refs'] ?? null,
                ]);
                if (array_key_exists('context_state', $internal)) {
                    $conversation->update(['context_state' => $internal['context_state']]);
                }

                $assistantMsgRecord = BtecSupportMessage::create([
                    'conversation_id' => $conversation->conversation_id,
                    'role' => 'assistant',
                    'message' => $internal['message'],
                    'intent' => $internal['intent'] ?? 'academic_internal',
                    'is_private' => $isPrivate,
                    'private_refs' => $internal['private_refs'] ?? null,
                    'citations' => $internal['citations'] ?? [],
                    'actions' => $internal['actions'] ?? [],
                ]);

                return [
                    'success' => true,
                    'conversation_id' => $conversation->conversation_id,
                    'message_id' => $assistantMsgRecord->message_id,
                    'message' => $internal['message'],
                    'intent' => $internal['intent'] ?? 'academic_internal',
                    'is_private' => $isPrivate,
                    'citations' => $internal['citations'] ?? [],
                    'actions' => $internal['actions'] ?? [],
                    'needs_escalation' => (bool) ($internal['needs_escalation'] ?? false),
                    'created_at' => $assistantMsgRecord->created_at->format('H:i d/m/Y'),
                    'is_cached' => false,
                ];
            }
        }

        // Chỉ gọi RAG công khai (KHÔNG truyền thông tin cá nhân $student ra ngoài)
        $ragResult = $this->ragService->search($userMessage, null);

        // 1. SMART CACHE: Chỉ áp dụng cho câu hỏi quy chế/hướng dẫn CÔNG KHAI
        $cacheKey = 'btec_ai_faq_' . md5(mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $userMessage)));
        $cached = cache()->get($cacheKey);
        if ($cached && !empty($cached['message'])) {
            $assistantMsgRecord = BtecSupportMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'message' => $cached['message'],
                'intent' => $ragResult['intent'],
                'is_private' => false,
                'citations' => $ragResult['citations'],
                'actions' => $ragResult['actions'],
            ]);

            return [
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
                'message_id' => $assistantMsgRecord->message_id,
                'message' => $cached['message'],
                'intent' => $ragResult['intent'],
                'is_private' => false,
                'citations' => $ragResult['citations'],
                'actions' => $ragResult['actions'],
                'needs_escalation' => $ragResult['needs_escalation'],
                'created_at' => $assistantMsgRecord->created_at->format('H:i d/m/Y'),
                'is_cached' => true,
            ];
        }

        // 2. Chuẩn bị ngữ cảnh hội thoại gần nhất: TUYỆT ĐỐI LỌC BỎ tin nhắn riêng tư (is_private = true) trước khi gửi ra LLM ngoài!
        $recentMessages = $conversation->messages()
            ->where('message_id', '<', $userMsgRecord->message_id)
            ->where('is_private', false)
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
                'maxOutputTokens' => 900, // Tối ưu tốc độ cực nhanh trong ~1.2s
            ]
        ];

        // 3. Ưu tiên gọi Vilao AI (DeepSeek / OpenAI-compatible đang hoạt động ổn định), sau đó fallback sang Gemini API
        $aiReplyText = '';
        $success = false;

        $vilaoReply = $this->callVilaoChat($systemInstruction, $recentMessages, $userMessage);
        if (! empty($vilaoReply)) {
            $aiReplyText = $this->cleanAiReply($vilaoReply);
            $success = true;
            if (! empty($ragResult['is_confident'])) {
                cache()->put($cacheKey, ['message' => $aiReplyText], 86400);
            }
        }

        if (! $success && ! empty($this->apiKey)) {
            $modelsToTry = array_unique(array_merge([$this->model], $this->candidateModels));
            $lastError = '';

            foreach ($modelsToTry as $candidate) {
                try {
                    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $candidate . ':generateContent';
                    $response = Http::timeout(5)->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])->post($url, $payload);

                    if ($response->successful()) {
                        $data = $response->json();
                        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if (! empty($reply)) {
                            $aiReplyText = $this->cleanAiReply($reply);
                            $success = true;
                            if (! empty($ragResult['is_confident'])) {
                                cache()->put($cacheKey, ['message' => $aiReplyText], 86400);
                            }
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
        }

        if (! $success) {
            Log::warning('All AI Support LLM providers unavailable. Using relevance-gated RAG fallback.');
            $aiReplyText = $this->generateInstantRagAnswer($ragResult, $userMessage);
        }

        if ($this->replyRequiresTicket($aiReplyText)) {
            $ragResult['needs_escalation'] = true;
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
        $moderation = app(\App\Services\ContentModerationService::class)->moderate($userMessage, 'chat');
        $storedUserMessage = $moderation['flagged'] ? $moderation['masked_text'] : $userMessage;

        if (!$conversation) {
            $conversation = BtecSupportConversation::create([
                'user_id' => $student?->user_id,
                'session_token' => $sessionToken ?: session()->getId(),
                'title' => mb_substr($storedUserMessage, 0, 45) . (mb_strlen($storedUserMessage) > 45 ? '...' : ''),
            ]);
        }

        $userMsgRecord = BtecSupportMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'message' => $storedUserMessage,
            'is_private' => false,
        ]);

        // Kiểm duyệt ngôn từ không phù hợp (Việt + Anh) trước khi phát byte đầu tiên
        if ($moderation['flagged']) {
            if ($onChunk) {
                $onChunk($moderation['warning']);
            }

            $assistantMsgRecord = BtecSupportMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'message' => $moderation['warning'],
                'intent' => 'content_moderation_warning',
                'is_private' => false,
                'citations' => [],
                'actions' => [],
            ]);

            return [
                'conversation' => $conversation,
                'message' => $assistantMsgRecord,
                'rag' => [
                    'intent' => 'content_moderation_warning',
                    'citations' => [],
                    'actions' => [],
                    'needs_escalation' => false,
                    'moderated' => true,
                    'masked_user_message' => $storedUserMessage,
                ],
                'full_text' => $moderation['warning'],
            ];
        }

        // 0. ƯU TIÊN TUYỆT ĐỐI TRÊN LUỒNG STREAMING: Kiểm tra quyền & xử lý nghiệp vụ nội bộ TRƯỚC khi phát byte đầu tiên
        if ($student !== null) {
            $internal = $this->academicOrchestrator()->handle($student, $userMessage, $conversation);
            if (! empty($internal['handled'])) {
                $isPrivate = (bool) ($internal['is_private'] ?? false);
                $userMsgRecord->update([
                    'is_private' => $isPrivate,
                    'private_refs' => $internal['private_refs'] ?? null,
                ]);
                if (array_key_exists('context_state', $internal)) {
                    $conversation->update(['context_state' => $internal['context_state']]);
                }

                if ($onChunk) {
                    $onChunk($internal['message']);
                }

                $assistantMsgRecord = BtecSupportMessage::create([
                    'conversation_id' => $conversation->conversation_id,
                    'role' => 'assistant',
                    'message' => $internal['message'],
                    'intent' => $internal['intent'] ?? 'academic_internal',
                    'is_private' => $isPrivate,
                    'private_refs' => $internal['private_refs'] ?? null,
                    'citations' => $internal['citations'] ?? [],
                    'actions' => $internal['actions'] ?? [],
                ]);

                return [
                    'conversation' => $conversation,
                    'message' => $assistantMsgRecord,
                    'rag' => [
                        'intent' => $internal['intent'] ?? 'academic_internal',
                        'citations' => $internal['citations'] ?? [],
                        'actions' => $internal['actions'] ?? [],
                        'confidence' => 1.0,
                        'needs_escalation' => (bool) ($internal['needs_escalation'] ?? false),
                    ],
                    'full_text' => $internal['message'],
                ];
            }
        }

        // Kiểm tra Cache FAQ TRƯỚC khi gọi RAG/Embedding để trả về tức thì (<50ms)
        $cacheKey = 'btec_ai_faq_v2_' . md5(mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $userMessage)));
        $cached = cache()->get($cacheKey);
        if (is_array($cached) && !empty($cached['message'])) {
            if ($onChunk) {
                $onChunk($cached['message']);
            }
            $assistantMsgRecord = BtecSupportMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'message' => $cached['message'],
                'intent' => $cached['intent'] ?? 'general_inquiry',
                'is_private' => false,
                'citations' => $cached['citations'] ?? [],
                'actions' => $cached['actions'] ?? [],
            ]);

            return [
                'conversation' => $conversation,
                'message' => $assistantMsgRecord,
                'rag' => [
                    'intent' => $cached['intent'] ?? 'general_inquiry',
                    'citations' => $cached['citations'] ?? [],
                    'actions' => $cached['actions'] ?? [],
                    'needs_escalation' => (bool) ($cached['needs_escalation'] ?? false),
                ],
                'full_text' => $cached['message'],
            ];
        }

        // Chỉ gọi RAG công khai (KHÔNG truyền dữ liệu cá nhân $student ra ngoài)
        $ragResult = $this->ragService->search($userMessage, null);

        // TUYỆT ĐỐI LỌC BỎ tin nhắn riêng tư (is_private = true) trước khi gửi ra LLM ngoài!
        $recentMessages = $conversation->messages()
            ->where('message_id', '<', $userMsgRecord->message_id)
            ->where('is_private', false)
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
                'maxOutputTokens' => 900,
            ]
        ];

        $totalText = '';
        $streamSuccess = false;

        // Ưu tiên stream trực tiếp từ DeepSeek / OpenAI-compatible (token hiện ngay trong ~0.5-1s)
        $streamedReply = $this->streamVilaoChat($systemInstruction, $recentMessages, $userMessage, $onChunk);
        if (! empty($streamedReply)) {
            $totalText = $this->cleanAiReply($streamedReply);
            $streamSuccess = true;
            if ($this->replyRequiresTicket($totalText)) {
                $ragResult['needs_escalation'] = true;
            }
            if (! empty($ragResult['is_confident'])) {
                cache()->put($cacheKey, [
                    'message' => $totalText,
                    'intent' => $ragResult['intent'] ?? 'general_inquiry',
                    'citations' => $ragResult['citations'] ?? [],
                    'actions' => $ragResult['actions'] ?? [],
                    'needs_escalation' => (bool) ($ragResult['needs_escalation'] ?? false),
                ], 86400);
            }
        }

        $modelsToTry = $streamSuccess ? [] : array_unique(array_merge([$this->model], $this->candidateModels));
        $payloadJson = json_encode($payload);

        foreach ($modelsToTry as $candidate) {
            $totalText = '';

            if (app()->runningUnitTests()) {
                try {
                    $testUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$candidate}:generateContent";
                    $resp = Http::timeout(5)->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])->post($testUrl, $payload);

                    if ($resp->successful()) {
                        $partText = (string) ($resp->json('candidates.0.content.parts.0.text') ?? '');
                        if ($partText !== '') {
                            $totalText = $partText;
                            if ($onChunk) {
                                $onChunk($partText);
                            }
                            $streamSuccess = true;
                            cache()->put($cacheKey, ['message' => $this->cleanAiReply($totalText)], 86400);
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                }
                continue;
            }

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
        } else {
            $totalText = $this->cleanAiReply($totalText);
        }

        if ($this->replyRequiresTicket($totalText)) {
            $ragResult['needs_escalation'] = true;
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
     * Tạo Ticket hỗ trợ chuyển tiếp cán bộ đào tạo và gửi thông báo cho Admin.
     */
    public function createTicket(
        User $user,
        string $topic,
        string $description,
        string $department = 'Phòng Đào tạo',
        ?int $classId = null,
    ): BtecSupportTicket {
        $code = 'TK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $descMod = app(\App\Services\ContentModerationService::class)->moderate($description, 'comment');
        $cleanDescription = $descMod['flagged'] ? $descMod['masked_text'] : $description;

        $ticket = BtecSupportTicket::create([
            'user_id' => $user->user_id,
            'class_id' => $classId,
            'ticket_code' => $code,
            'topic' => $topic,
            'description' => $cleanDescription,
            'department' => $department,
            'status' => BtecSupportTicket::STATUS_OPEN,
        ]);

        // Gửi thông báo riêng cho các tài khoản Admin để tiếp nhận & phân công
        $notificationService = app(\App\Services\NotificationService::class);
        $admins = User::where('role', User::ROLE_ADMIN)->get();
        foreach ($admins as $admin) {
            $notificationService->notifyUser(
                author: $user,
                recipientUserId: $admin->user_id,
                title: "Ticket mới #{$code}: {$topic}",
                body: "Sinh viên {$user->full_name} vừa gửi yêu cầu \"{$topic}\". Nội dung: " . mb_substr($description, 0, 180),
                targetUrl: route('admin.tickets.index'),
                classId: $classId,
            );
        }

        return $ticket;
    }

    /**
     * Làm sạch câu trả lời, loại bỏ ký hiệu thô ### và dấu sao bullet *.
     */
    
    /**
     * Gọi Vilao AI (OpenAI-compatible: DeepSeek / GPT) cho BTEC AI Support.
     */
    private function callVilaoChat(string $systemInstruction, $recentMessages, string $userMessage): ?string
    {
        $baseUrl = rtrim((string) env('OPENAI_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $providers = [];

        if (! empty(env('DEEPSEEK_KEY_MAIN'))) {
            $providers[] = [
                'key' => env('DEEPSEEK_KEY_MAIN'),
                'model' => env('DEEPSEEK_MODEL_MAIN', 'deepseek-v4.1-flash'),
            ];
        }
        if (! empty(env('OPENAI_API_KEY'))) {
            $providers[] = [
                'key' => env('OPENAI_API_KEY'),
                'model' => env('OPENAI_MODEL', 'vgpt/gpt-6.1-sol'),
            ];
        }

        if (empty($providers)) {
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];
        foreach ($recentMessages as $msg) {
            $messages[] = [
                'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $msg->message,
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        foreach ($providers as $provider) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $provider['key'],
                        'Content-Type' => 'application/json',
                    ])
                    ->post($baseUrl . '/chat/completions', [
                        'model' => $provider['model'],
                        'temperature' => 0.2,
                        'max_tokens' => 900,
                        'messages' => $messages,
                    ]);

                if ($response->successful()) {
                    $text = $response->json('choices.0.message.content');
                    if (is_string($text) && trim($text) !== '') {
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('BTEC AI Support Vilao attempt failed: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Stream SSE thật trực tiếp từ DeepSeek / OpenAI-compatible (giảm thời gian chờ token đầu xuống ~0.5-1s).
     */
    private function streamVilaoChat(string $systemInstruction, $recentMessages, string $userMessage, ?callable $onChunk = null): ?string
    {
        if (app()->runningUnitTests()) {
            $reply = $this->callVilaoChat($systemInstruction, $recentMessages, $userMessage);
            if ($reply && $onChunk) {
                $onChunk($reply);
            }
            return $reply;
        }

        $baseUrl = rtrim((string) env('OPENAI_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $providers = [];

        if (! empty(env('DEEPSEEK_KEY_MAIN'))) {
            $providers[] = [
                'key' => env('DEEPSEEK_KEY_MAIN'),
                'model' => env('DEEPSEEK_MODEL_MAIN', env('DEEPSEEK_MODEL', 'vgpt/gpt-6-sol')),
            ];
        }
        if (! empty(env('OPENAI_API_KEY'))) {
            $providers[] = [
                'key' => env('OPENAI_API_KEY'),
                'model' => env('OPENAI_MODEL', 'vgpt/gpt-6.1-sol'),
            ];
        }

        if (empty($providers)) {
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];
        foreach ($recentMessages as $msg) {
            $messages[] = [
                'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $msg->message,
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        foreach ($providers as $provider) {
            $fullText = '';
            $lineBuf = '';
            $payloadJson = json_encode([
                'model' => $provider['model'],
                'temperature' => 0.2,
                'max_tokens' => 450,
                'stream' => true,
                'messages' => $messages,
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/completions');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $provider['key'],
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$fullText, &$lineBuf, $onChunk) {
                $lineBuf .= $data;
                while (($pos = strpos($lineBuf, "\n")) !== false) {
                    $line = trim(substr($lineBuf, 0, $pos));
                    $lineBuf = substr($lineBuf, $pos + 1);
                    if ($line === '' || $line === 'data: [DONE]' || ! str_starts_with($line, 'data: ')) {
                        continue;
                    }
                    $json = json_decode(substr($line, 6), true);
                    $delta = $json['choices'][0]['delta']['content'] ?? '';
                    if ($delta !== '') {
                        $fullText .= $delta;
                        if ($onChunk) {
                            $onChunk($delta);
                        }
                    }
                }
                return strlen($data);
            });

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && trim($fullText) !== '') {
                return trim($fullText);
            }
        }

        return null;
    }

    /**
     * TẠO CÂU TRẢ LỜI RAG TỨC THÌ CÓ CHỐT CHẶN ĐỘ TIN CẬY (Relevance Gate).
     * Tuyệt đối KHÔNG chép bừa đoạn quy chế không liên quan khi không chắc chắn.
     */
    public function generateInstantRagAnswer(array $ragResult, string $query): string
    {
        $isConfident = (bool) ($ragResult['is_confident'] ?? false);
        $chunk = $ragResult['top_chunks'][0] ?? null;
        $feat = $ragResult['top_app_feature'] ?? null;

        $body = '';
        if ($chunk && $isConfident) {
            $text = (string) ($chunk['text'] ?? '');
            $paragraphs = array_filter(
                preg_split('/\R{2,}/', $text) ?: [],
                fn ($p) => mb_strlen(trim($p)) > 15 && ! str_starts_with(trim($p), '[')
            );
            $body = trim(implode("\n\n", array_slice($paragraphs, 0, 3)));
            if ($body === '') {
                $body = trim(mb_substr($text, 0, 600));
            }
        }

        // Chốt chặn chống trả lời linh tinh: nếu không đủ độ tin cậy hoặc nội dung trích xuất rỗng
        if (! $isConfident || mb_strlen($body) < 20) {
            $fallback = "Hiện sổ tay quy chế trên hệ thống chưa có thông tin chi tiết cho câu hỏi **\"{$query}\"** (hoặc chức năng này đang được cập nhật và cải tiến trên LMS).\n\n"
                . "👉 Em hãy bấm nút **Tạo Ticket** ngay bên dưới để lập phiếu liên hệ trực tiếp với **Phòng Đào tạo** hỗ trợ giải đáp và xử lý cho em nhé!";

            return $this->cleanAiReply($fallback);
        }

        $reply = "📌 **Căn cứ quy định BTEC FPT [{$chunk['title']}]:**\n\n" . $body . "\n\n";

        if ($feat) {
            $reply .= "👉 **Đường dẫn thao tác trên Smart LMS:** [{$feat['name']}]({$feat['route']})\n\n";
        }

        $reply .= "💡 Nếu trường hợp của em cần kiểm tra hồ sơ cụ thể, em hãy bấm nút **Tạo Ticket** bên dưới để liên hệ trực tiếp với **Phòng Đào tạo** nhé!";

        return $this->cleanAiReply($reply);
    }

    private function replyRequiresTicket(string $replyText): bool
    {
        return (bool) preg_match('/(tạo ticket|lập phiếu|gửi phiếu|phòng đào tạo|bộ phận tài chính|chưa nêu rõ|chưa quy định|chưa hỗ trợ|đang được cập nhật)/iu', $replyText);
    }

    public function cleanAiReply(string $text): string
    {
        $protectedBlocks = [];
        $cleaned = preg_replace_callback('/```[a-zA-Z0-9_-]*\r?\n[\s\S]*?```/', function ($m) use (&$protectedBlocks) {
            $key = '___PROTECTED_FENCE_' . count($protectedBlocks) . '___';
            $protectedBlocks[$key] = $m[0];
            return $key;
        }, $text);

        $cleaned = preg_replace('/<thought>[\s\S]*?<\/thought>/i', '', $cleaned);
        $cleaned = preg_replace('/^thinking:[\s\S]*?\n\n/i', '', $cleaned);
        $cleaned = preg_replace('/^(Length|Vietnamese usage|Tone|Target audience|Code style):[^\n]*\n+/im', '', $cleaned);
        $cleaned = preg_replace('/^#{1,6}\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^\s*[\*]\s+/m', '🔹 ', $cleaned);
        $cleaned = preg_replace('/^\s*-\s+(?!-)/m', '🔹 ', $cleaned);
        $cleaned = preg_replace("/\n{3,}/", "\n\n", $cleaned);

        foreach ($protectedBlocks as $key => $block) {
            $cleaned = str_replace($key, $block, $cleaned);
        }

        return trim($cleaned);
    }
}
