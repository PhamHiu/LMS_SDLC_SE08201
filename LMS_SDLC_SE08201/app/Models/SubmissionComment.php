<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionComment extends Model
{
    protected $table = 'submission_comments';
    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'submission_id',
        'user_id',
        'content',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id', 'submission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
