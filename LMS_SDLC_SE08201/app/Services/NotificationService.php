<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * Tạo thông báo: class_id = null → toàn hệ thống, ngược lại → nội bộ lớp.
     */
    public function create(User $author, array $data): Notification
    {
        return Notification::create([
            'user_id' => $author->user_id,
            'class_id' => $data['class_id'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
    }

    public function delete(Notification $notification): void
    {
        $notification->delete();
    }

    /** Thông báo do Admin quản lý (toàn hệ thống) */
    public function paginateSystem(): LengthAwarePaginator
    {
        return Notification::with('author')
            ->whereNull('class_id')
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /** Thông báo các lớp giảng viên phụ trách */
    public function paginateForTeacher(User $teacher): LengthAwarePaginator
    {
        $classIds = $teacher->teachingClasses()->pluck('class_id');

        return Notification::with(['author', 'classRoom'])
            ->whereIn('class_id', $classIds)
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /** Thông báo sinh viên nhìn thấy: toàn hệ thống + lớp đã ghi danh */
    public function paginateForStudent(User $student): LengthAwarePaginator
    {
        $classIds = $student->enrollments()->pluck('class_id');

        return Notification::with(['author', 'classRoom'])
            ->where(fn ($q) => $q->whereNull('class_id')->orWhereIn('class_id', $classIds))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /** Các thông báo mới nhất hiển thị trên dashboard */
    public function latestFor(User $user, int $limit = 5)
    {
        if ($user->isStudent()) {
            $classIds = $user->enrollments()->pluck('class_id');

            return Notification::with(['author', 'classRoom'])
                ->where(fn ($q) => $q->whereNull('class_id')->orWhereIn('class_id', $classIds))
                ->orderByDesc('created_at')->take($limit)->get();
        }

        if ($user->isTeacher()) {
            $classIds = $user->teachingClasses()->pluck('class_id');

            return Notification::with(['author', 'classRoom'])
                ->where(fn ($q) => $q->whereNull('class_id')->orWhereIn('class_id', $classIds))
                ->orderByDesc('created_at')->take($limit)->get();
        }

        return Notification::with('author')->orderByDesc('created_at')->take($limit)->get();
    }
}
