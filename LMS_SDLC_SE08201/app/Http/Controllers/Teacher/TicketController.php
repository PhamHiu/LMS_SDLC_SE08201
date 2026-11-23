<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\BtecSupportTicket;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): View
    {
        $teacher = $request->user();

        $tickets = BtecSupportTicket::with(['user', 'classRoom.subject', 'forwardedByUser'])
            ->where('assigned_teacher_id', $teacher->user_id)
            ->orderByRaw("CASE status WHEN 'IN_PROGRESS' THEN 0 WHEN 'OPEN' THEN 1 WHEN 'RESOLVED' THEN 2 ELSE 3 END")
            ->orderByDesc('forwarded_at')
            ->paginate(12);

        return view('teacher.tickets.index', compact('tickets'));
    }

    /**
     * Giảng viên báo cáo kết quả xử lý nội bộ về cho Quản trị viên (Admin sẽ là đầu mối phản hồi chính thức cho Sinh viên).
     */
    public function reply(Request $request, BtecSupportTicket $ticket): RedirectResponse
    {
        $teacher = $request->user();
        abort_unless($ticket->assigned_teacher_id === $teacher->user_id, 403);

        $validated = $request->validate([
            'teacher_reply' => 'required|string|max:2000',
        ]);

        $ticket->update([
            'teacher_reply' => $validated['teacher_reply'],
            'teacher_replied_at' => now(),
        ]);

        // Báo lại kết quả xử lý cho Admin đã chuyển tiếp (hoặc toàn bộ Admin)
        $adminIds = $ticket->forwarded_by
            ? [(int) $ticket->forwarded_by]
            : \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->pluck('user_id')->all();

        foreach ($adminIds as $adminId) {
            $this->notificationService->notifyUser(
                author: $teacher,
                recipientUserId: (int) $adminId,
                title: "Giảng viên đã phản hồi Ticket #{$ticket->ticket_code}",
                body: "Giảng viên {$teacher->full_name} báo cáo kết quả Ticket #{$ticket->ticket_code}: {$validated['teacher_reply']}",
                targetUrl: route('admin.tickets.index'),
                classId: $ticket->class_id,
            );
        }

        return back()->with('success', "Đã gửi báo cáo kết quả xử lý Ticket #{$ticket->ticket_code} về cho Quản trị viên.");
    }
}
