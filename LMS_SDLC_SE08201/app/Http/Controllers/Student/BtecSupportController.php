<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\BtecSupportConversation;
use App\Models\BtecSupportMessage;
use App\Services\AI\BtecSupportAiService;
use App\Services\AI\SupportAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BtecSupportController extends Controller
{
    public function __construct(
        private readonly BtecSupportAiService $supportService,
        private readonly SupportAuthorizationService $authService,
    ) {
    }

    /**
     * Khởi tạo hội thoại và kiểm tra lại quyền cho từng tin nhắn lịch sử riêng tư.
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
                ->with('conversation')
                ->orderByDesc('message_id')
                ->take(40)
                ->get()
                ->reverse()
                ->values()
                ->map(fn (BtecSupportMessage $m) => $this->authService->formatHistoryMessageForActor($user, $m))
                ->values();
        }

        $defaultSuggestions = [
            'Lịch học ngày mai của em có môn nào?',
            'Điểm các môn hiện tại của em ra sao?',
            'Em còn nghỉ được mấy buổi nữa?',
            'Nghỉ mấy buổi thì bị trượt môn học?',
            'Quy định nộp Assignment và tỷ lệ đạo văn thế nào?',
            'Điều kiện và thủ tục bảo lưu học tập?',
        ];

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation?->conversation_id,
            'messages' => $messages,
            'suggestions' => $defaultSuggestions,
            'student' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Gửi tin nhắn thường: xác thực chủ hội thoại TRƯỚC khi xử lý.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:3000',
            'conversation_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $existing = BtecSupportConversation::find((int) $request->conversation_id);
            if ($existing && ! $this->authService->canAccessConversation($user, $existing)) {
                $this->authService->logDeniedAttempt($user, 'conversation_access', 'Attempted to access another user conversation');
                return response()->json([
                    'success' => false,
                    'message' => 'Hội thoại này không thuộc quyền truy cập của tài khoản hiện tại.',
                ], 403);
            }
            $conversation = $existing;
        }

        $result = $this->supportService->sendMessage(
            $conversation,
            $request->message,
            $user
        );

        return response()->json($result);
    }

    /**
     * Server-Sent Events (SSE) Streaming: kiểm tra quyền hội thoại TRƯỚC khi phát byte đầu tiên.
     */
    public function stream(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'message' => 'required|string|max:3000',
            'conversation_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $existing = BtecSupportConversation::find((int) $request->conversation_id);
            if ($existing && ! $this->authService->canAccessConversation($user, $existing)) {
                $this->authService->logDeniedAttempt($user, 'conversation_stream_access', 'Attempted to stream into another user conversation');
                return response()->json([
                    'success' => false,
                    'message' => 'Hội thoại này không thuộc quyền truy cập của tài khoản hiện tại.',
                ], 403);
            }
            $conversation = $existing;
        }

        $userMessage = $request->message;

        return response()->stream(function () use ($conversation, $userMessage, $user) {
            if (! app()->runningUnitTests()) {
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }

            $res = $this->supportService->streamMessage(
                $conversation,
                $userMessage,
                $user,
                session()->getId(),
                function ($chunk) {
                    echo "data: " . json_encode(['chunk' => $chunk], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                    if (! app()->runningUnitTests()) {
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                }
            );

            echo "data: " . json_encode([
                'done' => true,
                'status' => 'ok',
                'conversation_id' => $res['conversation']->conversation_id,
                'message_id' => $res['message']->message_id,
                'intent' => $res['rag']['intent'] ?? 'general',
                'is_private' => (bool) ($res['message']->is_private ?? false),
                'moderated' => (bool) ($res['rag']['moderated'] ?? false),
                'masked_user_message' => $res['rag']['masked_user_message'] ?? null,
                'sources' => $res['rag']['citations'] ?? [],
                'app_actions' => $res['rag']['actions'] ?? [],
                'confidence' => $res['rag']['confidence'] ?? 0.9,
                'suggest_ticket' => $res['rag']['needs_escalation'] ?? false,
                'created_at' => $res['message']->created_at->format('H:i d/m'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

            if (! app()->runningUnitTests()) {
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Tạo Ticket hỗ trợ sau khi người dùng xác nhận; chỉ báo thành công khi backend đã lưu.
     */
    public function submitTicket(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để gửi Ticket hỗ trợ.',
            ], 401);
        }

        // Khóa cứng danh tính người gửi theo phiên đăng nhập: chặn mọi hành vi giả mạo ID/Tên từ request
        if (
            ($request->filled('user_id') && (int) $request->input('user_id') !== (int) $user->user_id)
            || ($request->filled('sender_id') && (int) $request->input('sender_id') !== (int) $user->user_id)
            || ($request->filled('sender_name') && trim((string) $request->input('sender_name')) !== trim((string) $user->full_name))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'ID và tên người gửi được khóa cố định theo tài khoản đang đăng nhập và không thể thay đổi.',
            ], 403);
        }

        $validated = $request->validate([
            'topic' => 'required|string|max:150',
            'description' => 'required|string|max:3000',
            'department' => 'nullable|string|max:100',
            'class_id' => 'nullable|integer|exists:classes,class_id',
        ]);

        $classId = $validated['class_id'] ?? null;

        // Xác thực phạm vi lớp học của sinh viên trước khi gắn vào ticket
        if ($classId && $user->isStudent()) {
            $enrolled = $user->enrollments()->where('class_id', $classId)->exists();
            if (! $enrolled) {
                $classId = null;
            }
        }

        $ticket = $this->supportService->createTicket(
            $user,
            $validated['topic'],
            $validated['description'],
            $validated['department'] ?? 'Phòng Đào tạo',
            $classId ? (int) $classId : null,
        );

        return response()->json([
            'success' => true,
            'ticket_code' => $ticket->ticket_code,
            'sender_id' => $user->user_id,
            'sender_name' => $user->full_name,
            'message' => "Yêu cầu đã được lưu thành công với mã phiếu: {$ticket->ticket_code} (Người gửi: #{$user->user_id} - {$user->full_name}). Quản trị viên đã nhận thông báo và sẽ xử lý hoặc chuyển tiếp giảng viên phụ trách.",
        ]);
    }

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
