<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtecSupportTicket extends Model
{
    protected $table = 'btec_support_tickets';
    protected $primaryKey = 'ticket_id';

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'user_id',
        'class_id',
        'assigned_teacher_id',
        'forwarded_by',
        'forwarded_at',
        'ticket_code',
        'topic',
        'description',
        'department',
        'status',
        'resolution_note',
        'admin_note',
        'teacher_reply',
        'teacher_replied_at',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'forwarded_at' => 'datetime',
            'teacher_replied_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function isForwardedToTeacher(): bool
    {
        return $this->assigned_teacher_id !== null;
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_IN_PROGRESS && $this->assigned_teacher_id) {
            return 'Đã chuyển Giảng viên';
        }

        return match ($this->status) {
            self::STATUS_OPEN => 'Chờ tiếp nhận',
            self::STATUS_IN_PROGRESS => 'Đang xử lý',
            self::STATUS_RESOLVED => 'Đã giải quyết',
            self::STATUS_CLOSED => 'Đã đóng',
            default => $this->status,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id', 'user_id');
    }

    public function forwardedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by', 'user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'user_id');
    }
}
