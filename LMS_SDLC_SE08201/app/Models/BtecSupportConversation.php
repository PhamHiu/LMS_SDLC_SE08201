<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtecSupportConversation extends Model
{
    protected $table = 'btec_support_conversations';
    protected $primaryKey = 'conversation_id';

    protected $fillable = [
        'user_id',
        'session_token',
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BtecSupportMessage::class, 'conversation_id', 'conversation_id');
    }
}

