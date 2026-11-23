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

    /**
     * Gửi thông báo riêng đích danh 1 người dùng (Admin / Giảng viên / Sinh viên).
     */
    public function notifyUser(
        User $author,
        int $recipientUserId,
        string $title,
        string $body,
        ?string $targetUrl = null,
        ?int $classId = null,
    ): Notification {
        return Notification::create([
            'user_id' => $author->user_id,
            'recipient_user_id' => $recipientUserId,
            'class_id' => $classId,
            'title' => $title,
            'body' => $body,
            'target_url' => $targetUrl,
        ]);
    }

    public function delete(Notification $notification): void
    {
        $notification->delete();
    }

    /** Thông báo do Admin quản lý (toàn hệ thống + thông báo riêng gửi cho Admin) */
    public function paginateSystem(): LengthAwarePaginator
    {
        $adminId = auth()->id();

        return Notification::with('author')
            ->where(function ($q) use ($adminId) {
                $q->where(fn ($sub) => $sub->whereNull('class_id')->whereNull('recipient_user_id'));
                if ($adminId) {
                    $q->orWhere('recipient_user_id', $adminId);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /** Thông báo các lớp giảng viên phụ trách + thông báo riêng gửi tới giảng viên */
    public function paginateForTeacher(User $teacher): LengthAwarePaginator
    {
        $classIds = $teacher->teachingClasses()->pluck('class_id');

        return Notification::with(['author', 'classRoom'])
            ->where(function ($q) use ($classIds, $teacher) {
                $q->where('recipient_user_id', $teacher->user_id)
                    ->orWhere(fn ($sub) => $sub->whereNull('recipient_user_id')->whereIn('class_id', $classIds));
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /** Thông báo sinh viên nhìn thấy: toàn hệ thống + lớp đã ghi danh + thông báo riêng */
    public function paginateForStudent(User $student): LengthAwarePaginator
    {
        $classIds = $student->enrollments()->pluck('class_id');

        return Notification::with(['author', 'classRoom', 'assignment', 'reads' => fn ($q) => $q->where('user_id', $student->user_id)])
            ->where(function ($q) use ($classIds, $student) {
                $q->where('recipient_user_id', $student->user_id)
                    ->orWhere(fn ($sub) => $sub->whereNull('recipient_user_id')
                        ->where(fn ($c) => $c->whereNull('class_id')->orWhereIn('class_id', $classIds)));
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /** Các thông báo mới nhất hiển thị trên dashboard */
    public function latestFor(User $user, int $limit = 5)
    {
        if ($user->isStudent()) {
            $classIds = $user->enrollments()->pluck('class_id');

            return Notification::with(['author', 'classRoom', 'assignment', 'reads' => fn ($q) => $q->where('user_id', $user->user_id)])
                ->where(function ($q) use ($classIds, $user) {
                    $q->where('recipient_user_id', $user->user_id)
                        ->orWhere(fn ($sub) => $sub->whereNull('recipient_user_id')
                            ->where(fn ($c) => $c->whereNull('class_id')->orWhereIn('class_id', $classIds)));
                })
                ->orderByDesc('created_at')->take($limit)->get();
        }

        if ($user->isTeacher()) {
            $classIds = $user->teachingClasses()->pluck('class_id');

            return Notification::with(['author', 'classRoom', 'assignment'])
                ->where(function ($q) use ($classIds, $user) {
                    $q->where('recipient_user_id', $user->user_id)
                        ->orWhere(fn ($sub) => $sub->whereNull('recipient_user_id')
                            ->where(fn ($c) => $c->whereNull('class_id')->orWhereIn('class_id', $classIds)));
                })
                ->orderByDesc('created_at')->take($limit)->get();
        }

        return Notification::with('author')
            ->where(function ($q) use ($user) {
                $q->whereNull('recipient_user_id')
                    ->orWhere('recipient_user_id', $user->user_id);
            })
            ->orderByDesc('created_at')->take($limit)->get();
    }

    /** Đánh dấu một thông báo là đã đọc cho người dùng */
    public function markAsRead(Notification $notification, User $user): void
    {
        \App\Models\NotificationRead::firstOrCreate([
            'notification_id' => $notification->notification_id,
            'user_id' => $user->user_id,
        ], [
            'read_at' => now(),
        ]);
    }

    /** Đánh dấu toàn bộ thông báo của học sinh là đã đọc */
    public function markAllAsReadForStudent(User $student): void
    {
        $classIds = $student->enrollments()->pluck('class_id');
        $notifications = Notification::where(function ($q) use ($classIds, $student) {
            $q->where('recipient_user_id', $student->user_id)
                ->orWhere(fn ($sub) => $sub->whereNull('recipient_user_id')
                    ->where(fn ($c) => $c->whereNull('class_id')->orWhereIn('class_id', $classIds)));
        })->get();

        foreach ($notifications as $n) {
            $this->markAsRead($n, $student);
        }
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
