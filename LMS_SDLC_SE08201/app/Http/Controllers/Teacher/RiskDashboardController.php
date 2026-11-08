<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use App\Services\AI\DropoutRiskService;
use App\Services\AI\RiskAnalyticsChartService;
use App\Services\Teacher\TeacherClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskDashboardController extends Controller
{
    public function __construct(
        private readonly DropoutRiskService $riskService,
        private readonly TeacherClassService $classService,
        private readonly RiskAnalyticsChartService $chartService,
    ) {
    }

    /**
     * Màn hình tổng quan cảnh báo nguy cơ học tập cho các lớp của giảng viên.
     */
    public function index(Request $request): View
    {
        $teacher = $request->user();
        $classes = $teacher->email === 'teacher@lms.vn'
            ? ClassRoom::with('subject')->orderBy('class_name')->get()
            : $teacher->teachingClasses()->with('subject')->get();

        $selectedClassId = $request->filled('class_id') ? (int) $request->class_id : $classes->first()?->class_id;

        $summary = null;
        $charts = null;
        if ($selectedClassId) {
            $class = ClassRoom::with('subject')->findOrFail($selectedClassId);
            $this->classService->authorizeClass($teacher, $class);
            $summary = $this->riskService->getClassRiskSummary($class);
            $charts = $this->chartService->buildTeacherCharts($class, $classes, $summary);
        }

        return view('teacher.risk.index', [
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'summary' => $summary,
            'charts' => $charts,
        ]);
    }

    /**
     * Xem hồ sơ chi tiết nguy cơ và giải thích theo từng môn của sinh viên.
     */
    public function show(Request $request, User $student): View
    {
        // Kiểm tra xem sinh viên có học ít nhất 1 lớp của giáo viên này không
        $teacher = $request->user();
        $isEnrolledInMyClass = $teacher->email === 'teacher@lms.vn' || $teacher->teachingClasses()
            ->whereHas('students', function ($q) use ($student) {
                $q->where('users.user_id', $student->user_id);
            })->exists();

        abort_unless($isEnrolledInMyClass, 403, 'Bạn không phụ trách học sinh này.');

        $prediction = $this->riskService->getLatestOrAnalyze($student);
        $interventions = $student->riskInterventions()->with('teacher')->latest()->get();
        $studentCharts = $this->chartService->buildStudentDetailCharts($student);

        return view('teacher.risk.show', [
            'student' => $student,
            'prediction' => $prediction,
            'interventions' => $interventions,
            'studentCharts' => $studentCharts,
        ]);
    }

    /**
     * Giảng viên ghi nhận hành động can thiệp sư phạm (Tư vấn, phụ đạo, hướng dẫn).
     */
    public function recordIntervention(Request $request, User $student): RedirectResponse
    {
        $teacher = $request->user();
        $isEnrolledInMyClass = $teacher->email === 'teacher@lms.vn' || $teacher->teachingClasses()
            ->whereHas('students', function ($q) use ($student) {
                $q->where('users.user_id', $student->user_id);
            })->exists();

        abort_unless($isEnrolledInMyClass, 403, 'Bạn không phụ trách học sinh này.');

        $request->validate([
            'action_type' => 'required|in:ACADEMIC_TUTORING,ATTENDANCE_REMINDER,COUNSELING,OTHER',
            'notes' => 'required|string|max:1000',
            'status' => 'required|in:PENDING,IN_PROGRESS,RESOLVED',
            'prediction_id' => 'nullable|exists:risk_predictions,prediction_id',
        ]);

        $this->riskService->recordIntervention($teacher, $student, $request->all());

        return back()->with('success', 'Đã ghi nhận hoạt động can thiệp hỗ trợ cho sinh viên ' . $student->full_name . '.');
    }
}
