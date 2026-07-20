<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;

    public const PRESENT = 'P';
    public const ABSENT = 'A';
    public const LATE = 'L';

    protected $fillable = ['user_id', 'session_id', 'attendance_time', 'status', 'note'];

    protected function casts(): array
    {
        return ['attendance_time' => 'datetime'];
    }

    public function statusLabel(): string
    {
        return [
            self::PRESENT => 'Có mặt',
            self::ABSENT => 'Vắng',
            self::LATE => 'Đi muộn',
        ][$this->status] ?? $this->status;
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function session()
    {
        return $this->belongsTo(ClassSession::class, 'session_id', 'session_id');
    }
}
