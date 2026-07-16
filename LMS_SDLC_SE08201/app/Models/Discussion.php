<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    protected $table = 'discussions';
    protected $primaryKey = 'discussion_id';

    public const UPDATED_AT = null; // Bảng chỉ có created_at

    protected $fillable = ['user_id', 'parent_id', 'assignment_id', 'comment'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id', 'assignment_id');
    }

    public function parent()
    {
        return $this->belongsTo(Discussion::class, 'parent_id', 'discussion_id');
    }

    public function replies()
    {
        return $this->hasMany(Discussion::class, 'parent_id', 'discussion_id')
            ->orderBy('created_at');
    }
}
