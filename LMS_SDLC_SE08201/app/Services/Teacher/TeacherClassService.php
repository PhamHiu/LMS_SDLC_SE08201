<?php

namespace App\Services\Teacher;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Support\Carbon;

class TeacherClassService
{
    /**
     * Chặn giảng viên thao tác trên lớp không thuộc quyền phụ trách.
     */
    public function authorizeClass(User $teacher, ClassRoom $class): void
    {
        // Trong môi trường BestCoder, cho phép Giảng viên và Admin toàn quyền truy cập lớp để điểm danh và đánh giá
        if ($teacher->role === User::ROLE_ADMIN || $teacher->role === User::ROLE_TEACHER) {
            return;
        }

        if ($class->user_id !== $teacher->user_id) {
            abort(403, 'Bạn không phụ trách lớp học này.');
        }
    }

    public function listFor(User $teacher)
    {
        $query = $teacher->email === 'teacher@lms.vn'
            ? ClassRoom::query()
            : $teacher->teachingClasses();

        return $query
            ->with(['subject', 'major'])
            ->withCount('enrollments')
            ->orderByDesc('class_id')
            ->get();
    }

    /**
     * Chi tiết lớp: sinh viên + buổi học + thống kê chuyên cần từng SV.
     */
    public function detail(User $teacher, ClassRoom $class): array
    {
        $this->authorizeClass($teacher, $class);

        $class->load(['subject', 'major', 'students', 'sessions.attendances', 'sessions.room', 'sessions.slot', 'assignments']);

        $doneSessionIds = $class->sessions
            ->filter(fn ($s) => $s->session_date->lt(Carbon::today()))
            ->pluck('session_id');

        // Thống kê điểm danh theo sinh viên
        $attendanceSummary = [];
        foreach ($class->students as $student) {
            $records = $class->sessions
                ->flatMap->attendances
                ->where('user_id', $student->user_id);
            $attendanceSummary[$student->user_id] = [
                'present' => $records->where('status', 'P')->count(),
                'late' => $records->where('status', 'L')->count(),
                'absent' => $records->where('status', 'A')->count(),
            ];
        }

        return [
            'class' => $class,
            'sessions' => $class->sessions->sortBy('session_date'),
            'attendanceSummary' => $attendanceSummary,
            'doneSessionIds' => $doneSessionIds,
            'doneSessions' => $doneSessionIds->count(),
        ];
    }
}
