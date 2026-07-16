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
        if ($class->user_id !== $teacher->user_id) {
            abort(403, 'Bạn không phụ trách lớp học này.');
        }
    }

    public function listFor(User $teacher)
    {
        return $teacher->teachingClasses()
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

        $class->load(['subject', 'major', 'students', 'sessions.attendances', 'assignments']);

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
                'absent' => $records->where('status', 'A')->count(),
                'late' => $records->where('status', 'L')->count(),
            ];
        }

        return [
            'class' => $class,
            'attendanceSummary' => $attendanceSummary,
            'doneSessions' => $doneSessionIds->count(),
        ];
    }
}
