<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = 'enrollments';
    protected $primaryKey = 'enrollment_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'class_id', 'enrolled_at'];

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime'];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }
}
