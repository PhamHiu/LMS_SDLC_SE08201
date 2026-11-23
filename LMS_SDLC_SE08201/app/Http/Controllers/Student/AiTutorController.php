<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Subject;
use App\Services\AI\AiTutorService;
use App\Services\Student\StudentAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiTutorController extends Controller
{
    public function __construct(
        private readonly AiTutorService $aiTutorService,
        private readonly StudentAccessService $accessService,
    ) {
    }

    /**
     * Màn hình chính của AI Learning Tutor: hiển thị danh sách hội thoại và khung chat.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $conversations = $user->aiConversations()->get();
        $activeConversation = $conversations->first();

        $academicProfile = $this->aiTutorService->resolveStudentAcademicProfile($user);

        return view('student.ai_tutor.index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation ? $activeConversation->load('messages') : null,
            'academicProfile' => $academicProfile,
        ]);
    }

    /**
     * Tạo ngay một cuộc trò chuyện mới với AI (không cần chọn môn).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'mode' => 'nullable|in:EXPLAIN,GUIDANCE,REVIEW,PRACTICE',
            'initial_message' => 'nullable|string|max:1000',
        ]);

        $conversation = AiConversation::create([
            'user_id' => $request->user()->user_id,
            'subject_id' => null,
            'mode' => $request->input('mode', AiConversation::MODE_EXPLAIN),
            'title' => 'Cuộc trò chuyện mới',
        ]);

        if ($request->filled('initial_message')) {
            $this->aiTutorService->sendMessage($conversation, $request->initial_message);
        }

        return redirect()->route('student.ai-tutor.show', $conversation);
    }

    /**
     * Mở một cuộc trò chuyện cụ thể.
     */
    public function show(Request $request, AiConversation $conversation): View
    {
        // Bảo mật: Học sinh chỉ xem được cuộc trò chuyện của chính mình
        abort_if($conversation->user_id !== $request->user()->user_id, 403, 'Bạn không có quyền truy cập cuộc trò chuyện này.');

        $conversations = $request->user()->aiConversations()->get();
        $conversation->load('messages');
        $academicProfile = $this->aiTutorService->resolveStudentAcademicProfile($request->user());

        return view('student.ai_tutor.index', [
            'conversations' => $conversations,
            'activeConversation' => $conversation,
            'academicProfile' => $academicProfile,
        ]);
    }

    /**
     * Gửi tin nhắn qua AJAX/Fetch và nhận câu trả lời tức thời từ MR. BIẾT TUỐT.
     * Cho phép học sinh gửi kèm tệp hoặc hình ảnh (giới hạn tối đa 5MB).
     */
    public function sendMessage(Request $request, AiConversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->user_id, 403);

        $request->validate([
            'message' => 'nullable|string|max:4000',
            'file' => 'nullable|file|max:5120', // Giới hạn tối đa 5MB
            'mode' => 'nullable|in:EXPLAIN,GUIDANCE,REVIEW,PRACTICE',
        ], [
            'file.max' => 'Dung lượng tệp đính kèm không được vượt quá 5MB.',
        ]);

        if ($request->filled('mode') && $request->input('mode') !== $conversation->mode) {
            $conversation->update(['mode' => $request->input('mode')]);
        }

        $messageText = trim((string) $request->input('message', ''));
        $hasFile = $request->hasFile('file');

        if (empty($messageText) && !$hasFile) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung câu hỏi hoặc tải lên tệp/hình ảnh (tối đa 5MB).'
            ], 422);
        }

        $attachmentData = null;
        if ($hasFile) {
            $file = $request->file('file');
            $path = $file->store('ai_tutor_attachments', 'public');
            $originalName = $file->getClientOriginalName();
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $size = $file->getSize();

            $attachmentData = [
                'path' => $path,
                'name' => $originalName,
                'mime' => $mime,
                'size' => $size,
                'size_formatted' => ($size >= 1048576) ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 1) . ' KB',
                'url' => asset('storage/' . $path),
                'is_image' => str_starts_with($mime, 'image/'),
            ];
        }

        $assistantMessage = $this->aiTutorService->sendMessage($conversation, $messageText, $attachmentData);

        return response()->json([
            'success' => true,
            'data' => [
                'message_id' => $assistantMessage->message_id,
                'role' => 'assistant',
                'content' => $assistantMessage->content,
                'masked_user_message' => $assistantMessage->getAttribute('masked_user_message'),
                'pending_image_prompt' => $assistantMessage->getAttribute('pending_image_prompt'),
                'created_at' => $assistantMessage->created_at->format('H:i d/m/Y'),
                'attachment' => $attachmentData,
            ]
        ]);
    }

    /**
     * Xóa cuộc trò chuyện.
     */
    
    /**
     * PHƯƠNG PHÁP 4: Server-Sent Events (SSE) Streaming tin nhắn AI từ MR. BIẾT TUỐT.
     */
    public function stream(Request $request, AiConversation $conversation): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_if($conversation->user_id !== $request->user()->user_id, 403);

        $requestedMode = (string) $request->input('mode', '');
        if (in_array($requestedMode, ['EXPLAIN', 'GUIDANCE', 'REVIEW', 'PRACTICE'], true) && $requestedMode !== $conversation->mode) {
            $conversation->update(['mode' => $requestedMode]);
        }

        $messageText = trim((string) $request->input('message', ''));
        $attachmentData = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('ai_tutor_attachments', 'public');
            $originalName = $file->getClientOriginalName();
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $size = $file->getSize();

            $attachmentData = [
                'path' => $path,
                'name' => $originalName,
                'mime' => $mime,
                'size' => $size,
                'size_formatted' => ($size >= 1048576) ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 1) . ' KB',
                'url' => asset('storage/' . $path),
                'is_image' => str_starts_with($mime, 'image/'),
            ];
        }

        return response()->stream(function () use ($conversation, $messageText, $attachmentData) {
            @set_time_limit(120);
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $assistantMessage = $this->aiTutorService->streamMessage(
                $conversation,
                $messageText,
                $attachmentData,
                function ($chunk) {
                    echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            );

            echo "data: " . json_encode([
                'done' => true,
                'message_id' => $assistantMessage->message_id,
                'content' => $assistantMessage->content,
                'masked_user_message' => $assistantMessage->getAttribute('masked_user_message'),
                'pending_image_prompt' => $assistantMessage->getAttribute('pending_image_prompt'),
                'created_at' => $assistantMessage->created_at->format('H:i d/m/Y'),
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

    /**
     * Endpoint vẽ ảnh minh họa AI bất đồng bộ (Async) sau khi text đã hiển thị xong.
     */
    public function generateImage(Request $request, AiMessage $message): JsonResponse
    {
        $conversation = $message->conversation;
        abort_if(! $conversation || $conversation->user_id !== $request->user()->user_id, 403);

        // Giải phóng session lock trước khi gọi API tạo ảnh để không chặn các request chat tiếp theo
        if ($request->hasSession()) {
            $request->session()->save();
        }

        $imageUrl = $this->aiTutorService->generateDeferredImageForMessage($message);

        return response()->json([
            'success' => $imageUrl !== null,
            'image_url' => $imageUrl,
        ]);
    }

    public function destroy(Request $request, AiConversation $conversation): RedirectResponse
    {
        abort_if($conversation->user_id !== $request->user()->user_id, 403);

        $conversation->delete();

        return redirect()->route('student.ai-tutor.index')->with('success', 'Đã xóa cuộc trò chuyện.');
    }
}

