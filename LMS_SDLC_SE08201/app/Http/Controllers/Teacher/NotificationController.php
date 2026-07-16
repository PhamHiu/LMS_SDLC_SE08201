<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
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
        return view('teacher.notifications', [
            'notifications' => $this->notificationService->paginateForTeacher($request->user()),
            'classes' => $request->user()->teachingClasses()->with('subject')->get(),
        ]);
    }

    public function store(NotificationRequest $request): RedirectResponse
    {
        // Giảng viên chỉ được gửi thông báo cho lớp mình phụ trách
        $ownsClass = $request->user()->teachingClasses()
            ->where('class_id', $request->validated('class_id'))
            ->exists();
        abort_unless($ownsClass, 403, 'Bạn không phụ trách lớp này.');

        $this->notificationService->create($request->user(), $request->validated());

        return back()->with('success', 'Đã gửi thông báo đến lớp.');
    }

    public function destroy(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->user_id, 403);

        $this->notificationService->delete($notification);

        return back()->with('success', 'Đã xóa thông báo.');
    }
}
