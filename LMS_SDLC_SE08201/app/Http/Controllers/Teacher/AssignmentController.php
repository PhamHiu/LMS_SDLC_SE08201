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
        $teacher = $request->user();
        $classId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $subjectId = $request->filled('subject_id') ? (int) $request->query('subject_id') : null;
        $search = $request->filled('search') ? trim($request->query('search')) : null;

        $classesQuery = ($teacher->email === 'teacher@lms.vn' || $teacher->role === \App\Models\User::ROLE_ADMIN)
            ? ClassRoom::with('subject')
            : $teacher->teachingClasses()->with('subject');

        $classes = $classesQuery->orderBy('class_name')->get();
        $subjects = \App\Models\Subject::orderBy('subject_name')->get();

        return view('teacher.assignments.index', [
            'assignments' => $this->assignmentService->listFor($teacher, $classId, $subjectId, $search),
            'classes' => $classes,
            'subjects' => $subjects,
            'selectedClassId' => $classId,
            'selectedSubjectId' => $subjectId,
            'search' => $search,
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

        // Gửi thông báo đến sinh viên trong lớp khi giáo viên tạo / giao bài tập
        \App\Models\Notification::create([
            'user_id' => $request->user()->user_id,
            'class_id' => $class->class_id,
            'assignment_id' => $assignment->assignment_id,
            'title' => 'Bài tập mới: ' . $assignment->title,
            'body' => sprintf(
                'Thầy/Cô đã giao bài tập "%s" cho lớp %s. Hạn nộp: %s. Vui lòng bấm vào đây để xem chi tiết và nộp bài.',
                $assignment->title,
                $class->class_name,
                $assignment->due_date ? $assignment->due_date->format('H:i d/m/Y') : 'Không giới hạn'
            ),
        ]);

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

    /**
     * Sửa hạn nộp bài tập nhanh và thông báo gia hạn cho sinh viên (nếu cần).
     */
    public function extendDeadline(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'notify_students' => ['nullable', 'boolean'],
        ], [
            'due_date.required' => 'Vui lòng chọn hạn nộp mới.',
            'due_date.date' => 'Định dạng ngày giờ không hợp lệ.',
        ]);

        $newDueDate = \Carbon\Carbon::parse($validated['due_date']);
        $oldDueDate = $assignment->due_date ? $assignment->due_date->format('H:i d/m/Y') : 'Chưa có hạn';

        $assignment->update([
            'due_date' => $newDueDate,
        ]);

        // Nếu giáo viên tích chọn gửi thông báo gia hạn cho lớp
        if ($request->boolean('notify_students')) {
            \App\Models\Notification::create([
                'user_id' => $request->user()->user_id,
                'class_id' => $assignment->class_id,
                'assignment_id' => $assignment->assignment_id,
                'title' => 'Gia hạn nộp bài tập: ' . $assignment->title,
                'body' => sprintf(
                    'Bài tập "%s" lớp %s đã được Thầy/Cô gia hạn nộp đến: %s (Hạn cũ: %s). Bấm vào đây để vào nộp bài ngay nhé!',
                    $assignment->title,
                    $assignment->classRoom?->class_name ?? '',
                    $newDueDate->format('H:i d/m/Y'),
                    $oldDueDate
                ),
            ]);
        }

        return back()->with('success', 'Đã cập nhật hạn nộp bài tập đến ' . $newDueDate->format('H:i d/m/Y') . ' thành công!');
    }

    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);
        $this->assignmentService->delete($assignment);

        return redirect()->route('teacher.assignments.index')->with('success', 'Đã xóa bài tập.');
    }
}
