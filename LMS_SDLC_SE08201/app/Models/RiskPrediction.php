<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskPrediction extends Model
{
    protected $table = 'risk_predictions';
    protected $primaryKey = 'prediction_id';

    public const LEVEL_SAFE = 'SAFE';
    public const LEVEL_MEDIUM = 'MEDIUM';
    public const LEVEL_HIGH = 'HIGH';
    public const LEVEL_VERY_HIGH = 'VERY_HIGH';

    protected $fillable = [
        'user_id',
        'risk_level',
        'risk_score',
        'analysis_method',
        'model_version',
        'summary_explanation',
        'recommendations',
        'data_quality_status',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'float',
            'analyzed_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function user()
    {
        return $this->student();
    }

    public function factors()
    {
        return $this->hasMany(RiskFactor::class, 'prediction_id', 'prediction_id');
    }

    public function interventions()
    {
        return $this->hasMany(RiskIntervention::class, 'prediction_id', 'prediction_id');
    }

    public function levelLabel(): string
    {
        return match ($this->risk_level) {
            self::LEVEL_SAFE => 'An toàn',
            self::LEVEL_MEDIUM => 'Trung bình',
            self::LEVEL_HIGH => 'Nguy cơ cao',
            self::LEVEL_VERY_HIGH => 'Nguy cơ rất cao',
            default => 'Chưa xác định',
        };
    }

    public function badgeColorClass(): string
    {
        return match ($this->risk_level) {
            self::LEVEL_SAFE => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::LEVEL_MEDIUM => 'bg-amber-100 text-amber-800 border-amber-300',
            self::LEVEL_HIGH => 'bg-orange-100 text-orange-800 border-orange-300',
            self::LEVEL_VERY_HIGH => 'bg-rose-100 text-rose-800 border-rose-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}

