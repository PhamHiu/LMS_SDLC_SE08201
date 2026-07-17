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
        return view('student.assignments.index', [
            'assignments' => $this->submissionService->assignmentsFor($request->user(), $request->query('type')),
            'type' => $request->query('type'),
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

    /** Xem trước bài nộp */
    public function previewSubmission(Request $request, \App\Models\Submission $submission): View
    {
        $user = $request->user();
        if ($user->role === 'STUDENT' && $submission->user_id !== $user->user_id) {
            abort(403);
        }
        if ($user->role === 'TEACHER' && $submission->assignment->classRoom->user_id !== $user->user_id) {
            abort(403);
        }

        $type = $request->query('type', 'submit');
        $filePath = $type === 'result' ? $submission->result_file_path : $submission->submit_file_path;
        abort_unless($filePath, 404);

        $filename = basename($filePath);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fileType = 'other';
        if (in_array($ext, ['pdf'])) {
            $fileType = 'pdf';
        } elseif (in_array($ext, ['docx'])) {
            $fileType = 'docx';
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            $fileType = 'image';
        }

        return view('shared.preview', [
            'filename' => $filename,
            'fileType' => $fileType,
            'fileUrl' => route('student.submissions.file', ['submission' => $submission, 'type' => $type]),
            'downloadUrl' => route('student.submissions.download-raw', ['submission' => $submission, 'type' => $type]),
        ]);
    }

    /** Phục vụ file từ Storage */
    public function serveSubmissionFile(Request $request, \App\Models\Submission $submission)
    {
        $user = $request->user();
        if ($user->role === 'STUDENT' && $submission->user_id !== $user->user_id) {
            abort(403);
        }
        if ($user->role === 'TEACHER' && $submission->assignment->classRoom->user_id !== $user->user_id) {
            abort(403);
        }

        $type = $request->query('type', 'submit');
        $filePath = $type === 'result' ? $submission->result_file_path : $submission->submit_file_path;
        abort_unless($filePath && \Illuminate\Support\Facades\Storage::exists($filePath), 404);

        return response()->file(storage_path('app/' . $filePath));
    }

    /** Tải file thô */
    public function downloadSubmissionFileRaw(Request $request, \App\Models\Submission $submission)
    {
        $user = $request->user();
        if ($user->role === 'STUDENT' && $submission->user_id !== $user->user_id) {
            abort(403);
        }
        if ($user->role === 'TEACHER' && $submission->assignment->classRoom->user_id !== $user->user_id) {
            abort(403);
        }

        $type = $request->query('type', 'submit');
        $filePath = $type === 'result' ? $submission->result_file_path : $submission->submit_file_path;
        abort_unless($filePath && \Illuminate\Support\Facades\Storage::exists($filePath), 404);

        return response()->download(storage_path('app/' . $filePath));
    }
}
