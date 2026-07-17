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
use Illuminate\View\View;

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

        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('assignments/attachments', 'public');
        }
        $data['show_answer_after_submit'] = $request->has('show_answer_after_submit');

        $assignment = $this->assignmentService->create($class, $data);

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

    public function edit(Request $request, Assignment $assignment): View
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $assignment->load('questions');
        return view('teacher.assignments.edit', ['assignment' => $assignment]);
    }

    public function update(AssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('assignments/attachments', 'public');
        }
        $data['show_answer_after_submit'] = $request->has('show_answer_after_submit');

        $this->assignmentService->update($assignment, $data);

        return redirect()
            ->route('teacher.assignments.show', $assignment)
            ->with('success', 'Cập nhật bài tập "'.$assignment->title.'" thành công.');
    }

    public function generateQuiz(Request $request, \App\Services\Teacher\AiQuizService $aiService)
    {
        $request->validate([
            'topic' => 'required|string',
            'quantity' => 'required|integer|min:1|max:10',
            'reference_document' => 'nullable|file|mimes:txt,pdf,docx,doc|max:5120',
        ]);

        try {
            $filePath = null;
            $originalExtension = null;
            if ($request->hasFile('reference_document')) {
                $file = $request->file('reference_document');
                $filePath = $file->getRealPath();
                $originalExtension = $file->getClientOriginalExtension();
            }

            $questions = $aiService->generateQuestions(
                $request->input('topic'),
                $request->input('quantity'),
                $filePath,
                $originalExtension
            );
            return response()->json(['success' => true, 'questions' => $questions]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI generateQuiz Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);
        $this->assignmentService->delete($assignment);

        return redirect()->route('teacher.assignments.index')->with('success', 'Đã xóa bài tập.');
    }
}
