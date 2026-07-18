<?php

namespace App\Services\Student;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    /**
     * Thời khóa biểu theo TUẦN (Thứ 2 → Chủ nhật) của tuần chứa $weekStart.
     * Trả về collection các buổi học, gom nhóm theo thứ trong tuần (1 = T2 … 7 = CN).
     */
    public function weeklyTimetable(User $student, Carbon $weekStart): Collection
    {
        $classIds = $this->access->enrolledClassIds($student);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return ClassSession::with('classRoom.subject', 'classRoom.teacher', 'slot', 'room')
            ->whereIn('class_id', $classIds)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('slot_id')
            ->get()
            ->groupBy(fn ($s) => $s->session_date->dayOfWeekIso); // 1 (T2) … 7 (CN)
    }

    /**
     * Lịch sử điểm danh gom nhóm theo TUẦN (mới nhất trước), kèm thống kê từng tuần.
     */
    public function attendanceHistory(User $student): array
    {
        $records = $student->attendances()
            ->with('session.classRoom.subject', 'session.slot')
            ->get()
            ->filter(fn ($a) => $a->session !== null);

        $byWeek = $records
            ->sortByDesc(fn ($a) => $a->session->session_date)
            ->groupBy(fn ($a) => $a->session->session_date->copy()->startOfWeek(Carbon::MONDAY)->toDateString());

        $weeks = $byWeek->map(fn ($group, $weekStart) => [
            'week_start' => Carbon::parse($weekStart),
            'week_end' => Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY),
            'present' => $group->where('status', 'P')->count(),
            'late' => $group->where('status', 'L')->count(),
            'absent' => $group->where('status', 'A')->count(),
            'records' => $group->sortBy(fn ($a) => $a->session->session_date),
        ]);

        return ['weeks' => $weeks];
    }
}
