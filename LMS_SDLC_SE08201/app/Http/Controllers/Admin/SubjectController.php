<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Major;
use App\Models\Subject;
use App\Services\Admin\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjectService)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.subjects.index', [
            'subjects' => $this->subjectService->paginate($request->query('search')),
            'search' => $request->query('search'),
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.form', [
            'subject' => null,
            'majors' => Major::orderBy('major_name')->get(),
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $this->subjectService->create($request->validated());

        return redirect()->route('admin.subjects.index')->with('success', 'Tạo môn học thành công.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.form', [
            'subject' => $subject->load('majors'),
            'majors' => Major::orderBy('major_name')->get(),
        ]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->subjectService->update($subject, $request->validated());

        return redirect()->route('admin.subjects.index')->with('success', 'Cập nhật môn học thành công.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->subjectService->delete($subject);

        return back()->with('success', 'Đã xóa môn học.');
    }
}
