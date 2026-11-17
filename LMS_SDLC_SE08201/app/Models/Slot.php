<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Xác định ca học đang diễn ra tại thời điểm hiện tại
     */
    public static function getCurrentSlot(?Carbon $time = null): ?self
    {
        $now = $time ?? now();
        $timeStr = $now->format('H:i:s');
        return self::where('start_time', '<=', $timeStr)
            ->where('end_time', '>=', $timeStr)
            ->first();
    }

    /**
     * Xác định ca học tiếp theo sắp tới
     */
    public static function getUpcomingSlot(?Carbon $time = null): ?self
    {
        $now = $time ?? now();
        $timeStr = $now->format('H:i:s');
        return self::where('start_time', '>', $timeStr)
            ->orderBy('start_time')
            ->first() ?? self::orderBy('start_time')->first();
    }

    /**
     * Xác định ca học đang trong khung giờ mở điểm danh hợp lệ (trước 30p - sau 15p vào ca)
     */
    public static function getAttendanceActiveSlot(?Carbon $time = null): ?self
    {
        $now = $time ?? now();
        $today = $now->format('Y-m-d');
        foreach (self::orderBy('slot_number')->get() as $slot) {
            $start = Carbon::parse($today . ' ' . $slot->start_time);
            $open = $start->copy()->subMinutes(30);
            $close = $start->copy()->addMinutes(15);
            if ($now->between($open, $close)) {
                return $slot;
            }
        }
        return null;
    }
}
