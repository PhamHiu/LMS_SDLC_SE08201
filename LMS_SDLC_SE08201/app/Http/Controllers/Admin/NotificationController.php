<?php

namespace App\Http\Controllers\Admin;

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

    public function index(): View
    {
        return view('admin.notifications', [
            'notifications' => $this->notificationService->paginateSystem(),
        ]);
    }

    public function store(NotificationRequest $request): RedirectResponse
    {
        // Admin gửi thông báo toàn hệ thống → class_id luôn null
        $this->notificationService->create($request->user(), [
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'class_id' => null,
        ]);

        return back()->with('success', 'Đã gửi thông báo toàn hệ thống.');
    }

    public function destroy(Request $request, Notification $notification): RedirectResponse
    {
        $this->notificationService->delete($notification);

        return back()->with('success', 'Đã xóa thông báo.');
    }
}
