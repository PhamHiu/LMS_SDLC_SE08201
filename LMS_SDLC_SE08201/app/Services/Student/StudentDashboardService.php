<?php

namespace App\Services\Student;

use App\Models\Assignment;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Support\Carbon;

class StudentDashboardService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    /**
     * Dashboard sinh viên: bài sắp đến hạn + kết quả học tập + lịch tuần.
     */
    public function getStats(User $student): array
    {
        $classIds = $this->access->enrolledClassIds($student);

        // Bài tập sắp đến hạn mà sinh viên CHƯA nộp
        $submittedIds = $student->submissions()->whereNotNull('submitted_at')->pluck('assignment_id');
        $upcomingAssignments = Assignment::with('classRoom.subject')
            ->whereIn('class_id', $classIds)
            ->whereNotIn('assignment_id', $submittedIds)
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Kết quả gần đây
        $recentGrades = $student->submissions()
            ->with('assignment.classRoom.subject')
            ->whereNotNull('score')
            ->orderByDesc('graded_at')
            ->take(5)
            ->get();

        // Lịch học tuần này
        $weekSessions = ClassSession::with('classRoom.subject', 'slot', 'room')
            ->whereIn('class_id', $classIds)
            ->whereBetween('session_date', [Carbon::now()->startOfWeek(Carbon::MONDAY), Carbon::now()->endOfWeek(Carbon::SUNDAY)])
            ->orderBy('session_date')
            ->get();

        // Tỷ lệ chuyên cần tổng
        $attendances = $student->attendances;
        $totalAtt = $attendances->count();

        return [
            'upcomingAssignments' => $upcomingAssignments,
            'recentGrades' => $recentGrades,
            'weekSessions' => $weekSessions,
            'enrolledCount' => $classIds->count(),
            'avgScore' => round((float) $student->submissions()->whereNotNull('score')->avg('score'), 2),
            'attendanceRate' => $totalAtt > 0
                ? round($attendances->whereIn('status', ['P', 'L'])->count() / $totalAtt * 100)
                : null,
        ];
    }
}
