<?php

namespace App\Services\Student;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Material;
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
     * - Buổi học tiếp theo (ngày + khung giờ + phòng)
     * - Bài tập/quiz sắp đến hạn mà sinh viên chưa nộp
     *
     * @return Collection<int, array>
     */
    public function enrolledClasses(User $student): Collection
    {
        $classIds = $this->access->enrolledClassIds($student);

        if ($classIds->isEmpty()) {
            return collect();
        }

        return ClassRoom::with([
                'teacher',
                'subject',
                // Buổi học kèm khung giờ + phòng để dựng "buổi tiếp theo"
                'sessions.slot',
                'sessions.room',
                // Bài tập kèm bài nộp CỦA riêng sinh viên này (lọc theo user_id)
                'assignments' => fn ($q) => $q->orderBy('due_date'),
                'assignments.submissions' => fn ($q) => $q->where('user_id', $student->user_id),
            ])
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

    /**
     * Số liệu tổng hợp hiển thị ở thanh KPI đầu trang.
     *
     * @param  Collection<int, array>  $cards
     * @return array<string, int>
     */
    public function summary(Collection $cards): array
    {
        if ($cards->isEmpty()) {
            return ['total' => 0, 'active' => 0, 'avg_percent' => 0, 'held' => 0, 'sessions_total' => 0, 'deadlines' => 0];
        }

        return [
            'total'          => $cards->count(),
            'active'         => $cards->where('class.status', ClassRoom::STATUS_ACTIVE)->count(),
            'avg_percent'    => (int) round($cards->avg('percent')),
            'held'           => (int) $cards->sum('held'),
            'sessions_total' => (int) $cards->sum('total'),
            // Tổng số bài tập/quiz sắp đến hạn còn chưa nộp trên tất cả các lớp
            'deadlines'      => (int) $cards->sum('pending_count'),
        ];
    }

    /**
     * Chặn sinh viên xem lớp chưa ghi danh.
     */
    public function authorizeClass(User $student, ClassRoom $class): void
    {
        if (! $this->access->enrolledClassIds($student)->contains($class->class_id)) {
            abort(403, 'Bạn chưa ghi danh vào lớp học này.');
        }
    }

    /**
     * Toàn bộ dữ liệu cho trang chi tiết 1 lớp — TẤT CẢ chỉ thuộc lớp này:
     * thông tin lớp, danh sách buổi học + điểm danh của sinh viên,
     * bài tập/quiz + trạng thái nộp, học liệu của môn, và các số liệu tổng hợp.
     *
     * @return array<string, mixed>
     */
    public function classDetail(User $student, ClassRoom $class): array
    {
        $class->load(['teacher', 'subject', 'major']);

        // Buổi học của lớp + điểm danh của riêng sinh viên
        $sessions = $class->sessions()->with(['slot', 'room'])->get();
        $attendanceMap = Attendance::where('user_id', $student->user_id)
            ->whereIn('session_id', $sessions->pluck('session_id'))
            ->get()
            ->keyBy('session_id');
        $sessions->each(fn ($s) => $s->my_attendance = $attendanceMap->get($s->session_id));

        // Bài tập/quiz của lớp + bài nộp của sinh viên
        $assignments = Assignment::where('class_id', $class->class_id)
            ->with(['submissions' => fn ($q) => $q->where('user_id', $student->user_id)])
            ->orderByRaw('CASE WHEN due_date >= NOW() THEN 0 ELSE 1 END')
            ->orderBy('due_date')
            ->get()
            ->each(fn ($a) => $a->my_submission = $a->submissions->first());

        // Học liệu của môn học gắn với lớp
        $materials = $class->subject_id
            ? Material::where('subject_id', $class->subject_id)->with('uploader')->orderByDesc('material_id')->get()
            : collect();

        // Số liệu tiến độ
        $held = $sessions->filter(fn ($s) => $s->session_date && ! $s->session_date->isAfter(today()))->count();
        $total = $class->total_sessions > 0 ? $class->total_sessions : $sessions->count();
        $percent = $total > 0 ? (int) round(min($held, $total) / $total * 100) : 0;

        return [
            'class'       => $class,
            'sessions'    => $sessions,
            'assignments' => $assignments,
            'materials'   => $materials,
            'held'        => min($held, $total ?: $held),
            'total'       => $total,
            'percent'     => $percent,
            'attendance'  => [
                'present' => $attendanceMap->where('status', Attendance::PRESENT)->count(),
                'late'    => $attendanceMap->where('status', Attendance::LATE)->count(),
                'absent'  => $attendanceMap->where('status', Attendance::ABSENT)->count(),
            ],
        ];
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

        // Buổi học tiếp theo: buổi gần nhất có ngày >= hôm nay
        $nextSession = $class->sessions
            ->filter(fn ($s) => $s->session_date && ! $s->session_date->isBefore(today()))
            ->sortBy('session_date')
            ->first();

        // Bài tập/quiz sắp đến hạn mà sinh viên chưa nộp
        $pending = $class->assignments
            ->filter(fn ($a) => $a->due_date && $a->due_date->isFuture() && $a->submissions->isEmpty())
            ->sortBy('due_date')
            ->values();

        return [
            'class'         => $class,
            'total'         => $total,
            'held'          => $held,
            'percent'       => $percent,
            'next_session'  => $nextSession,
            'next_deadline' => $pending->first(),
            'pending_count' => $pending->count(),
        ];
    }
}
