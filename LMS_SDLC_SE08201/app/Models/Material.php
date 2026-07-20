<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materials';
    protected $primaryKey = 'material_id';
    public $timestamps = false;

    protected $fillable = ['subject_id', 'user_id', 'title', 'file_path', 'description'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Phần mở rộng file, phục vụ hiển thị icon */
    public function extension(): string
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }

    public function isVideo(): bool
    {
        return in_array($this->extension(), ['mp4', 'webm', 'ogg']);
    }
}
