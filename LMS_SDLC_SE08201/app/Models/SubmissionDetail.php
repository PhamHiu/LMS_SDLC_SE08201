<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionDetail extends Model
{
    protected $table = 'submission_details';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;

    protected $fillable = ['submission_id', 'question_id', 'selected_option'];

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id', 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }

    public function isCorrect(): bool
    {
        return $this->question && $this->selected_option === $this->question->correct_answer;
    }
}
