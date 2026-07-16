<?php

namespace App\Services\Admin;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubjectService
{
    public function paginate(?string $search = null, ?int $majorId = null): LengthAwarePaginator
    {
        return Subject::with('majors')->withCount('classes')
            ->when($majorId, function ($q) use ($majorId) {
                $q->whereHas('majors', function ($q2) use ($majorId) {
                    $q2->where('majors.major_id', $majorId);
                });
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('subject_name', 'like', "%{$search}%")
                        ->orWhere('subject_code', 'like', "%{$search}%")
                        ->orWhereHas('majors', function ($q3) use ($search) {
                            $q3->where('major_name', 'like', "%{$search}%")
                               ->orWhere('major_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('classes', function ($q4) use ($search) {
                            $q4->where('class_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('subject_code')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Tạo môn học + gán vào các chuyên ngành (bảng majors_subjects).
     */
    public function create(array $data): Subject
    {
        $subject = Subject::create($data);
        $subject->majors()->sync($data['major_ids'] ?? []);

        return $subject;
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($data);
        $subject->majors()->sync($data['major_ids'] ?? []);

        return $subject;
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }
}
