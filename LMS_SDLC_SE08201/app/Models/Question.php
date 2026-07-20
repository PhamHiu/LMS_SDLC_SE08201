<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'questions';
    protected $primaryKey = 'question_id';
    public $timestamps = false;

    protected $fillable = [
        'assignment_id', 'content',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id', 'assignment_id');
    }

    /** Trả về mảng đáp án [A => ..., B => ...] để render */
    public function options(): array
    {
        return [
            'A' => $this->option_a,
            'B' => $this->option_b,
            'C' => $this->option_c,
            'D' => $this->option_d,
        ];
    }
}
