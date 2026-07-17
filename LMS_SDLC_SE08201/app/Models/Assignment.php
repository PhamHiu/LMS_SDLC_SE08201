<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'assignments';
    protected $primaryKey = 'assignment_id';

    public const UPDATED_AT = null; // Bảng chỉ có created_at

    public const TYPE_ESSAY = 'essay';
    public const TYPE_QUIZ = 'quiz';

    protected $fillable = ['class_id', 'title', 'type', 'description', 'due_date', 'max_score'];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'created_at' => 'datetime',
            'max_score' => 'float',
        ];
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_QUIZ ? 'Trắc nghiệm' : 'Tự luận';
    }

    public function isQuiz(): bool
    {
        return $this->type === self::TYPE_QUIZ;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast();
    }

    // ==== Quan hệ ====

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'assignment_id', 'assignment_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'assignment_id', 'assignment_id');
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class, 'assignment_id', 'assignment_id');
    }

    /** Bình luận gốc (không phải reply) */
    public function rootDiscussions()
    {
        return $this->hasMany(Discussion::class, 'assignment_id', 'assignment_id')
            ->whereNull('parent_id')
            ->orderByDesc('created_at');
    }
}
