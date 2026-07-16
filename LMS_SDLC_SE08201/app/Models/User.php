<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_TEACHER = 'TEACHER';
    public const ROLE_STUDENT = 'STUDENT';

    protected $fillable = [
        'major_id', 'role', 'email', 'hashpassword', 'full_name',
        'date_of_birth', 'gender', 'address', 'phone', 'avatar',
    ];

    protected $hidden = ['hashpassword', 'remember_token'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /** Laravel Auth sử dụng cột hashpassword thay cho password mặc định. */
    public function getAuthPassword(): string
    {
        return $this->hashpassword;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function roleLabel(): string
    {
        return [
            self::ROLE_ADMIN => 'Quản trị viên',
            self::ROLE_TEACHER => 'Giảng viên',
            self::ROLE_STUDENT => 'Sinh viên',
        ][$this->role] ?? $this->role;
    }

    // ==== Quan hệ ====

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id', 'major_id');
    }

    /** Các lớp giảng viên phụ trách */
    public function teachingClasses()
    {
        return $this->hasMany(ClassRoom::class, 'user_id', 'user_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_id', 'user_id');
    }

    /** Các lớp sinh viên đã ghi danh */
    public function enrolledClasses()
    {
        return $this->belongsToMany(ClassRoom::class, 'enrollments', 'user_id', 'class_id')
            ->withPivot('enrolled_at');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'user_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'user_id', 'user_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'user_id', 'user_id');
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class, 'user_id', 'user_id');
    }

    public function postedNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }
}
