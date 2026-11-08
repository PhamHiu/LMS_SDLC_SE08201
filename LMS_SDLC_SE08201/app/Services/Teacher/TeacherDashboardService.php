<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Discussion;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;

class TeacherDashboardService
{
    /**
     * Dashboard giảng viên: lịch dạy tuần này + tiến độ lớp + bài chờ chấm + biểu đồ.
     */
    public function getStats(User $teacher): array
    {
        $classIds = $teacher->teachingClasses()->pluck('class_id');

        // Lịch dạy trong tuần hiện tại
        $weekSessions = ClassSession::with(['classRoom.subject', 'slot', 'room', 'attendances'])
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

        $totalStudents = $classes->sum('enrollments_count');

        // ===== Bổ sung dữ liệu mới =====

        // Tỷ lệ chuyên cần trung bình các lớp mình dạy
        $sessionIds = ClassSession::whereIn('class_id', $classIds)->pluck('session_id');
        $totalAtt   = Attendance::whereIn('session_id', $sessionIds)->count();
        $presentAtt = Attendance::whereIn('session_id', $sessionIds)->whereIn('status', ['P', 'L'])->count();
        $attendanceAvg = $totalAtt > 0 ? round($presentAtt / $totalAtt * 100, 1) : null;

        // Phân bố điểm số (5 khoảng) — các lớp mình dạy
        $scoreQuery = Submission::whereNotNull('score')
            ->whereHas('assignment', fn ($q) => $q->whereIn('class_id', $classIds));

        $scoreDistribution = [
            '0-2'  => (clone $scoreQuery)->where('score', '>=', 0)->where('score', '<', 2)->count(),
            '2-4'  => (clone $scoreQuery)->where('score', '>=', 2)->where('score', '<', 4)->count(),
            '4-6'  => (clone $scoreQuery)->where('score', '>=', 4)->where('score', '<', 6)->count(),
            '6-8'  => (clone $scoreQuery)->where('score', '>=', 6)->where('score', '<', 8)->count(),
            '8-10' => (clone $scoreQuery)->where('score', '>=', 8)->where('score', '<=', 10)->count(),
        ];

        // Tỷ lệ nộp bài: (tổng submitted) / (tổng enrolled × assignments) × 100
        $totalSubmitted = Submission::whereNotNull('submitted_at')
            ->whereHas('assignment', fn ($q) => $q->whereIn('class_id', $classIds))
            ->count();
        $totalAssignments = $classes->sum(fn ($c) => $c->assignments()->count());
        $totalExpected    = 0;
        foreach ($classes as $c) {
            $totalExpected += $c->enrollments_count * $c->assignments()->count();
        }
        $submissionRate = $totalExpected > 0 ? round($totalSubmitted / $totalExpected * 100, 1) : null;

        // Top 5 thảo luận gần đây trong lớp mình
        $recentDiscussions = Discussion::with(['author', 'assignment.classRoom'])
            ->whereHas('assignment', fn ($q) => $q->whereIn('class_id', $classIds))
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return [
            'weekSessions'       => $weekSessions,
            'classes'            => $classes,
            'pendingGrading'     => $pendingGrading,
            'totalStudents'      => $totalStudents,
            'attendance_avg'     => $attendanceAvg,
            'score_distribution' => $scoreDistribution,
            'submission_rate'    => $submissionRate,
            'recent_discussions' => $recentDiscussions,
        ];
    }
}
