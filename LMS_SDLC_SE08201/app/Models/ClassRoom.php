<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model cho bảng `classes` (đặt tên ClassRoom vì `class` là từ khóa PHP).
 */
class ClassRoom extends Model
{
    protected $table = 'classes';
    protected $primaryKey = 'class_id';
    public $timestamps = false;

    public const STATUS_PENDING = 0; // Chưa mở
    public const STATUS_ACTIVE = 1;  // Đang học
    public const STATUS_CLOSED = 2;  // Đã đóng

    protected $fillable = [
        'user_id', 'subject_id', 'major_id', 'class_name',
        'start_date', 'end_date', 'total_sessions', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_PENDING => 'Chưa mở',
            self::STATUS_ACTIVE => 'Đang học',
            self::STATUS_CLOSED => 'Đã đóng',
        ][$this->status] ?? 'Không rõ';
    }

    // ==== Quan hệ ====

    /** Giảng viên đứng lớp */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id', 'major_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id', 'class_id');
    }

    /** Danh sách sinh viên trong lớp */
    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'class_id', 'user_id')
            ->withPivot('enrolled_at');
    }

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_id', 'class_id')->orderBy('session_date');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id', 'class_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'class_id', 'class_id');
    }
}
