<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Subject;
use App\Services\Student\StudentAccessService;
use App\Services\Student\StudentMaterialService;
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
}
