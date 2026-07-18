<?php

namespace App\Services\Student;

use App\Models\Material;
use App\Models\User;

class StudentMaterialService
{
    public function __construct(private readonly StudentAccessService $access)
    {
    }

    /**
     * Học liệu các môn thuộc lớp sinh viên đã ghi danh.
     */
    public function listFor(User $student, ?int $subjectId = null, ?string $search = null)
    {
        $subjectIds = $this->access->enrolledSubjectIds($student);

        $query = Material::with(['subject', 'uploader'])
            ->whereIn('subject_id', $subjectIds)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhereHas('subject', function ($q2) use ($search) {
                      $q2->where('subject_code', 'LIKE', "%{$search}%")
                         ->orWhere('subject_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        return $query->orderByDesc('material_id')
            ->paginate(12)
            ->withQueryString();
    }

    /** Chặn tải học liệu của môn chưa ghi danh */
    public function authorizeMaterial(User $student, Material $material): void
    {
        if (! $this->access->enrolledSubjectIds($student)->contains($material->subject_id)) {
            abort(403, 'Bạn chưa ghi danh môn học chứa học liệu này.');
        }
    }
}
