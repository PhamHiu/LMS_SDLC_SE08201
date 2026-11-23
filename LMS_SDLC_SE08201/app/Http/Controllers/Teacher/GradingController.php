<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\GradeRequest;
use App\Models\Assignment;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
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

    /**
     * Công bố điểm bài tự luận cho toàn bộ lớp và gửi thông báo tới học sinh.
     */
    public function publishGrades(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        if (! $assignment->isEssay()) {
            return back()->with('info', 'Bài trắc nghiệm đã tự động hiển thị điểm ngay sau khi nộp.');
        }

        $assignment->loadMissing('classRoom.subject');
        $gradedCount = $assignment->submissions()->whereNotNull('score')->count();

        if ($gradedCount === 0) {
            return back()->with('error', 'Chưa có bài nộp nào được chấm điểm. Vui lòng chấm ít nhất 1 bài trước khi công bố điểm của lớp.');
        }

        $assignment->update([
            'grades_published_at' => now(),
        ]);

        $className = $assignment->classRoom?->class_name ?? '';
        $subjectName = $assignment->classRoom?->subject?->subject_name ?? '';

        Notification::create([
            'user_id' => $request->user()->user_id,
            'class_id' => $assignment->class_id,
            'assignment_id' => $assignment->assignment_id,
            'title' => 'Đã công bố điểm bài tự luận: ' . $assignment->title,
            'body' => sprintf(
                'Giảng viên %s đã công bố điểm bài tự luận "%s" (%s — lớp %s, %d bài đã chấm). Các em bấm vào thông báo để xem điểm số và nhận xét chi tiết.',
                $request->user()->full_name,
                $assignment->title,
                $subjectName,
                $className,
                $gradedCount
            ),
            'target_url' => route('student.assignments.show', $assignment),
        ]);

        return back()->with('success', "Đã công bố điểm bài tự luận \"{$assignment->title}\" cho lớp {$className} và gửi thông báo đến học sinh!");
    }

    /**
     * Gửi thông báo nhắc nhở nộp bài đến toàn bộ sinh viên chưa nộp trong lớp.
     */
    public function remind(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $overview = $this->gradingService->submissionsOverview($assignment);
        $notSubmitted = $overview['notSubmitted'];

        if ($notSubmitted->isEmpty()) {
            return back()->with('info', 'Tất cả sinh viên trong lớp đã hoàn thành nộp bài tập này!');
        }

        $dueDateStr = $assignment->due_date ? $assignment->due_date->format('H:i d/m/Y') : 'sớm nhất có thể';
        $title = 'Nhắc nhở nộp bài: ' . $assignment->title;
        $body = sprintf(
            'Thầy/Cô nhắc nhở các bạn chưa nộp bài tập "%s" (%s) thuộc lớp %s. Hạn chót: %s. Hiện tại lớp còn %d bạn chưa nộp bài, các em khẩn trương hoàn thành và nộp bài trên hệ thống nhé!',
            $assignment->title,
            $assignment->typeLabel(),
            $assignment->classRoom?->class_name ?? '',
            $dueDateStr,
            $notSubmitted->count()
        );

        // Bảng notifications có unique(assignment_id) để chống trùng
        Notification::updateOrCreate(
            ['assignment_id' => $assignment->assignment_id],
            [
                'user_id' => $request->user()->user_id,
                'class_id' => $assignment->class_id,
                'title' => $title,
                'body' => $body,
            ]
        );

        return back()->with('success', "Đã gửi thông báo nhắc nhở nộp bài tập đến toàn bộ {$notSubmitted->count()} sinh viên chưa nộp bài trong lớp!");
    }

    /**
     * Gửi thông báo nhắc nhở cá nhân cho một sinh viên cụ thể chưa nộp bài.
     */
    public function remindStudent(Request $request, Assignment $assignment, User $student): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $dueDateStr = $assignment->due_date ? $assignment->due_date->format('H:i d/m/Y') : 'sớm nhất có thể';
        $title = 'Nhắc nhở nộp bài: ' . $assignment->title;
        $body = sprintf(
            'Chào bạn %s, Thầy/Cô nhắc bạn chưa hoàn thành nộp bài tập "%s" cho lớp %s. Hạn chót nộp bài: %s. Bạn vui lòng hoàn thành và nộp bài sớm nhé!',
            $student->full_name,
            $assignment->title,
            $assignment->classRoom?->class_name ?? '',
            $dueDateStr
        );

        // Lưu thông báo nhắc nhở kèm assignment_id để học sinh bấm vào sẽ đến ngay bài tập
        Notification::create([
            'user_id' => $request->user()->user_id,
            'class_id' => $assignment->class_id,
            'assignment_id' => $assignment->assignment_id,
            'title' => $title,
            'body' => $body,
        ]);

        $studentIdentifier = $student->student_code ?: $student->email;

        return back()->with('success', "Đã gửi thông báo nhắc nhở nộp bài đến sinh viên {$student->full_name} ({$studentIdentifier})!");
    }
}
