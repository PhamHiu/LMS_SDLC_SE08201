<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtecSupportMessage extends Model
{
    protected $table = 'btec_support_messages';
    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'role',
        'message',
        'intent',
        'citations',
        'actions',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'citations' => 'array',
            'actions' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BtecSupportConversation::class, 'conversation_id', 'conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}

