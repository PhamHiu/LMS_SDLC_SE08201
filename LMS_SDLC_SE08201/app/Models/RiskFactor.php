<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskFactor extends Model
{
    protected $table = 'risk_factors';
    protected $primaryKey = 'factor_id';

    public const TYPE_ATTENDANCE = 'ATTENDANCE';
    public const TYPE_GRADE = 'GRADE';
    public const TYPE_RETAKE = 'RETAKE';
    public const TYPE_TREND = 'TREND';

    protected $fillable = [
        'prediction_id',
        'subject_id',
        'factor_type',
        'title',
        'description',
        'severity',
        'impact_weight',
    ];

    protected function casts(): array
    {
        return [
            'impact_weight' => 'float',
        ];
    }

    public function prediction()
    {
        return $this->belongsTo(RiskPrediction::class, 'prediction_id', 'prediction_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }
}

