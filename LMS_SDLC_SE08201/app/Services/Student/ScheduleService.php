<?php

namespace App\Services\Student;

use App\Models\ClassSession;
use App\Models\User;

class ScheduleService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    /**
     * Thời khóa biểu toàn bộ các lớp đã ghi danh.
     */
    public function timetable(User $student)
    {
        $classIds = $this->access->enrolledClassIds($student);

        return ClassSession::with('classRoom.subject', 'classRoom.teacher')
            ->whereIn('class_id', $classIds)
            ->orderBy('session_date')
            ->get()
            ->groupBy(fn ($s) => $s->session_date->format('Y-m'));
    }

    /**
     * Lịch sử điểm danh theo từng lớp + thống kê.
     */
    public function attendanceHistory(User $student): array
    {
        $records = $student->attendances()
            ->with('session.classRoom.subject')
            ->get()
            ->sortByDesc(fn ($a) => $a->session->session_date)
            ->groupBy(fn ($a) => $a->session->class_id);

        $summary = $records->map(fn ($group) => [
            'class' => $group->first()->session->classRoom,
            'present' => $group->where('status', 'P')->count(),
            'late' => $group->where('status', 'L')->count(),
            'absent' => $group->where('status', 'A')->count(),
            'records' => $group,
        ]);

        return ['summary' => $summary];
    }
}
