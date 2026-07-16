<?php

namespace App\Services\Admin;

use App\Models\ClassRoom;
use App\Models\Major;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;

class DashboardService
{
    /**
     * Số liệu thống kê tổng quan cho Dashboard Admin.
     */
    public function getStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_students' => User::where('role', User::ROLE_STUDENT)->count(),
            'total_teachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'total_majors' => Major::count(),
            'total_subjects' => Subject::count(),
            'total_classes' => ClassRoom::count(),
            'active_classes' => ClassRoom::where('status', ClassRoom::STATUS_ACTIVE)->count(),
            'recent_users' => User::orderByDesc('created_at')->take(5)->get(),
            'recent_classes' => ClassRoom::with(['teacher', 'subject'])
                ->orderByDesc('class_id')->take(5)->get(),
            'recent_notifications' => Notification::with('author')
                ->orderByDesc('created_at')->take(5)->get(),
        ];
    }
}
