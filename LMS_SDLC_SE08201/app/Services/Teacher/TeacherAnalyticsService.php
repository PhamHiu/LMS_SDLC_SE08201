<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TeacherAnalyticsService
{
    /**
     * Thống kê Điểm số (chỉ lấy lớp do teacher giảng dạy)
     */
    public function getGradeStats(User $teacher, array $filters): array
    {
        $query = Submission::whereNotNull('score')
            ->with(['assignment.classRoom.subject', 'student'])
            ->whereHas('assignment.classRoom', function ($q) use ($teacher) {
                $q->where('user_id', $teacher->user_id);
            });

        if (!empty($filters['date_from'])) {
            $query->where('graded_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('graded_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['class_id'])) {
            $query->whereHas('assignment', fn($q) => $q->where('class_id', $filters['class_id']));
        }

        $submissions = $query->get();

        $avgScore = $submissions->avg('score');
        $maxScore = $submissions->max('score');
        $minScore = $submissions->min('score');
        $totalGraded = $submissions->count();

        // Phân bố điểm số
        $distribution = [
            '0-2'  => $submissions->where('score', '>=', 0)->where('score', '<', 2)->count(),
            '2-4'  => $submissions->where('score', '>=', 2)->where('score', '<', 4)->count(),
            '4-6'  => $submissions->where('score', '>=', 4)->where('score', '<', 6)->count(),
            '6-8'  => $submissions->where('score', '>=', 6)->where('score', '<', 8)->count(),
            '8-10' => $submissions->where('score', '>=', 8)->where('score', '<=', 10)->count(),
        ];

        return [
            'summary' => [
                'avg_score' => $avgScore ? round($avgScore, 2) : 0,
                'max_score' => $maxScore ?? 0,
                'min_score' => $minScore ?? 0,
                'total_graded' => $totalGraded,
            ],
            'distribution' => $distribution,
            'data' => $submissions
        ];
    }

    /**
     * Thống kê Chuyên cần (chỉ lấy lớp do teacher giảng dạy)
     */
    public function getAttendanceStats(User $teacher, array $filters): array
    {
        $query = Attendance::with(['student', 'session.classRoom.subject'])
            ->whereHas('session.classRoom', function ($q) use ($teacher) {
                $q->where('user_id', $teacher->user_id);
            });

        if (!empty($filters['date_from'])) {
            $query->where('attendance_time', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('attendance_time', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['class_id'])) {
            $query->whereHas('session', fn($q) => $q->where('class_id', $filters['class_id']));
        }

        $attendances = $query->get();
        $total = $attendances->count();

        $present = $attendances->where('status', 'P')->count();
        $absent = $attendances->where('status', 'A')->count();
        $late = $attendances->where('status', 'L')->count();

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
     * Lấy tổng quan các lớp học của giảng viên
     */
    public function getClassOverview(User $teacher): array
    {
        $classes = ClassRoom::where('user_id', $teacher->user_id)
            ->withCount('students')
            ->get();
            
        return [
            'total_classes' => $classes->count(),
            'classes' => $classes
        ];
    }
}
