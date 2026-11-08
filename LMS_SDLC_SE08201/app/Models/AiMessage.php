<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $table = 'ai_messages';
    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'structured_output',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'structured_output' => 'array',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id', 'conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    public function getAttachmentAttribute(): ?array
    {
        return $this->structured_output['attachment'] ?? null;
    }

    public function hasAttachment(): bool
    {
        return !empty($this->attachment);
    }
}

