<?php

namespace App\Services\Teacher;

use App\Models\ClassSession;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;

class TeacherDashboardService
{
    /**
     * Dashboard giảng viên: lịch dạy tuần này + tiến độ lớp + bài chờ chấm.
     */
    public function getStats(User $teacher): array
    {
        $classIds = $teacher->teachingClasses()->pluck('class_id');

        // Lịch dạy trong tuần hiện tại
        $weekSessions = ClassSession::with('classRoom.subject')
            ->whereIn('class_id', $classIds)
            ->whereBetween('session_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->orderBy('session_date')
            ->get();

        // Tiến độ từng lớp: số buổi đã diễn ra / tổng số buổi
        $classes = $teacher->teachingClasses()
            ->with('subject')
            ->withCount([
                'enrollments',
                'sessions as done_sessions_count' => fn ($q) => $q->where('session_date', '<', Carbon::today()),
            ])
            ->get();

        // Số bài tự luận đã nộp nhưng chưa chấm
        $pendingGrading = Submission::whereHas('assignment', fn ($q) => $q->whereIn('class_id', $classIds)->where('type', 'essay'))
            ->whereNotNull('submitted_at')
            ->whereNull('graded_at')
            ->count();

        return [
            'weekSessions' => $weekSessions,
            'classes' => $classes,
            'pendingGrading' => $pendingGrading,
            'totalStudents' => $classes->sum('enrollments_count'),
        ];
    }
}
