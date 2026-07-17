<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\MaterialRequest;
use App\Models\Material;
use App\Services\Teacher\MaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function __construct(private readonly MaterialService $materialService)
    {
    }

    public function index(Request $request): View
    {
        return view('teacher.materials.index', [
            'materials' => $this->materialService->listFor($request->user(), $request->query('subject_id')),
            'subjects' => $this->materialService->teachingSubjects($request->user()),
            'subjectId' => $request->query('subject_id'),
        ]);
    }

    public function store(MaterialRequest $request): RedirectResponse
    {
        $this->materialService->store(
            $request->user(),
            $request->safe()->except('file'),
            $request->file('file'),
        );

        return back()->with('success', 'Đăng tải học liệu thành công.');
    }

    public function destroy(Request $request, Material $material): RedirectResponse
    {
        $this->materialService->delete($request->user(), $material);

        return back()->with('success', 'Đã xóa học liệu.');
    }

    public function download(Request $request, Material $material): StreamedResponse|RedirectResponse
    {
        $this->materialService->authorizeDownload($request->user(), $material);

        if (! Storage::disk('public')->exists($material->file_path)) {
            return back()->with('error', 'File không tồn tại trên hệ thống (dữ liệu demo).');
        }

        return Storage::disk('public')->download(
            $material->file_path,
            $material->title.'.'.$material->extension(),
        );
    }
}
