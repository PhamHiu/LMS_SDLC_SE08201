<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $table = 'slots';
    protected $primaryKey = 'slot_id';
    public $timestamps = false;

    protected $fillable = ['slot_number', 'start_time', 'end_time', 'label'];

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'slot_id', 'slot_id');
    }
}
