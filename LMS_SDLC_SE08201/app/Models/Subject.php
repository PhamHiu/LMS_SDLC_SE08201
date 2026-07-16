<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subjects';
    protected $primaryKey = 'subject_id';
    public $timestamps = false;

    protected $fillable = ['subject_code', 'subject_name', 'description'];

    public function majors()
    {
        return $this->belongsToMany(Major::class, 'majors_subjects', 'subject_id', 'major_id');
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class, 'subject_id', 'subject_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'subject_id', 'subject_id');
    }
}
