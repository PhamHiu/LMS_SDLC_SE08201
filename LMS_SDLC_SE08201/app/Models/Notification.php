<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = ['user_id', 'class_id', 'assignment_id', 'title', 'body', 'target_url'];

    /** Thông báo toàn hệ thống (không gắn lớp cụ thể) */
    public function isSystemWide(): bool
    {
        return $this->class_id === null;
    }

    /** Thông báo nhắc hạn bài tập (được sinh tự động) */
    public function isDeadlineReminder(): bool
    {
        return $this->assignment_id !== null;
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id', 'assignment_id');
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class, 'notification_id', 'notification_id');
    }

    /** Kiểm tra xem một người dùng cụ thể đã đọc thông báo này chưa */
    public function isReadBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Tối ưu nếu đã eager load reads
        if ($this->relationLoaded('reads')) {
            return $this->reads->contains('user_id', $user->user_id);
        }

        return $this->reads()->where('user_id', $user->user_id)->exists();
    }

    /** Lấy URL chuyển hướng khi click vào thông báo */
    public function getTargetUrl(?User $user = null): string
    {
        if (!empty($this->target_url)) {
            return $this->target_url;
        }

        if (!empty($this->assignment_id)) {
            if ($user && $user->isTeacher()) {
                return route('teacher.grading.index', $this->assignment_id);
            }
            return route('student.assignments.show', $this->assignment_id);
        }

        if (!empty($this->class_id)) {
            if ($user && $user->isTeacher()) {
                return route('teacher.classes.show', $this->class_id);
            }
            return route('student.classes.show', $this->class_id);
        }

        if ($user && $user->isTeacher()) {
            return route('teacher.notifications.index');
        }

        return route('student.notifications.index');
    }
}
