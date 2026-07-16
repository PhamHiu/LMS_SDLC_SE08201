<?php

namespace App\Services\Admin;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubjectService
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Subject::with('majors')->withCount('classes')
            ->when($search, fn ($q) => $q->where('subject_name', 'like', "%{$search}%")
                ->orWhere('subject_code', 'like', "%{$search}%"))
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
