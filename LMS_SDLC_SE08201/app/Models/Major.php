<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    protected $table = 'majors';
    protected $primaryKey = 'major_id';
    public $timestamps = false;

    protected $fillable = ['major_name', 'major_code', 'description'];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'majors_subjects', 'major_id', 'subject_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'major_id', 'major_id');
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class, 'major_id', 'major_id');
    }
}
