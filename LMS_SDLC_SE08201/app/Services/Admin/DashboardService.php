<?php

namespace App\Services\Admin;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Discussion;
use App\Models\Major;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Số liệu thống kê tổng quan cho Dashboard Admin.
     */
    public function getStats(): array
    {
        // ===== KPI Cards (giữ nguyên + bổ sung) =====
        $totalUsers     = User::count();
        $totalStudents  = User::where('role', User::ROLE_STUDENT)->count();
        $totalTeachers  = User::where('role', User::ROLE_TEACHER)->count();
        $totalMajors    = Major::count();
        $totalSubjects  = Subject::count();
        $totalClasses   = ClassRoom::count();
        $activeClasses  = ClassRoom::where('status', ClassRoom::STATUS_ACTIVE)->count();

        // Bổ sung: Bài tập đang chờ nộp toàn hệ thống
        $pendingAssignments = Assignment::where('due_date', '>', now())->count();

        // Bổ sung: Tỷ lệ chuyên cần tổng (%)
        $totalAttendance = Attendance::count();
        $presentCount    = Attendance::whereIn('status', ['P', 'L'])->count();
        $attendanceRate  = $totalAttendance > 0
            ? round($presentCount / $totalAttendance * 100, 1)
            : null;

        // ===== Khung trái: Biểu đồ =====

        // 1. Xu hướng điểm TB theo kỳ (6 tháng gần nhất)
        $scoreTrend = Submission::whereNotNull('score')
            ->whereNotNull('graded_at')
            ->where('graded_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->selectRaw("DATE_FORMAT(graded_at, '%Y-%m') as period, ROUND(AVG(score), 2) as avg_score")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // 2. Tỷ lệ phân bố điểm (5 khoảng)
        $scoreDistribution = [];

        // 3. Tỷ lệ SV theo ngành
        $studentsByMajor = [];

        // 4. Top 8 môn có điểm TB cao nhất
        $topSubjectsByScore = [];

        // 5. Tỷ lệ điểm danh P/A/L
        $attendanceStats = [];

        // 6. Lịch học hôm nay
        $todaySessions = ClassSession::with(['classRoom.subject', 'classRoom.teacher', 'slot', 'room', 'attendances'])
            ->where('session_date', Carbon::today())
            ->orderBy('slot_id')
            ->get();

        // ===== Khung phải: Bảng =====

        // Giữ nguyên: Thông báo gần đây
        $recentNotifications = Notification::with('author')
            ->orderByDesc('created_at')->take(5)->get();

        // Bổ sung: Top 8 bài tập sắp hạn
        $upcomingAssignments = Assignment::with(['classRoom.teacher', 'classRoom.subject'])
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->take(8)
            ->get();

        // Bổ sung: Trạng thái hệ thống
        $systemStatus = [
            'active_classes'     => $activeClasses,
            'open_assignments'   => Assignment::where('due_date', '>', now())->count(),
            'pending_grading'    => Submission::whereNotNull('submitted_at')->whereNull('graded_at')->count(),
            'today_discussions'  => Discussion::whereDate('created_at', Carbon::today())->count(),
            'new_users_7days'    => User::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
        ];

        return [
            // KPI Cards
            'total_users'           => $totalUsers,
            'total_students'        => $totalStudents,
            'total_teachers'        => $totalTeachers,
            'total_majors'          => $totalMajors,
            'total_subjects'        => $totalSubjects,
            'total_classes'         => $totalClasses,
            'active_classes'        => $activeClasses,
            'pending_assignments'   => $pendingAssignments,
            'attendance_rate'       => $attendanceRate,

            // Khung trái - Biểu đồ
            'score_trend'           => [], // Lấy qua AJAX
            'score_distribution'    => [], // Lấy qua AJAX
            'students_by_major'     => [], // Lấy qua AJAX
            'top_subjects_by_score' => [], // Lấy qua AJAX
            'attendance_stats'      => [], // Lấy qua AJAX
            'today_sessions'        => $todaySessions,

            // Khung phải - Bảng
            'recent_notifications'  => $recentNotifications,
            'upcoming_assignments'  => $upcomingAssignments,
            'system_status'         => $systemStatus,

            // Giữ tương thích cũ
            'recent_users'          => User::orderByDesc('created_at')->take(5)->get(),
            'recent_classes'        => ClassRoom::with(['teacher', 'subject'])
                ->orderByDesc('class_id')->take(5)->get(),
        ];
    }

    /**
     * Lấy dữ liệu 5 biểu đồ qua AJAX, có hỗ trợ bộ lọc chung.
     */
    public function getDashboardCharts(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $majorId = $filters['major_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;

        // ==========================
        // 1. SCORE TREND
        // ==========================
        $isCustomDate = !empty($filters['date_from']) || !empty($filters['date_to']);
        $trendDateFrom = $dateFrom;
        $trendDateTo = $dateTo;
        if (!$isCustomDate) {
            $trendDateFrom = Carbon::now()->subWeeks(12)->startOfWeek()->format('Y-m-d');
            $trendDateTo = Carbon::now()->endOfWeek()->format('Y-m-d');
        }

        $qTrend = Submission::join('assignments', 'submissions.assignment_id', '=', 'assignments.assignment_id')
            ->join('classes', 'assignments.class_id', '=', 'classes.class_id')
            ->whereNotNull('submissions.score')
            ->whereNotNull('submissions.graded_at');

        if ($trendDateFrom) $qTrend->where('submissions.graded_at', '>=', Carbon::parse($trendDateFrom)->startOfDay());
        if ($trendDateTo) $qTrend->where('submissions.graded_at', '<=', Carbon::parse($trendDateTo)->endOfDay());
        if ($majorId) $qTrend->where('classes.major_id', $majorId);
        if ($subjectId) $qTrend->where('classes.subject_id', $subjectId);

        if ($isCustomDate) {
            $qTrend->selectRaw("DATE(submissions.graded_at) as period_key, MIN(DATE(submissions.graded_at)) as period_start, ROUND(AVG(submissions.score), 2) as avg_score");
        } else {
            $qTrend->selectRaw("DATE_FORMAT(submissions.graded_at, '%x-W%v') as period_key, MIN(DATE(submissions.graded_at)) as period_start, ROUND(AVG(submissions.score), 2) as avg_score");
        }
        $qTrend->groupBy('period_key')->orderBy('period_key');
        $trendResults = $qTrend->get();

        $labelsTrend = [];
        $scoresTrend = [];
        foreach ($trendResults as $row) {
            $labelsTrend[] = $isCustomDate ? Carbon::parse($row->period_start)->format('d/m/Y') : 'Tuần ' . Carbon::parse($row->period_start)->format('d/m/Y');
            $scoresTrend[] = (float) $row->avg_score;
        }

        // ==========================
        // 2. SCORE DISTRIBUTION
        // ==========================
        $qDist = Submission::whereNotNull('score');
        if ($majorId || $subjectId || $dateFrom || $dateTo) {
            $qDist->join('assignments', 'submissions.assignment_id', '=', 'assignments.assignment_id')
                  ->join('classes', 'assignments.class_id', '=', 'classes.class_id');
            if ($majorId) $qDist->where('classes.major_id', $majorId);
            if ($subjectId) $qDist->where('classes.subject_id', $subjectId);
            if ($dateFrom) $qDist->where('submissions.graded_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            if ($dateTo) $qDist->where('submissions.graded_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $scoreDistribution = [
            '0-2'  => (clone $qDist)->where('score', '>=', 0)->where('score', '<', 2)->count(),
            '2-4'  => (clone $qDist)->where('score', '>=', 2)->where('score', '<', 4)->count(),
            '4-6'  => (clone $qDist)->where('score', '>=', 4)->where('score', '<', 6)->count(),
            '6-8'  => (clone $qDist)->where('score', '>=', 6)->where('score', '<', 8)->count(),
            '8-10' => (clone $qDist)->where('score', '>=', 8)->where('score', '<=', 10)->count(),
        ];

        // ==========================
        // 3. STUDENTS BY MAJOR
        // ==========================
        $qMajor = User::where('role', User::ROLE_STUDENT)
            ->whereNotNull('users.major_id')
            ->join('majors', 'users.major_id', '=', 'majors.major_id');
        if ($majorId) {
            $qMajor->where('users.major_id', $majorId);
        }
        if ($subjectId) {
            $qMajor->whereHas('enrolledClasses', fn($q) => $q->where('subject_id', $subjectId));
        }
        $studentsByMajorData = $qMajor->selectRaw('majors.major_name, COUNT(DISTINCT users.user_id) as count')
            ->groupBy('majors.major_name')
            ->get();
        $studentsMajor = [
            'labels' => $studentsByMajorData->pluck('major_name')->toArray(),
            'counts' => $studentsByMajorData->pluck('count')->toArray(),
        ];

        // ==========================
        // 4. TOP SUBJECTS BY SCORE
        // ==========================
        $qTopSub = Submission::whereNotNull('score')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.assignment_id')
            ->join('classes', 'assignments.class_id', '=', 'classes.class_id')
            ->join('subjects', 'classes.subject_id', '=', 'subjects.subject_id');
        if ($majorId) $qTopSub->where('classes.major_id', $majorId);
        if ($subjectId) $qTopSub->where('classes.subject_id', $subjectId);
        if ($dateFrom) $qTopSub->where('submissions.graded_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        if ($dateTo) $qTopSub->where('submissions.graded_at', '<=', Carbon::parse($dateTo)->endOfDay());
        
        $topSubjectsData = $qTopSub->selectRaw('subjects.subject_name, ROUND(AVG(submissions.score), 2) as avg_score')
            ->groupBy('subjects.subject_name')
            ->orderByDesc('avg_score')
            ->take(8)
            ->get();
        $topSubjects = [
            'labels' => $topSubjectsData->pluck('subject_name')->toArray(),
            'scores' => $topSubjectsData->pluck('avg_score')->toArray(),
        ];

        // ==========================
        // 5. ATTENDANCE STATS
        // ==========================
        $qAtt = Attendance::join('sessions', 'attendances.session_id', '=', 'sessions.session_id')
            ->join('classes', 'sessions.class_id', '=', 'classes.class_id');
        if ($majorId) $qAtt->where('classes.major_id', $majorId);
        if ($subjectId) $qAtt->where('classes.subject_id', $subjectId);
        if ($dateFrom) $qAtt->where('sessions.session_date', '>=', Carbon::parse($dateFrom)->startOfDay());
        if ($dateTo) $qAtt->where('sessions.session_date', '<=', Carbon::parse($dateTo)->endOfDay());
        
        $attendanceStats = [
            'P' => (clone $qAtt)->where('attendances.status', 'P')->count(),
            'A' => (clone $qAtt)->where('attendances.status', 'A')->count(),
            'L' => (clone $qAtt)->where('attendances.status', 'L')->count(),
        ];

        return [
            'scoreTrend' => [
                'labels' => $labelsTrend,
                'scores' => $scoresTrend
            ],
            'scoreDist' => $scoreDistribution,
            'studentsMajor' => $studentsMajor,
            'topSubjects' => $topSubjects,
            'attendance' => $attendanceStats,
        ];
    }
}
