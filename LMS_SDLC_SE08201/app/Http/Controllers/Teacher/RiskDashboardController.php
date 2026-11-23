<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use App\Services\AI\DropoutRiskService;
use App\Services\Attendance\AbsenceStatisticsService;
use App\Services\Teacher\TeacherClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskDashboardController extends Controller
{
    public function __construct(
        private readonly AbsenceStatisticsService $absenceStats,
        private readonly TeacherClassService $classService,
        private readonly DropoutRiskService $riskService,
    ) {
    }

    /**
     * Thống kê số buổi nghỉ học của học sinh theo lớp / môn học của giảng viên.
     */
    public function index(Request $request): View
    {
        $teacher = $request->user();
        $classes = $teacher->email === 'teacher@lms.vn'
            ? ClassRoom::with('subject')->orderBy('class_name')->get()
            : $teacher->teachingClasses()->with('subject')->get();

        $selectedClassId = $request->filled('class_id') ? (int) $request->class_id : $classes->first()?->class_id;
        $selectedStatus = (string) ($request->query('status') ?: 'ALL');
        $search = $request->query('q');

        $report = null;
        if ($selectedClassId) {
            $class = ClassRoom::with('subject')->findOrFail($selectedClassId);
            $this->classService->authorizeClass($teacher, $class);
            $report = $this->absenceStats->getClassAbsenceReport($class, $selectedStatus, $search);
        }

        return view('teacher.risk.index', [
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
            'report' => $report,
        ]);
    }

    /**
     * Xem chi tiết số buổi nghỉ học theo từng môn của 1 học sinh.
     */
    public function show(Request $request, User $student): View
    {
        $teacher = $request->user();
        $isEnrolledInMyClass = $teacher->email === 'teacher@lms.vn' || $teacher->teachingClasses()
            ->whereHas('students', function ($q) use ($student) {
                $q->where('users.user_id', $student->user_id);
            })->exists();

        abort_unless($isEnrolledInMyClass, 403, 'Bạn không phụ trách học sinh này.');

        $student->loadMissing('major');
        $breakdown = $this->absenceStats->getStudentSubjectBreakdown($student);
        $interventions = $student->riskInterventions()->with('teacher')->latest()->get();

        return view('teacher.risk.show', [
            'student' => $student,
            'breakdown' => $breakdown,
            'interventions' => $interventions,
        ]);
    }

    /**
     * Giảng viên ghi nhận nhắc nhở / hỗ trợ học sinh.
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

        return back()->with('success', 'Đã lưu ghi chú hỗ trợ cho học sinh ' . $student->full_name . '.');
    }
}
