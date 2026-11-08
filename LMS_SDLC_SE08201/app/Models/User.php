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
        'major_id', 'role', 'student_code', 'email', 'hashpassword', 'full_name',
        'date_of_birth', 'gender', 'address', 'phone', 'avatar',
    ];

    protected static function booted()
    {
        static::created(function ($user) {
            if (empty($user->student_code)) {
                $year = $user->created_at ? $user->created_at->format('y') : date('y');
                $paddedId = str_pad($user->user_id, 5, '0', STR_PAD_LEFT);
                $user->student_code = 'HL' . $year . $paddedId;
                $user->saveQuietly();
            }
        });
    }

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

    public function riskPredictions()
    {
        return $this->hasMany(RiskPrediction::class, 'user_id', 'user_id')->orderByDesc('analyzed_at');
    }

    public function latestRiskPrediction()
    {
        return $this->hasOne(RiskPrediction::class, 'user_id', 'user_id')->latestOfMany('analyzed_at');
    }

    public function riskInterventions()
    {
        return $this->hasMany(RiskIntervention::class, 'user_id', 'user_id');
    }

    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class, 'user_id', 'user_id')->orderByDesc('updated_at');
    }
}