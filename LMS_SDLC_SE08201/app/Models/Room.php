<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    public $timestamps = false;

    protected $fillable = ['room_name', 'capacity', 'location'];

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'room_id', 'room_id');
    }
}
