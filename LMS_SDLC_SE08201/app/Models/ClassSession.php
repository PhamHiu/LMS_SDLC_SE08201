<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model cho bảng `sessions` (buổi học).
 * Lưu ý: SESSION_DRIVER=file nên bảng này không xung đột với session của Laravel.
 */
class ClassSession extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable = ['class_id', 'session_date', 'room', 'shift'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'session_id', 'session_id');
    }
}
