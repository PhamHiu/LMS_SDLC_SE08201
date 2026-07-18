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
     * Dashboard sinh viên: bài sắp đến hạn + kết quả học tập + lịch tuần + biểu đồ.
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

        // ===== Bổ sung dữ liệu mới =====

        // Tiến trình hoàn thành bài tập per class
        $enrolledClasses = $student->enrolledClasses()->with('subject')->get();
        $assignmentProgress = [];
        foreach ($enrolledClasses as $class) {
            $totalAssign = $class->assignments()->count();
            $doneAssign  = $student->submissions()
                ->whereNotNull('submitted_at')
                ->whereHas('assignment', fn ($q) => $q->where('class_id', $class->class_id))
                ->count();
            $assignmentProgress[] = [
                'class_name' => $class->class_name,
                'subject_name' => $class->subject?->subject_name,
                'total'  => $totalAssign,
                'done'   => $doneAssign,
                'percent' => $totalAssign > 0 ? round($doneAssign / $totalAssign * 100) : 0,
            ];
        }

        // Lịch sử điểm (trend) — điểm theo thời gian
        $scoreHistory = $student->submissions()
            ->with('assignment')
            ->whereNotNull('score')
            ->whereNotNull('graded_at')
            ->orderBy('graded_at')
            ->get()
            ->map(fn ($s) => [
                'title' => $s->assignment?->title,
                'score' => $s->score,
                'max_score' => $s->assignment?->max_score,
                'date'  => $s->graded_at?->format('d/m'),
            ]);

        // Chuyên cần cá nhân: {present, absent, late}
        $attendanceBreakdown = [
            'present' => $attendances->where('status', 'P')->count(),
            'absent'  => $attendances->where('status', 'A')->count(),
            'late'    => $attendances->where('status', 'L')->count(),
        ];

        // Số bài tập chưa nộp (dùng cho KPI card)
        $pendingCount = Assignment::whereIn('class_id', $classIds)
            ->where('due_date', '>', now())
            ->whereNotIn('assignment_id', $submittedIds)
            ->count();

        return [
            'upcomingAssignments'   => $upcomingAssignments,
            'recentGrades'          => $recentGrades,
            'weekSessions'          => $weekSessions,
            'enrolledCount'         => $classIds->count(),
            'avgScore'              => round((float) $student->submissions()->whereNotNull('score')->avg('score'), 2),
            'attendanceRate'        => $totalAtt > 0
                ? round($attendances->whereIn('status', ['P', 'L'])->count() / $totalAtt * 100)
                : null,
            'assignment_progress'   => $assignmentProgress,
            'score_history'         => $scoreHistory,
            'attendance_breakdown'  => $attendanceBreakdown,
            'pending_count'         => $pendingCount,
        ];
    }
}
