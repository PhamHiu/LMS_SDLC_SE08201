<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';
    protected $primaryKey = 'conversation_id';

    public const MODE_EXPLAIN = 'EXPLAIN';
    public const MODE_GUIDANCE = 'GUIDANCE';
    public const MODE_REVIEW = 'REVIEW';
    public const MODE_PRACTICE = 'PRACTICE';

    protected $fillable = [
        'user_id',
        'subject_id',
        'title',
        'mode',
        'model_used',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    public function messages()
    {
        return $this->hasMany(AiMessage::class, 'conversation_id', 'conversation_id')->orderBy('created_at');
    }

    public function modeLabel(): string
    {
        return match ($this->mode) {
            self::MODE_EXPLAIN => '💡 Giải thích kiến thức',
            self::MODE_GUIDANCE => '🧭 Hướng dẫn bài tập',
            self::MODE_REVIEW => '🔍 Kiểm tra bài làm',
            self::MODE_PRACTICE => '✍️ Luyện tập tự học',
            default => 'Hỏi đáp chung',
        };
    }
}

