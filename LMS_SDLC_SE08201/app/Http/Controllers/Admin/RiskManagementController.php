<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Attendance\AbsenceStatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskManagementController extends Controller
{
    public function __construct(
        private readonly AbsenceStatisticsService $absenceStats,
    ) {
    }

    /**
     * Thống kê số buổi nghỉ học của học sinh theo môn học toàn trường.
     */
    public function index(Request $request): View
    {
        $selectedSubjectId = $request->filled('subject_id') ? (int) $request->query('subject_id') : null;
        $selectedStatus = (string) ($request->query('status') ?: 'ALL');
        $search = $request->query('q');

        $report = $this->absenceStats->getAdminSubjectAbsenceReport(
            subjectId: $selectedSubjectId,
            status: $selectedStatus,
            search: $search,
        );

        return view('admin.risk.index', [
            'rows' => $report['rows'],
            'counts' => $report['counts'],
            'subjects' => $report['subjects'],
            'selectedSubjectId' => $selectedSubjectId,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
        ]);
    }

    /**
     * Chi tiết số buổi nghỉ theo từng môn học của 1 học sinh.
     */
    public function show(User $student): View
    {
        $student->loadMissing('major');
        $breakdown = $this->absenceStats->getStudentSubjectBreakdown($student);
        $interventions = $student->riskInterventions()->with('teacher')->latest()->get();

        return view('admin.risk.show', [
            'student' => $student,
            'breakdown' => $breakdown,
            'interventions' => $interventions,
        ]);
    }
}
