<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BtecSupportTicket;
use App\Models\ClassRoom;
use App\Models\User;
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
        $statusFilter = $request->query('status');
        $query = BtecSupportTicket::with(['user', 'classRoom.subject', 'assignedTeacher', 'forwardedByUser', 'resolvedByUser'])
            ->orderByRaw("CASE status WHEN 'OPEN' THEN 0 WHEN 'IN_PROGRESS' THEN 1 WHEN 'RESOLVED' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at');

        if ($statusFilter && in_array($statusFilter, [
            BtecSupportTicket::STATUS_OPEN,
            BtecSupportTicket::STATUS_IN_PROGRESS,
            BtecSupportTicket::STATUS_RESOLVED,
            BtecSupportTicket::STATUS_CLOSED,
        ], true)) {
            $query->where('status', $statusFilter);
        }

        $tickets = $query->paginate(12)->withQueryString();
        $teachers = User::where('role', User::ROLE_TEACHER)->orderBy('full_name')->get();
        $classes = ClassRoom::with(['subject', 'teacher'])->orderBy('class_name')->get();

        $counts = [
            'all' => BtecSupportTicket::count(),
            'OPEN' => BtecSupportTicket::where('status', BtecSupportTicket::STATUS_OPEN)->count(),
            'IN_PROGRESS' => BtecSupportTicket::where('status', BtecSupportTicket::STATUS_IN_PROGRESS)->count(),
            'RESOLVED' => BtecSupportTicket::where('status', BtecSupportTicket::STATUS_RESOLVED)->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'teachers', 'classes', 'statusFilter', 'counts'));
    }

    /**
     * Admin chuyển tiếp Ticket cho Giảng viên phụ trách và gửi thông báo cho Giảng viên + Sinh viên.
     */
    public function forward(Request $request, BtecSupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_teacher_id' => 'required|integer|exists:users,user_id',
            'class_id' => 'nullable|integer|exists:classes,class_id',
            'admin_note' => 'nullable|string|max:1500',
        ]);

        $teacher = User::where('user_id', $validated['assigned_teacher_id'])
            ->where('role', User::ROLE_TEACHER)
            ->firstOrFail();

        $admin = $request->user();
        $classId = $validated['class_id'] ?? $ticket->class_id;
        $adminNote = trim((string) ($validated['admin_note'] ?? ''));

        $ticket->update([
            'class_id' => $classId,
            'assigned_teacher_id' => $teacher->user_id,
            'forwarded_by' => $admin->user_id,
            'forwarded_at' => now(),
            'admin_note' => $adminNote !== '' ? $adminNote : $ticket->admin_note,
            'status' => BtecSupportTicket::STATUS_IN_PROGRESS,
        ]);

        $ticket->loadMissing(['user', 'classRoom']);
        $studentName = $ticket->user?->full_name ?? 'Sinh viên';
        $classLabel = $ticket->classRoom ? " ({$ticket->classRoom->class_name})" : '';

        // 1. Gửi thông báo riêng cho Giảng viên phụ trách
        $this->notificationService->notifyUser(
            author: $admin,
            recipientUserId: $teacher->user_id,
            title: "Admin chuyển tiếp Ticket #{$ticket->ticket_code}: {$ticket->topic}",
            body: "Sinh viên {$studentName}{$classLabel} gửi yêu cầu: \"{$ticket->description}\"" . ($adminNote !== '' ? " | Ghi chú từ Admin: {$adminNote}" : ''),
            targetUrl: route('teacher.tickets.index'),
            classId: $classId,
        );

        // 2. Gửi thông báo cập nhật trạng thái cho Sinh viên
        $this->notificationService->notifyUser(
            author: $admin,
            recipientUserId: $ticket->user_id,
            title: "Ticket #{$ticket->ticket_code} đã chuyển Giảng viên phụ trách",
            body: "Yêu cầu \"{$ticket->topic}\" của bạn đã được Quản trị viên chuyển tới Giảng viên {$teacher->full_name} để kiểm tra và xử lý.",
            targetUrl: route('student.tickets.index'),
            classId: $classId,
        );

        return back()->with('success', "Đã chuyển tiếp Ticket #{$ticket->ticket_code} tới Giảng viên {$teacher->full_name} và gửi thông báo.");
    }

    /**
     * Admin trực tiếp phản hồi / đóng Ticket và gửi thông báo về cho Sinh viên.
     */
    public function reply(Request $request, BtecSupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_note' => 'required|string|max:2000',
            'status' => 'required|in:IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        $admin = $request->user();
        $isDone = in_array($validated['status'], [BtecSupportTicket::STATUS_RESOLVED, BtecSupportTicket::STATUS_CLOSED], true);

        $ticket->update([
            'resolution_note' => $validated['resolution_note'],
            'status' => $validated['status'],
            'resolved_by' => $isDone ? $admin->user_id : $ticket->resolved_by,
            'resolved_at' => $isDone ? now() : $ticket->resolved_at,
        ]);

        $this->notificationService->notifyUser(
            author: $admin,
            recipientUserId: $ticket->user_id,
            title: "Phản hồi Ticket #{$ticket->ticket_code}: {$ticket->statusLabel()}",
            body: "Quản trị viên phản hồi yêu cầu \"{$ticket->topic}\": {$validated['resolution_note']}",
            targetUrl: route('student.tickets.index'),
            classId: $ticket->class_id,
        );

        return back()->with('success', "Đã cập nhật phản hồi cho Ticket #{$ticket->ticket_code} và gửi thông báo cho sinh viên.");
    }
}
