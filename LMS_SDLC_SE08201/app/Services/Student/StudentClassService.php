<?php

namespace App\Services\Student;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentClassService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    /**
     * Danh sách lớp sinh viên đã ghi danh, kèm thông tin hiển thị cho card:
     * - Tên lớp + môn học
     * - Giảng viên (kèm avatar)
     * - Trạng thái (Chưa mở / Đang học / Đã đóng)
     * - Tiến độ: số buổi đã học / tổng số buổi (%)
     *
     * @return Collection<int, array>
     */
    public function enrolledClasses(User $student): Collection
    {
        $classIds = $this->access->enrolledClassIds($student);

        if ($classIds->isEmpty()) {
            return collect();
        }

        return ClassRoom::with(['teacher', 'subject'])
            ->whereIn('class_id', $classIds)
            // Số buổi đã diễn ra: có ngày học <= hôm nay
            ->withCount(['sessions as held_sessions_count' => function ($query) {
                $query->whereDate('session_date', '<=', today());
            }])
            // Tổng số buổi thực tế đã xếp lịch (dùng khi total_sessions chưa được nhập)
            ->withCount('sessions as scheduled_sessions_count')
            // Ưu tiên: Đang học (1) → Sắp mở (0) → Đã kết thúc (2)
            ->orderByRaw('FIELD(status, 1, 0, 2)')
            ->orderBy('class_name')
            ->get()
            ->map(fn (ClassRoom $class) => $this->presentCard($class))
            ->values();
    }

    /** Chuẩn hóa dữ liệu 1 lớp thành mảng phục vụ view. */
    private function presentCard(ClassRoom $class): array
    {
        // total_sessions là số buổi dự kiến (cột trong bảng classes).
        // Nếu chưa nhập (0), lấy số buổi đã được xếp lịch làm mốc.
        $total = $class->total_sessions > 0
            ? $class->total_sessions
            : (int) $class->scheduled_sessions_count;

        $held = (int) $class->held_sessions_count;

        // Không cho vượt quá tổng số buổi
        if ($total > 0) {
            $held = min($held, $total);
        }

        $percent = $total > 0 ? (int) round($held / $total * 100) : 0;

        return [
            'class'   => $class,
            'total'   => $total,
            'held'    => $held,
            'percent' => $percent,
        ];
    }
}
