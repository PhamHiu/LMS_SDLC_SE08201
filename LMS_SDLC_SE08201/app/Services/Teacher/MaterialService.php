<?php

namespace App\Services\Teacher;

use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MaterialService
{
    /**
     * Các môn học thuộc những lớp giảng viên đang phụ trách.
     */
    public function teachingSubjects(User $teacher)
    {
        $subjectIds = $teacher->teachingClasses()->pluck('subject_id')->unique();

        return Subject::whereIn('subject_id', $subjectIds)->orderBy('subject_name')->get();
    }

    /**
     * Danh sách học liệu của các môn giảng viên dạy (kèm học liệu GV khác đăng cùng môn).
     */
    public function listFor(User $teacher, ?int $subjectId = null)
    {
        $subjectIds = $teacher->teachingClasses()->pluck('subject_id')->unique();

        return Material::with(['subject', 'uploader'])
            ->whereIn('subject_id', $subjectIds)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->orderByDesc('material_id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Upload file học liệu qua Laravel Storage (disk public).
     */
    public function store(User $teacher, array $data, UploadedFile $file): Material
    {
        $path = $file->store('materials', 'public');

        return Material::create([
            'subject_id' => $data['subject_id'],
            'user_id' => $teacher->user_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
        ]);
    }

    /**
     * Giảng viên chỉ được tải học liệu thuộc môn của lớp mình phụ trách
     * (chống IDOR khi đổi {material} trên URL).
     */
    public function authorizeDownload(User $teacher, Material $material): void
    {
        $subjectIds = $teacher->teachingClasses()->pluck('subject_id')->unique();

        abort_unless(
            $subjectIds->contains($material->subject_id),
            403,
            'Học liệu không thuộc môn bạn phụ trách.'
        );
    }

    public function delete(User $teacher, Material $material): void
    {
        if ($material->user_id !== $teacher->user_id) {
            abort(403, 'Bạn chỉ có thể xóa học liệu do mình đăng tải.');
        }

        Storage::disk('public')->delete($material->file_path);
        $material->delete();
    }
}
