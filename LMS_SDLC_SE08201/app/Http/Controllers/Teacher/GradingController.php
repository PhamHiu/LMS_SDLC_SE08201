<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\GradeRequest;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\Teacher\AssignmentService;
use App\Services\Teacher\GradingService;
use App\Models\SubmissionComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradingController extends Controller
{
    public function __construct(
        private readonly GradingService $gradingService,
        private readonly AssignmentService $assignmentService,
    ) {
    }

    /** Danh sách bài nộp của một bài tập */
    public function index(Request $request, Assignment $assignment): View
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        return view('teacher.grading.index', array_merge(
            ['assignment' => $assignment->load('classRoom')],
            $this->gradingService->submissionsOverview($assignment),
        ));
    }

    /** Tải file bài làm của sinh viên */
    public function download(Request $request, Submission $submission): StreamedResponse|RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        if (! $submission->submit_file_path || ! Storage::disk('public')->exists($submission->submit_file_path)) {
            return back()->with('error', 'File bài làm không tồn tại (dữ liệu demo).');
        }

        return Storage::disk('public')->download($submission->submit_file_path);
    }

    public function grade(GradeRequest $request, Submission $submission): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        $this->gradingService->grade($submission, $request->validated(), $request->file('result_file'));

        // Save standalone drawings annotations if present in the request
        if ($request->has('page_annotations')) {
            Storage::disk('public')->put(
                'results/annotations_' . $submission->submission_id . '.json',
                $request->input('page_annotations')
            );
        }

        return back()->with('success', 'Đã chấm điểm cho '.$submission->student->full_name.'.');
    }

    /** View detailed grading interface */
    public function show(Request $request, Submission $submission): View
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        $submission->load(['student', 'assignment.classRoom', 'comments.user']);

        // Load standalone drawings annotations if file exists
        $savedAnnotations = [];
        $annotPath = 'results/annotations_' . $submission->submission_id . '.json';
        if (Storage::disk('public')->exists($annotPath)) {
            $savedAnnotations = json_decode(Storage::disk('public')->get($annotPath), true);
        }

        return view('teacher.grading.show', [
            'submission' => $submission,
            'assignment' => $submission->assignment,
            'savedAnnotations' => $savedAnnotations,
        ]);
    }

    /** Add comment to a submission */
    public function comment(Request $request, Submission $submission): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        $request->validate(['content' => 'required|string|max:1000']);

        SubmissionComment::create([
            'submission_id' => $submission->submission_id,
            'user_id' => $request->user()->user_id,
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Đã thêm nhận xét trao đổi.');
    }

    /** Delete a grading selection comment */
    public function deleteComment(Request $request, SubmissionComment $comment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $comment->submission->assignment);
        $comment->delete();
        return back()->with('success', 'Đã xóa nhận xét thành công.');
    }
}
