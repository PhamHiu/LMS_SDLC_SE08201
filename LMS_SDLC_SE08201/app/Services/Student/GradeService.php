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

        $byClass = $submissions->map(fn ($group) => [
            'class' => $group->first()->assignment->classRoom,
            'submissions' => $group->sortByDesc('submitted_at'),
            'avg' => round((float) $group->whereNotNull('score')->avg('score'), 2),
        ]);

        return [
            'byClass' => $byClass,
            'overallAvg' => round((float) $student->submissions()->whereNotNull('score')->avg('score'), 2),
        ];
    }
}
