<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EssaySubmissionRequest;
use App\Http\Requests\Student\QuizSubmissionRequest;
use App\Models\Assignment;
use App\Services\Student\StudentAccessService;
use App\Services\Student\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissionService,
        private readonly StudentAccessService $access,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $classIds = $this->access->enrolledClassIds($user);
        $enrolledClasses = \App\Models\ClassRoom::with('subject')->whereIn('class_id', $classIds)->get();
        
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $searchQuery = $request->query('search');

        return view('student.assignments.index', [
            'assignments' => $this->submissionService->assignmentsFor(
                $user, 
                $request->query('type'),
                $selectedClassId,
                $searchQuery
            ),
            'type' => $request->query('type'),
            'enrolledClasses' => $enrolledClasses,
            'selectedClassId' => $selectedClassId,
            'searchQuery' => $searchQuery,
        ]);
    }

    /** Chi tiết bài tập: form nộp tự luận / nút làm quiz / kết quả + thảo luận */
    public function show(Request $request, Assignment $assignment): View
    {
        $this->access->authorizeAssignment($request->user(), $assignment);

        $assignment->load(['classRoom.subject', 'rootDiscussions.author', 'rootDiscussions.replies.author']);

        return view('student.assignments.show', [
            'assignment' => $assignment,
            'submission' => $this->submissionService->submissionOf($request->user(), $assignment),
            'canViewAnswers' => $this->submissionService->canRevealAnswers($assignment),
        ]);
    }

    /** Nộp file bài tự luận */
    public function submitEssay(EssaySubmissionRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->access->authorizeAssignment($request->user(), $assignment);

        $this->submissionService->submitEssay($request->user(), $assignment, $request->file('file'));

        return back()->with('success', 'Nộp bài thành công lúc '.now()->format('H:i d/m/Y').'.');
    }

    /** Trang làm bài Quiz */
    public function takeQuiz(Request $request, Assignment $assignment): View|RedirectResponse
    {
        $this->access->authorizeAssignment($request->user(), $assignment);

        abort_unless($assignment->isQuiz(), 404);

        if ($this->submissionService->submissionOf($request->user(), $assignment)) {
            return redirect()
                ->route('student.assignments.show', $assignment)
                ->with('error', 'Bạn đã hoàn thành bài Quiz này.');
        }

        if ($assignment->isOverdue()) {
            return redirect()
                ->route('student.assignments.show', $assignment)
                ->with('error', 'Đã quá hạn làm bài.');
        }

        return view('student.assignments.quiz', [
            'assignment' => $assignment->load('questions'),
        ]);
    }

    /** Nộp bài Quiz — hệ thống tự chấm */
    public function submitQuiz(QuizSubmissionRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->access->authorizeAssignment($request->user(), $assignment);

        $submission = $this->submissionService->submitQuiz(
            $request->user(),
            $assignment,
            $request->validated('answers'),
        );

        return redirect()
            ->route('student.assignments.show', $assignment)
            ->with('success', "Hoàn thành bài Quiz! Điểm của bạn: {$submission->score}/{$assignment->max_score}.");
    }
}
