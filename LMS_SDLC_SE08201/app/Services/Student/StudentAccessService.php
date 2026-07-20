<?php

namespace App\Services\Student;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentAccessService
{
    /** ID các lớp sinh viên đã ghi danh */
    public function enrolledClassIds(User $student): Collection
    {
        return $student->enrollments()->pluck('class_id');
    }

    /** ID các môn học thuộc lớp đã ghi danh */
    public function enrolledSubjectIds(User $student): Collection
    {
        return $student->enrolledClasses()->pluck('classes.subject_id')->unique()->values();
    }

    /** Chặn sinh viên truy cập bài tập của lớp chưa ghi danh */
    public function authorizeAssignment(User $student, Assignment $assignment): void
    {
        if (! $this->enrolledClassIds($student)->contains($assignment->class_id)) {
            abort(403, 'Bạn chưa ghi danh vào lớp học chứa bài tập này.');
        }
    }
}
