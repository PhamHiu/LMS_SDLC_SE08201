<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): View
    {
        return view('student.notifications', [
            'notifications' => $this->notificationService->paginateForStudent($request->user()),
        ]);
    }

    /**
     * Bấm vào thông báo: Đánh dấu đã đọc và chuyển hướng đến màn hình đích
     * (Bài tập, gia hạn, nhắc nộp, lớp học, tài liệu...)
     */
    public function read(Request $request, Notification $notification): RedirectResponse
    {
        $user = $request->user();

        // Đánh dấu đã đọc
        $this->notificationService->markAsRead($notification, $user);

        // Chuyển hướng đến đích tương ứng
        $targetUrl = $notification->getTargetUrl($user);

        return redirect()->to($targetUrl);
    }

    /**
     * Đánh dấu toàn bộ thông báo của học sinh là đã đọc
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsReadForStudent($request->user());

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
