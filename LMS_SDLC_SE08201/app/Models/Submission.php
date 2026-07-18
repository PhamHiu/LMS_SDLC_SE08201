<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'submissions';
    protected $primaryKey = 'submission_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'assignment_id', 'submit_file_path', 'student_submit', 'other_file_path',
        'feedback', 'submitted_at', 'graded_at', 'score',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'float',
        ];
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id', 'assignment_id');
    }

    public function details()
    {
        return $this->hasMany(SubmissionDetail::class, 'submission_id', 'submission_id');
    }
}
