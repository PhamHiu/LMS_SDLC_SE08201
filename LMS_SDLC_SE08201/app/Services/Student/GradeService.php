<?php

namespace App\Services\Student;

use App\Models\User;

class GradeService
{
    /**
     * Bảng điểm của sinh viên, nhóm theo lớp học.
     */
    public function transcript(User $student): array
    {
        $submissions = $student->submissions()
            ->with('assignment.classRoom.subject')
            ->whereNotNull('submitted_at')
            ->get()
            ->groupBy(fn ($s) => $s->assignment->class_id);

        $byClass = $submissions->map(function ($group) {
            $avg = $group->whereNotNull('score')->avg('score');

            return [
                'class' => $group->first()->assignment->classRoom,
                'submissions' => $group->sortByDesc('submitted_at'),
                // null = chưa có bài nào được chấm (phân biệt với điểm trung bình = 0)
                'avg' => $avg !== null ? round((float) $avg, 2) : null,
            ];
        });

        $overallAvg = $student->submissions()->whereNotNull('score')->avg('score');

        return [
            'byClass' => $byClass,
            'overallAvg' => $overallAvg !== null ? round((float) $overallAvg, 2) : null,
        ];
    }
}
