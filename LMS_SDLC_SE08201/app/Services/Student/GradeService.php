<?php

namespace App\Services\Student;

use App\Models\User;
use App\Models\ClassRoom;
use Illuminate\Support\Collection;

class GradeService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    public function enrolledClassesForFilter(User $student): Collection
    {
        $classIds = $this->access->enrolledClassIds($student);
        return ClassRoom::with('subject')
            ->whereIn('class_id', $classIds)
            ->get();
    }

    /**
     * Bảng điểm của sinh viên, nhóm theo lớp học.
     */
    public function transcript(User $student, ?int $classId = null, ?string $search = null): array
    {
        $submissions = $student->submissions()
            ->with('assignment.classRoom.subject')
            ->whereNotNull('submitted_at')
            ->get();

        if ($classId) {
            $submissions = $submissions->filter(fn ($s) => $s->assignment->class_id == $classId);
        }

        if ($search) {
            $search = mb_strtolower($search);
            $submissions = $submissions->filter(function ($s) use ($search) {
                $subjectCode = mb_strtolower($s->assignment->classRoom->subject->subject_code ?? '');
                $subjectName = mb_strtolower($s->assignment->classRoom->subject->subject_name ?? '');
                $assignmentTitle = mb_strtolower($s->assignment->title ?? '');

                return str_contains($subjectCode, $search) ||
                       str_contains($subjectName, $search) ||
                       str_contains($assignmentTitle, $search);
            });
        }

        $byClass = $submissions->groupBy(fn ($s) => $s->assignment->class_id)
            ->map(fn ($group) => [
                'class' => $group->first()->assignment->classRoom,
                'submissions' => $group->sortByDesc('submitted_at'),
                'avg' => round((float) $group->whereNotNull('score')->avg('score'), 2),
            ])->values();

        return [
            'byClass' => $byClass,
        ];
    }
}
