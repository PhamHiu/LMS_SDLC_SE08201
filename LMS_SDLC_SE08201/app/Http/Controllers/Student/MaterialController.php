<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Subject;
use App\Services\AI\VietnameseSummarizerService;
use App\Services\Student\StudentAccessService;
use App\Services\Student\StudentMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function __construct(
        private readonly StudentMaterialService $materialService,
        private readonly StudentAccessService $access,
        private readonly VietnameseSummarizerService $summarizer,
    ) {
    }

    public function index(Request $request): View
    {
        $subjectIds = $this->access->enrolledSubjectIds($request->user());
        $searchQuery = $request->query('search');

        return view('student.materials.index', [
            'materials' => $this->materialService->listFor($request->user(), $request->query('subject_id'), $searchQuery),
            'subjects' => Subject::whereIn('subject_id', $subjectIds)->orderBy('subject_name')->get(),
            'subjectId' => $request->query('subject_id'),
            'searchQuery' => $searchQuery,
        ]);
    }

    public function download(Request $request, Material $material): StreamedResponse|RedirectResponse
    {
        $this->materialService->authorizeMaterial($request->user(), $material);

        if (! Storage::disk('public')->exists($material->file_path)) {
            return back()->with('error', 'File không tồn tại trên hệ thống (dữ liệu demo).');
        }

        return Storage::disk('public')->download(
            $material->file_path,
            $material->title.'.'.$material->extension(),
        );
    }

    /** Tóm tắt 1 tài liệu học cụ thể bằng ViT5 / AI Summarizer */
    public function summarize(Request $request, Material $material): JsonResponse
    {
        $this->materialService->authorizeMaterial($request->user(), $material);
        $material->loadMissing('subject');

        $result = $this->summarizer->summarizeMaterial($material);

        return response()->json([
            'title' => $material->title,
            'subject' => $material->subject?->subject_name,
            'engine' => $result['engine'],
            'summary' => $result['summary'],
        ]);
    }

    /** Tóm tắt tổng hợp toàn bộ tài liệu học theo Môn */
    public function summarizeSubject(Request $request, Subject $subject): JsonResponse
    {
        $enrolledSubjectIds = $this->access->enrolledSubjectIds($request->user());
        abort_unless($enrolledSubjectIds->contains($subject->subject_id), 403, 'Bạn chưa ghi danh môn học này.');

        $result = $this->summarizer->summarizeSubjectMaterials($subject);

        return response()->json([
            'title' => "Tổng hợp tài liệu môn {$subject->subject_name} ({$subject->subject_code})",
            'subject' => $subject->subject_name,
            'material_count' => $result['material_count'] ?? 0,
            'engine' => $result['engine'],
            'summary' => $result['summary'],
        ]);
    }
}
