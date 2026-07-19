<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\GradeRequest;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\Teacher\AssignmentService;
use App\Services\Teacher\GradingService;
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

    /** Hiển thị giao diện chấm điểm chi tiết (PDF + Canvas) */
    public function show(Request $request, Submission $submission): View
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        return view('teacher.grading.show', [
            'submission' => $submission->load(['student', 'assignment.classRoom']),
            'assignment' => $submission->assignment,
        ]);
    }

    /** Tải file bài làm của sinh viên */
    public function download(Request $request, Submission $submission): StreamedResponse|RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        $studentFile = $submission->student_submit ?? $submission->submit_file_path;

        if (! $studentFile || ! Storage::disk('public')->exists($studentFile)) {
            return back()->with('error', 'File bài làm không tồn tại (dữ liệu demo).');
        }

        return Storage::disk('public')->download($studentFile);
    }

    /** Chấm điểm + nhận xét */
    public function grade(GradeRequest $request, Submission $submission): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $submission->assignment);

        $this->gradingService->grade($submission, $request->validated(), $request->file('other_file'));

        return back()->with('success', 'Đã chấm điểm cho '.$submission->student->full_name.'.');
    }
}
