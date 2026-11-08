<?php

namespace App\Services\Student;

use App\Models\ClassSession;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    /**
     * Lấy danh sách các năm có lịch học của sinh viên
     */
    public function availableYears(User $student): array
    {
        $classIds = $this->access->enrolledClassIds($student);
        $years = ClassSession::whereIn('class_id', $classIds)
            ->selectRaw('YEAR(session_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
            
        // Nếu rỗng thì trả về năm hiện tại
        return empty($years) ? [Carbon::now()->year] : $years;
    }

    /**
     * Lấy danh sách tuần trong một năm
     */
    public function weeksOfYear(int $year): array
    {
        $weeks = [];
        $date = Carbon::createFromDate($year, 1, 1)->startOfWeek(Carbon::MONDAY);
        
        for ($i = 0; $i < 53; $i++) {
            $start = $date->copy()->addWeeks($i);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
            
            if ($start->year > $year) {
                break;
            }
            
            $weeks[] = [
                'week_start' => $start->toDateString(),
                'label' => $start->format('d/m') . ' - ' . $end->format('d/m')
            ];
        }
        return $weeks;
    }

    /**
     * Lấy danh sách tất cả ca học (slots)
     */
    public function allSlots(): Collection
    {
        return Slot::orderBy('slot_number')->get();
    }

    /**
     * Thời khóa biểu theo TUẦN (Thứ 2 → Chủ nhật) của tuần chứa $weekStart.
     * Trả về collection các buổi học, gom nhóm theo thứ trong tuần (1 = T2 … 7 = CN).
     */
    public function weeklyTimetable(User $student, Carbon $weekStart): Collection
    {
        $classIds = $this->access->enrolledClassIds($student);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return ClassSession::with([
            'classRoom.subject', 
            'classRoom.teacher', 
            'slot', 
            'room',
            'attendances' => function($query) use ($student) {
                $query->where('user_id', $student->user_id);
            }
        ])
            ->whereIn('class_id', $classIds)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('slot_id')
            ->get()
            ->groupBy(fn ($s) => $s->session_date->dayOfWeekIso); // 1 (T2) … 7 (CN)
    }

    /**
     * Danh sách lớp đã ghi danh (dùng cho bộ lọc)
     */
    public function enrolledClassesForFilter(User $student): Collection
    {
        $classIds = $this->access->enrolledClassIds($student);
        return \App\Models\ClassRoom::with('subject')
            ->whereIn('class_id', $classIds)
            ->get();
    }

    /**
     * Lịch sử điểm danh gom nhóm theo TUẦN (mới nhất trước), kèm thống kê từng tuần.
     */
    public function attendanceHistory(User $student, ?int $classId = null, ?string $search = null): array
    {
        $records = $student->attendances()
            ->with('session.classRoom.subject', 'session.slot')
            ->get()
            ->filter(fn ($a) => $a->session !== null);

        if ($classId) {
            $records = $records->filter(fn ($a) => $a->session->class_id == $classId);
        }

        if ($search) {
            $search = mb_strtolower($search);
            $records = $records->filter(function ($a) use ($search) {
                $className = mb_strtolower($a->session->classRoom->class_name ?? '');
                $subjectName = mb_strtolower($a->session->classRoom->subject->subject_name ?? '');
                $dateStr = $a->session->session_date->format('d/m/Y');
                
                $statusStr = match($a->status) {
                    'P' => 'có mặt',
                    'A' => 'vắng',
                    'L' => 'muộn',
                    default => ''
                };

                return str_contains($className, $search) || 
                       str_contains($subjectName, $search) || 
                       str_contains($dateStr, $search) || 
                       str_contains($statusStr, $search);
            });
        }

        $byWeek = $records
            ->sortByDesc(fn ($a) => $a->session->session_date)
            ->groupBy(fn ($a) => $a->session->session_date->copy()->startOfWeek(Carbon::MONDAY)->toDateString());

        $weeks = $byWeek->map(fn ($group, $weekStart) => [
            'week_start' => Carbon::parse($weekStart),
            'week_end' => Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY),
            'present' => $group->where('status', 'P')->count(),
            'late' => $group->where('status', 'L')->count(),
            'absent' => $group->where('status', 'A')->count(),
            'records' => $group->sortBy(fn ($a) => $a->session->session_date)->values(),
        ]);

        $totalSessions = $records->count();
        $totalPresent  = $records->where('status', 'P')->count();
        $totalAbsent   = $records->where('status', 'A')->count();
        $totalLate     = $records->where('status', 'L')->count();
        $attendanceRate = $totalSessions > 0 ? round(($totalPresent / $totalSessions) * 100, 1) : 100.0;

        return [
            'weeks' => $weeks,
            'summary' => [
                'total' => $totalSessions,
                'present' => $totalPresent,
                'absent' => $totalAbsent,
                'late' => $totalLate,
                'rate' => $attendanceRate,
            ]
        ];

        
    }
}
