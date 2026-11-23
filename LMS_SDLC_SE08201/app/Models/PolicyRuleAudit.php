<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyRuleAudit extends Model
{
    protected $table = 'policy_rule_audits';
    protected $primaryKey = 'audit_id';
    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'actor_user_id',
        'action',
        'changes_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PolicyRule::class, 'policy_id', 'policy_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }
}
