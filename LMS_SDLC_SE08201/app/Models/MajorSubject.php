<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MajorSubject extends Model
{
    protected $table = 'majors_subjects';
    protected $primaryKey = 'major_subject_id';
    public $timestamps = false;

    protected $fillable = ['major_id', 'subject_id'];

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id', 'major_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }
}
