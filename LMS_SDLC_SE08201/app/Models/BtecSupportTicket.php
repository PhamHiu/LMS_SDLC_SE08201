<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtecSupportTicket extends Model
{
    protected $table = 'btec_support_tickets';
    protected $primaryKey = 'ticket_id';

    protected $fillable = [
        'user_id',
        'ticket_code',
        'topic',
        'description',
        'department',
        'status',
        'resolution_note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

