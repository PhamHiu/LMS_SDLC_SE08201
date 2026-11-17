<?php

namespace App\Services\Attendance;

use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Slot;
use Carbon\Carbon;

class RealtimeScheduleService
{
    /**
     * Lấy thông tin thời gian thực hệ thống và ca học hiện tại
     */
    public function getCurrentTimeInfo(): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->copy()->startOfDay();

        $currentSlot = Slot::getCurrentSlot($now);
        $activeSlot = Slot::getAttendanceActiveSlot($now);
        $upcomingSlot = Slot::getUpcomingSlot($now);

        return [
            'now' => $now,
            'today' => $today,
            'formatted_time' => $now->format('H:i:s'),
            'formatted_date' => $now->format('d/m/Y'),
            'day_name' => $now->locale('vi')->dayName,
            'timezone' => 'Asia/Ho_Chi_Minh',
            'current_slot' => $currentSlot,
            'active_slot' => $activeSlot,
            'upcoming_slot' => $upcomingSlot,
        ];
    }

    /**
     * Tự động căn chỉnh lịch học của một lớp xoay quanh ngày hôm nay (Thời gian thực)
     * - Giữ nguyên kết quả điểm danh của các buổi quá khứ để bảo toàn dữ liệu AI Risk.
     * - Đặt 1 buổi rơi đúng vào HÔM NAY (Thời gian thực) để giảng viên điểm danh.
     * - Các buổi còn lại chia đều vào các ngày tương lai.
     */
    public function alignClassSchedule(ClassRoom $class): void
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $sessions = $class->sessions()->orderBy('session_id')->get();
        $total = $sessions->count();
        if ($total === 0) {
            return;
        }

        // Chọn buổi ở giữa làm buổi Hôm nay (ví dụ: buổi 6/12)
        $todayIndex = min(5, max(0, intval($total / 2) - 1));

        // Khoảng cách ngày giữa các buổi học (3 ngày / buổi: Thứ 2, Thứ 5 hoặc cách ngày)
        $intervalDays = 3;

        foreach ($sessions as $idx => $sess) {
            $offset = ($idx - $todayIndex) * $intervalDays;
            $sessionDate = $today->copy()->addDays($offset);

            // Gán ca học mặc định nếu chưa có
            $slotId = $sess->slot_id ?? ((($class->class_id + $idx) % 4) + 1);
            $roomId = $sess->room_id ?? (($class->class_id % 5) + 1);

            $sess->update([
                'session_date' => $sessionDate->toDateString(),
                'slot_id' => $slotId,
                'room_id' => $roomId,
            ]);

            // Nếu là buổi tương lai, xóa điểm danh cũ (vì chưa diễn ra)
            if ($offset > 0) {
                $sess->attendances()->delete();
            }
        }
    }

    /**
     * Căn chỉnh toàn bộ các lớp học trong hệ thống
     */
    public function alignAllClasses(): void
    {
        $classes = ClassRoom::all();
        foreach ($classes as $cls) {
            $this->alignClassSchedule($cls);
        }
    }
}
