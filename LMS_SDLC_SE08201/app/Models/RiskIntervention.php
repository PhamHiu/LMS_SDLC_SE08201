<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskIntervention extends Model
{
    protected $table = 'risk_interventions';
    protected $primaryKey = 'intervention_id';

    protected $fillable = [
        'user_id',
        'teacher_id',
        'prediction_id',
        'action_type',
        'notes',
        'status',
        'outcome_evaluation',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id', 'user_id');
    }

    public function prediction()
    {
        return $this->belongsTo(RiskPrediction::class, 'prediction_id', 'prediction_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action_type) {
            'ACADEMIC_TUTORING' => 'Phụ đạo học tập',
            'ATTENDANCE_REMINDER' => 'Nhắc nhở chuyên cần',
            'COUNSELING' => 'Tư vấn tâm lý / định hướng',
            default => 'Hỗ trợ khác',
        };
    }
}

