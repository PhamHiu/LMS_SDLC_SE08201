<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyRule extends Model
{
    protected $table = 'policy_rules';
    protected $primaryKey = 'policy_id';

    public const CODE_ATTENDANCE_MAX_ABSENCE = 'ATTENDANCE_MAX_ABSENCE';

    public const SCOPE_GLOBAL = 'GLOBAL';
    public const SCOPE_MAJOR = 'MAJOR';
    public const SCOPE_SUBJECT = 'SUBJECT';
    public const SCOPE_CLASS = 'CLASS';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const TYPE_ATTENDANCE_THRESHOLD = 'ATTENDANCE_THRESHOLD';
    public const TYPE_GENERAL_POLICY = 'GENERAL_POLICY';
    public const TYPE_UNSUPPORTED_CALCULATION = 'UNSUPPORTED_CALCULATION';

    public const OP_GT = 'GT';   // >
    public const OP_GTE = 'GTE'; // >=
    public const OP_LT = 'LT';   // <
    public const OP_LTE = 'LTE'; // <=

    protected $fillable = [
        'rule_code',
        'title',
        'source_document',
        'clause_ref',
        'excerpt',
        'scope_type',
        'scope_id',
        'term_code',
        'effective_from',
        'effective_to',
        'status',
        'priority',
        'rule_type',
        'threshold_value',
        'operator',
        'unit',
        'basis',
        'requires_engineering_support',
        'created_by',
        'approved_by',
        'approved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'threshold_value' => 'float',
            'priority' => 'integer',
            'requires_engineering_support' => 'boolean',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PolicyRuleAudit::class, 'policy_id', 'policy_id')->orderByDesc('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function operatorLabel(): string
    {
        return match ($this->operator) {
            self::OP_GT => 'quá (>)',
            self::OP_GTE => 'từ trở lên (>=)',
            self::OP_LT => 'dưới (<)',
            self::OP_LTE => 'từ trở xuống (<=)',
            default => (string) $this->operator,
        };
    }

    public function citationString(): string
    {
        $parts = [$this->source_document];
        if (! empty($this->clause_ref)) {
            $parts[] = $this->clause_ref;
        }
        if ($this->effective_from) {
            $eff = 'hiệu lực từ ' . $this->effective_from->format('d/m/Y');
            if ($this->effective_to) {
                $eff .= ' đến ' . $this->effective_to->format('d/m/Y');
            }
            $parts[] = $eff;
        }

        return implode(' — ', $parts);
    }
}
