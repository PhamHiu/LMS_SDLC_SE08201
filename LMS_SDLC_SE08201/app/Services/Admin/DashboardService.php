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
        $scoreDistribution = [
            '0-2'  => Submission::whereNotNull('score')->where('score', '>=', 0)->where('score', '<', 2)->count(),
            '2-4'  => Submission::whereNotNull('score')->where('score', '>=', 2)->where('score', '<', 4)->count(),
            '4-6'  => Submission::whereNotNull('score')->where('score', '>=', 4)->where('score', '<', 6)->count(),
            '6-8'  => Submission::whereNotNull('score')->where('score', '>=', 6)->where('score', '<', 8)->count(),
            '8-10' => Submission::whereNotNull('score')->where('score', '>=', 8)->where('score', '<=', 10)->count(),
        ];

        // 3. Tỷ lệ SV theo ngành
        $studentsByMajor = User::where('role', User::ROLE_STUDENT)
            ->whereNotNull('users.major_id')
            ->join('majors', 'users.major_id', '=', 'majors.major_id')
            ->selectRaw('majors.major_name, COUNT(*) as count')
            ->groupBy('majors.major_name')
            ->get();

        // 4. Top 8 môn có điểm TB cao nhất
        $topSubjectsByScore = Submission::whereNotNull('score')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.assignment_id')
            ->join('classes', 'assignments.class_id', '=', 'classes.class_id')
            ->join('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->selectRaw('subjects.subject_name, ROUND(AVG(submissions.score), 2) as avg_score')
            ->groupBy('subjects.subject_name')
            ->orderByDesc('avg_score')
            ->take(8)
            ->get();

        // 5. Tỷ lệ điểm danh P/A/L
        $attendanceStats = [
            'P' => Attendance::where('status', 'P')->count(),
            'A' => Attendance::where('status', 'A')->count(),
            'L' => Attendance::where('status', 'L')->count(),
        ];

        // 6. Lịch học hôm nay
        $todaySessions = ClassSession::with(['classRoom.subject', 'classRoom.teacher', 'slot', 'room'])
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
            'score_trend'           => $scoreTrend,
            'score_distribution'    => $scoreDistribution,
            'students_by_major'     => $studentsByMajor,
            'top_subjects_by_score' => $topSubjectsByScore,
            'attendance_stats'      => $attendanceStats,
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
}
