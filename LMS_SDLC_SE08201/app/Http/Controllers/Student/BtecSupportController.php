<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\BtecSupportConversation;
use App\Models\BtecSupportMessage;
use App\Services\AI\BtecSupportAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BtecSupportController extends Controller
{
    public function __construct(
        private readonly BtecSupportAiService $supportService
    ) {
    }

    /**
     * Khởi tạo hội thoại và lấy dữ liệu ban đầu cho Floating Bubble.
     */
    public function init(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $conversation = BtecSupportConversation::where('user_id', $user->user_id)
            ->latest()
            ->first();

        $messages = [];
        if ($conversation) {
            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->take(30)
                ->get()
                ->map(fn ($m) => [
                    'message_id' => $m->message_id,
                    'role' => $m->role,
                    'message' => $m->message,
                    'citations' => $m->citations,
                    'actions' => $m->actions,
                    'feedback' => $m->feedback,
                    'created_at' => $m->created_at->format('H:i d/m'),
                ]);
        }

        $defaultSuggestions = [
            '⏰ Nghỉ mấy buổi thì bị cấm thi môn học?',
            '📝 Quy định nộp Assignment và tỷ lệ đạo văn thế nào?',
            '💰 Hạn nộp học phí và quy định chậm đóng tiền?',
            '📱 Hướng dẫn cách xem Lịch học và Điểm danh trên ứng dụng?',
            '🏛️ Điều kiện và thủ tục bảo lưu học tập tại BTEC FPT?',
            '📞 Tôi cần liên hệ ai ở Phòng Đào tạo hoặc Dịch vụ sinh viên?',
        ];

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation?->conversation_id,
            'messages' => $messages,
            'suggestions' => $defaultSuggestions,
            'student' => [
                'name' => $user->full_name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Gửi tin nhắn hỏi trợ lý AI và nhận câu trả lời RAG.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:3000',
            'conversation_id' => 'nullable|exists:btec_support_conversations,conversation_id',
        ]);

        $user = $request->user();
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = BtecSupportConversation::where('user_id', $user->user_id)
                ->where('conversation_id', $request->conversation_id)
                ->first();
        }

        $result = $this->supportService->sendMessage(
            $conversation,
            $request->message,
            $user
        );

        return response()->json($result);
    }

    /**
     * Tạo Ticket chuyển tiếp cho Phòng Đào tạo / CTSV.
     */
    
    /**
     * PHƯƠNG PHÁP 4: Server-Sent Events (SSE) Streaming tin nhắn BTEC AI Support
     */
    public function stream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'message' => 'required|string|max:3000',
            'conversation_id' => 'nullable|exists:btec_support_conversations,conversation_id',
        ]);

        $user = $request->user();
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = BtecSupportConversation::where('user_id', $user->user_id)
                ->where('conversation_id', $request->conversation_id)
                ->first();
        }

        $userMessage = $request->message;

        return response()->stream(function () use ($conversation, $userMessage, $user) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $res = $this->supportService->streamMessage(
                $conversation,
                $userMessage,
                $user,
                session()->getId(),
                function ($chunk) {
                    echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            );

            echo "data: " . json_encode([
                'done' => true,
                'status' => 'ok',
                'conversation_id' => $res['conversation']->conversation_id,
                'message_id' => $res['message']->message_id,
                'intent' => $res['rag']['intent'] ?? 'general',
                'sources' => $res['rag']['citations'] ?? [],
                'app_actions' => $res['rag']['actions'] ?? [],
                'confidence' => $res['rag']['confidence'] ?? 0.9,
                'suggest_ticket' => $res['rag']['needs_escalation'] ?? false,
                'created_at' => $res['message']->created_at->format('H:i d/m'),
            ]) . "\n\n";

            if (ob_get_level() > 0) ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function submitTicket(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:150',
            'description' => 'required|string|max:3000',
            'department' => 'nullable|string|max:100',
        ]);

        $ticket = $this->supportService->createTicket(
            $request->user(),
            $request->topic,
            $request->description,
            $request->input('department', 'Phòng Đào tạo')
        );

        return response()->json([
            'success' => true,
            'ticket_code' => $ticket->ticket_code,
            'message' => "Yêu cầu của bạn đã được ghi nhận với mã phiếu: {$ticket->ticket_code}. Cán bộ phụ trách sẽ kiểm tra và phản hồi qua email của bạn.",
        ]);
    }

    /**
     * Đánh giá câu trả lời (Helpful / Not Helpful).
     */
    public function feedback(Request $request): JsonResponse
    {
        $request->validate([
            'message_id' => 'required|exists:btec_support_messages,message_id',
            'feedback' => 'required|in:helpful,not_helpful',
        ]);

        $msg = BtecSupportMessage::whereHas('conversation', function ($q) use ($request) {
            $q->where('user_id', $request->user()->user_id);
        })->find($request->message_id);

        if ($msg) {
            $msg->update(['feedback' => $request->feedback]);
        }

        return response()->json(['success' => true]);
    }
}

