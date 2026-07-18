<?php

namespace App\Services\Admin;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    /**
     * Helper: Áp dụng bộ lọc chung (ngành, môn, lớp, ngày, giảng viên)
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query;
    }

    /**
     * Thống kê Điểm số
     */
    public function getGradeStats(array $filters): array
    {
        $query = Submission::whereNotNull('score')->with(['assignment.classRoom.subject', 'student']);

        // Filter nâng cao cho Submission
        if (!empty($filters['date_from'])) {
            $query->where('graded_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('graded_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['class_id'])) {
            $query->whereHas('assignment', fn($q) => $q->where('class_id', $filters['class_id']));
        } elseif (!empty($filters['subject_id'])) {
            $query->whereHas('assignment.classRoom', fn($q) => $q->where('subject_id', $filters['subject_id']));
        } elseif (!empty($filters['major_id'])) {
            $query->whereHas('assignment.classRoom', fn($q) => $q->where('major_id', $filters['major_id']));
        }
        if (!empty($filters['teacher_id'])) {
            $query->whereHas('assignment.classRoom', fn($q) => $q->where('user_id', $filters['teacher_id']));
        }
        if (!empty($filters['student_id'])) {
            $query->where('user_id', $filters['student_id']);
        }

        $submissions = $query->get();

        $avgScore = $submissions->avg('score');
        $maxScore = $submissions->max('score');
        $minScore = $submissions->min('score');
        $totalGraded = $submissions->count();

        // Phân bố điểm số (0-2, 2-4, 4-6, 6-8, 8-10)
        $distribution = [
            '0-2'  => $submissions->where('score', '>=', 0)->where('score', '<', 2)->count(),
            '2-4'  => $submissions->where('score', '>=', 2)->where('score', '<', 4)->count(),
            '4-6'  => $submissions->where('score', '>=', 4)->where('score', '<', 6)->count(),
            '6-8'  => $submissions->where('score', '>=', 6)->where('score', '<', 8)->count(),
            '8-10' => $submissions->where('score', '>=', 8)->where('score', '<=', 10)->count(),
        ];

        // Trend điểm TB theo tháng (6 tháng gần đây nếu không có filter date)
        $trend = $submissions->groupBy(fn($s) => Carbon::parse($s->graded_at)->format('Y-m'))
            ->map(fn($group) => round($group->avg('score'), 2));

        return [
            'summary' => [
                'avg_score' => $avgScore ? round($avgScore, 2) : 0,
                'max_score' => $maxScore ?? 0,
                'min_score' => $minScore ?? 0,
                'total_graded' => $totalGraded,
            ],
            'distribution' => $distribution,
            'trend' => $trend,
            'data' => $submissions // Dùng cho table/export
        ];
    }

    /**
     * Thống kê Chuyên cần
     */
    public function getAttendanceStats(array $filters): array
    {
        $query = Attendance::with(['student', 'session.classRoom.subject']);

        if (!empty($filters['date_from'])) {
            $query->where('attendance_time', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('attendance_time', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['class_id'])) {
            $query->whereHas('session', fn($q) => $q->where('class_id', $filters['class_id']));
        } elseif (!empty($filters['subject_id'])) {
            $query->whereHas('session.classRoom', fn($q) => $q->where('subject_id', $filters['subject_id']));
        } elseif (!empty($filters['major_id'])) {
            $query->whereHas('session.classRoom', fn($q) => $q->where('major_id', $filters['major_id']));
        }
        if (!empty($filters['teacher_id'])) {
            $query->whereHas('session.classRoom', fn($q) => $q->where('user_id', $filters['teacher_id']));
        }
        if (!empty($filters['student_id'])) {
            $query->where('user_id', $filters['student_id']);
        }

        $attendances = $query->get();
        $total = $attendances->count();

        $present = $attendances->where('status', 'P')->count();
        $absent = $attendances->where('status', 'A')->count();
        $late = $attendances->where('status', 'L')->count();

        // Trend theo tuần
        $trend = $attendances->groupBy(fn($a) => Carbon::parse($a->attendance_time)->format('Y-W'))
            ->map(function($group) {
                $totalInWeek = $group->count();
                $presentInWeek = $group->whereIn('status', ['P', 'L'])->count();
                return $totalInWeek > 0 ? round($presentInWeek / $totalInWeek * 100, 2) : 0;
            });

        return [
            'summary' => [
                'present_rate' => $total > 0 ? round(($present + $late) / $total * 100, 2) : 0,
                'present_count' => $present,
                'absent_count' => $absent,
                'late_count' => $late,
                'total_records' => $total,
            ],
            'trend' => $trend,
            'data' => $attendances
        ];
    }

    /**
     * Thống kê Khóa học (Lớp học)
     */
    public function getCourseStats(array $filters): array
    {
        $query = ClassRoom::with(['subject', 'major', 'teacher'])
            ->withCount(['students', 'sessions']);

        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('start_date', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['major_id'])) {
            $query->where('major_id', $filters['major_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['teacher_id'])) {
            $query->where('user_id', $filters['teacher_id']);
        }

        $classes = $query->get();

        $statusCount = [
            'pending' => $classes->where('status', ClassRoom::STATUS_PENDING)->count(),
            'active' => $classes->where('status', ClassRoom::STATUS_ACTIVE)->count(),
            'closed' => $classes->where('status', ClassRoom::STATUS_CLOSED)->count(),
        ];

        return [
            'summary' => [
                'total_classes' => $classes->count(),
                'active_classes' => $statusCount['active'],
                'pending_classes' => $statusCount['pending'],
                'closed_classes' => $statusCount['closed'],
            ],
            'status_chart' => $statusCount,
            'data' => $classes
        ];
    }

    /**
     * Thống kê Người dùng
     */
    public function getUserStats(array $filters): array
    {
        $query = User::with('major');
        $query = $this->applyFilters($query, $filters);

        if (!empty($filters['major_id'])) {
            $query->where('major_id', $filters['major_id']);
        }

        $users = $query->get();

        $roleCount = [
            'admin' => $users->where('role', User::ROLE_ADMIN)->count(),
            'teacher' => $users->where('role', User::ROLE_TEACHER)->count(),
            'student' => $users->where('role', User::ROLE_STUDENT)->count(),
        ];

        $studentByMajor = $users->where('role', User::ROLE_STUDENT)
            ->groupBy(fn($u) => $u->major?->major_name ?? 'Chưa phân ngành')
            ->map(fn($group) => $group->count());

        return [
            'summary' => [
                'total_users' => $users->count(),
                'total_students' => $roleCount['student'],
                'total_teachers' => $roleCount['teacher'],
                'total_admins' => $roleCount['admin'],
            ],
            'role_chart' => $roleCount,
            'major_chart' => $studentByMajor,
            'data' => $users
        ];
    }
}
