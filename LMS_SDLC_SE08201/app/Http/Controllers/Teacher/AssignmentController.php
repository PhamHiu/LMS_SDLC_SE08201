<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AssignmentRequest;
use App\Models\Assignment;
use App\Models\ClassRoom;
use App\Services\Teacher\AssignmentService;
use App\Services\Teacher\TeacherClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignmentService,
        private readonly TeacherClassService $classService,
    ) {
    }

    public function index(Request $request): View
    {
        return view('teacher.assignments.index', [
            'assignments' => $this->assignmentService->listFor($request->user()),
            'classes' => $request->user()->teachingClasses()->with('subject')->get(),
        ]);
    }

    public function create(Request $request, ClassRoom $class): View
    {
        $this->classService->authorizeClass($request->user(), $class);

        return view('teacher.assignments.create', ['class' => $class]);
    }

    public function store(AssignmentRequest $request, ClassRoom $class): RedirectResponse
    {
        $this->classService->authorizeClass($request->user(), $class);

        // ( Đăng sửa ) Truyền thêm request->file('attachment_file') vào hàm create
        $assignment = $this->assignmentService->create(
            $class, 
            $request->validated(), 
            $request->file('attachment_file')
        );

        return redirect()
            ->route('teacher.assignments.show', $assignment)
            ->with('success', 'Tạo bài tập "'.$assignment->title.'" thành công.');
    }

    /** Chi tiết bài tập: câu hỏi (nếu quiz) + thảo luận */
    public function show(Request $request, Assignment $assignment): View
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $assignment->load([
            'classRoom.subject',
            'questions',
            'rootDiscussions.author',
            'rootDiscussions.replies.author',
        ]);

        return view('teacher.assignments.show', [
            'assignment' => $assignment,
            'submissionCount' => $assignment->submissions()->whereNotNull('submitted_at')->count(),
            'studentCount' => $assignment->classRoom->students()->count(),
        ]);
    }

    // ( Đăng thêm ) Hàm hiển thị trang sửa bài tập
    public function edit(Request $request, Assignment $assignment): View
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);
        
        $assignment->load('questions');

        return view('teacher.assignments.edit', [
            'assignment' => $assignment,
            'class' => $assignment->classRoom,
        ]);
    }

    // ( Đăng thêm ) Hàm cập nhật bài tập và lưu file tải lên
    public function update(AssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $this->assignmentService->update(
            $assignment, 
            $request->validated(), 
            $request->file('attachment_file')
        );

        return redirect()
            ->route('teacher.assignments.show', $assignment)
            ->with('success', 'Đã cập nhật bài tập thành công.');
    }

    // ( Đăng thêm ) Hàm tải xuống tài liệu đính kèm
    public function downloadAttachment(Request $request, Assignment $assignment): StreamedResponse|RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        if (! $assignment->attachment_path || ! Storage::disk('public')->exists($assignment->attachment_path)) {
            return back()->with('error', 'File đính kèm không tồn tại.');
        }

        return Storage::disk('public')->download($assignment->attachment_path);
    }

    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);
        $this->assignmentService->delete($assignment);

        return redirect()->route('teacher.assignments.index')->with('success', 'Đã xóa bài tập.');
    }
}
