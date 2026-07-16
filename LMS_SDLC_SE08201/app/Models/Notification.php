<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = ['user_id', 'class_id', 'title', 'body'];

    /** Thông báo toàn hệ thống (không gắn lớp cụ thể) */
    public function isSystemWide(): bool
    {
        return $this->class_id === null;
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }
}
