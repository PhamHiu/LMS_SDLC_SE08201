<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

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

    /**
     * Sinh thông báo nhắc hạn cho các bài tập sắp đến hạn nộp.
     *
     * Chống trùng: mỗi bài tập chỉ tạo DUY NHẤT 1 thông báo nhắc hạn
     * (kiểm tra qua cột `assignment_id`, được ràng buộc unique ở CSDL).
     * Vì vậy có thể chạy lệnh nhiều lần trong ngày mà không gửi lặp.
     *
     * @param  int  $daysAhead  Ngưỡng ngày: chỉ nhắc bài có hạn trong khoảng [hiện tại, +N ngày]
     * @return int  Số thông báo mới được tạo
     */
    public function sendAssignmentDeadlineReminders(int $daysAhead = 3): int
    {
        $now = now();
        $deadline = $now->copy()->addDays($daysAhead);

        // Bài tập còn hạn và sắp đến hạn, chưa có thông báo nhắc hạn nào
        $assignments = Assignment::with('classRoom')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now, $deadline])
            ->whereDoesntHave('notifications')
            ->get();

        $created = 0;
        foreach ($assignments as $assignment) {
            // Bỏ qua bài tập không còn gắn với lớp hợp lệ
            if (! $assignment->classRoom) {
                continue;
            }

            // Chốt chặn thứ 2 (phòng khi chạy song song): kiểm tra tồn tại trước khi tạo
            $exists = Notification::where('assignment_id', $assignment->assignment_id)->exists();
            if ($exists) {
                continue;
            }

            Notification::create([
                'user_id' => $assignment->classRoom->user_id, // giảng viên đứng lớp là người đăng
                'class_id' => $assignment->class_id,
                'assignment_id' => $assignment->assignment_id,
                'title' => 'Nhắc hạn nộp: '.$assignment->title,
                'body' => sprintf(
                    'Bài tập "%s" (%s) sẽ hết hạn nộp vào %s. Các em hoàn thành và nộp đúng hạn nhé.',
                    $assignment->title,
                    $assignment->typeLabel(),
                    $assignment->due_date->format('d/m/Y H:i')
                ),
            ]);
            $created++;
        }

        if ($created > 0) {
            Log::info("NotificationService: đã tạo {$created} thông báo nhắc hạn bài tập.");
        }

        return $created;
    }
}
