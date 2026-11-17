<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Model cho bảng `sessions` (buổi học).
 * Lưu ý: SESSION_DRIVER=file nên bảng này không xung đột với session của Laravel.
 */
class ClassSession extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable = ['class_id', 'session_date', 'slot_id', 'room_id'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }

    public function slot()
    {
        return $this->belongsTo(Slot::class, 'slot_id', 'slot_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'session_id', 'session_id');
    }

    /**
     * Thời điểm bắt đầu ca học (session_date + slot->start_time)
     */
    public function getStartDateTime(): ?Carbon
    {
        if (! $this->session_date) {
            return null;
        }
        $slot = $this->slot ?? Slot::find($this->slot_id ?? 1) ?? Slot::orderBy('slot_number')->first();
        if (! $slot?->start_time) {
            return null;
        }
        $time = substr($slot->start_time, 0, 5);
        return Carbon::parse($this->session_date->format('Y-m-d') . ' ' . $time);
    }

    /**
     * Thời điểm kết thúc ca học (session_date + slot->end_time)
     */
    public function getEndDateTime(): ?Carbon
    {
        if (! $this->session_date) {
            return null;
        }
        $slot = $this->slot ?? Slot::find($this->slot_id ?? 1) ?? Slot::orderBy('slot_number')->first();
        if (! $slot?->end_time) {
            return null;
        }
        $time = substr($slot->end_time, 0, 5);
        return Carbon::parse($this->session_date->format('Y-m-d') . ' ' . $time);
    }

    /**
     * Thời điểm mở điểm danh (trước giờ bắt đầu 30 phút)
     */
    public function getAttendanceWindowOpenTime(): ?Carbon
    {
        $start = $this->getStartDateTime();
        return $start ? $start->copy()->subMinutes(30) : null;
    }

    /**
     * Thời điểm kết thúc điểm danh (sau khi vào ca 15 phút)
     */
    public function getAttendanceWindowCloseTime(): ?Carbon
    {
        $start = $this->getStartDateTime();
        return $start ? $start->copy()->addMinutes(15) : null;
    }

    /**
     * Hiển thị ca học kèm giờ bắt đầu và kết thúc chi tiết (ví dụ: Ca 1: 07:15 – 09:15)
     */
    public function getSlotTimeDisplay(): string
    {
        $slot = $this->slot ?? Slot::find($this->slot_id ?? 1) ?? Slot::orderBy('slot_number')->first();
        if (! $slot) {
            return 'Chưa xếp ca';
        }
        $start = substr($slot->start_time, 0, 5);
        $end = substr($slot->end_time, 0, 5);
        return "Ca {$slot->slot_number}: {$start} – {$end}";
    }

    /**
     * Kiểm tra buổi học có diễn ra đúng ngày HÔM NAY (Thời gian thực) hay không
     */
    public function isToday(): bool
    {
        return $this->session_date ? $this->session_date->isToday() : false;
    }

    /**
     * Kiểm tra buổi học đã qua trong thực tế hay chưa
     */
    public function isPastSession(): bool
    {
        if (! $this->session_date) {
            return false;
        }
        if ($this->session_date->isPast() && ! $this->session_date->isToday()) {
            return true;
        }
        $end = $this->getEndDateTime();
        return $end ? now()->gte($end) : false;
    }

    /**
     * Kiểm tra buổi học thuộc tương lai
     */
    public function isFutureSession(): bool
    {
        if (! $this->session_date) {
            return false;
        }
        if ($this->session_date->isFuture() && ! $this->session_date->isToday()) {
            return true;
        }
        $start = $this->getStartDateTime();
        return $start ? now()->lt($start) : false;
    }

    /**
     * Kiểm tra buổi học đã học trong thực tế chưa (thời gian hiện tại đã qua giờ kết thúc ca)
     */
    public function isLearned(): bool
    {
        return $this->isPastSession();
    }

    /**
     * Kiểm tra buổi học đã học và đã điểm danh
     */
    public function isLearnedAndMarked(): bool
    {
        $hasAttendance = $this->relationLoaded('attendances')
            ? $this->attendances->isNotEmpty()
            : $this->attendances()->exists();

        return $hasAttendance && $this->isPastSession();
    }

    /**
     * Thông tin chi tiết về khung giờ và quyền điểm danh
     */
    public function getAttendanceWindowStatus(?User $user = null): array
    {
        $start = $this->getStartDateTime();
        $end = $this->getEndDateTime();
        $open = $this->getAttendanceWindowOpenTime();
        $close = $this->getAttendanceWindowCloseTime();
        $now = now();

        $isToday = $this->isToday();
        $isPastSession = $this->isPastSession();
        $isFutureSession = $this->isFutureSession();

        $isBeforeWindow = $open ? $now->lt($open) : false;
        $isInWindow = ($open && $close) ? $now->between($open, $close) : false;
        $isPastWindow = $close ? $now->gt($close) : false;

        $isAdmin = $user ? ($user->isAdmin() || $user->role === User::ROLE_ADMIN) : false;

        // Chỉ Admin được xác nhận "Có mặt" (P) sau khi hết giờ điểm danh
        // Giáo viên chỉ được đánh "Có mặt" khi đang trong cửa sổ điểm danh
        $canMarkPresent = $isAdmin || $isInWindow;

        // Số phút còn lại trong cửa sổ mở điểm danh
        $remainingMinutes = ($isInWindow && $close) ? max(0, $now->diffInMinutes($close, false)) : 0;
        $minutesUntilOpen = ($isBeforeWindow && $open) ? max(0, $now->diffInMinutes($open, false)) : 0;

        return [
            'start' => $start,
            'end' => $end,
            'open' => $open,
            'close' => $close,
            'is_today' => $isToday,
            'is_past_session' => $isPastSession,
            'is_future_session' => $isFutureSession,
            'is_before_window' => $isBeforeWindow,
            'is_in_window' => $isInWindow,
            'is_past_window' => $isPastWindow,
            'is_admin' => $isAdmin,
            'can_mark_present' => $canMarkPresent,
            'remaining_minutes' => $remainingMinutes,
            'minutes_until_open' => $minutesUntilOpen,
        ];
    }
}
