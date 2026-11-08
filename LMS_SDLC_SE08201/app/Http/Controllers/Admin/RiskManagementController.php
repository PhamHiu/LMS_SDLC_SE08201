<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AI\DropoutRiskService;
use App\Services\AI\RiskAnalyticsChartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskManagementController extends Controller
{
    public function __construct(
        private readonly DropoutRiskService $riskService,
        private readonly RiskAnalyticsChartService $chartService,
    ) {
    }

    /**
     * Màn hình quản trị trung tâm: Phân tích nguy cơ bỏ học toàn trường (Early Warning System).
     */
    public function index(Request $request): View
    {
        $overview = $this->riskService->getAdminOverview();
        $selectedLevel = $request->query('level');

        $filteredStudents = collect($overview['students']);
        if ($selectedLevel && $selectedLevel !== 'ALL') {
            $filteredStudents = $filteredStudents->filter(function ($item) use ($selectedLevel) {
                return $item['prediction']->risk_level === $selectedLevel;
            });
        }

        $charts = $this->chartService->buildAdminCharts($overview, $selectedLevel ?? 'ALL');

        return view('admin.risk.index', [
            'overview' => $overview,
            'filteredStudents' => $filteredStudents,
            'selectedLevel' => $selectedLevel ?? 'ALL',
            'charts' => $charts,
        ]);
    }

    /**
     * Chi tiết hồ sơ cảnh báo toàn diện của sinh viên từ góc nhìn Admin.
     */
    public function show(User $student): View
    {
        $prediction = $this->riskService->getLatestOrAnalyze($student);
        $interventions = $student->riskInterventions()->with('teacher')->latest()->get();
        $studentCharts = $this->chartService->buildStudentDetailCharts($student);

        return view('admin.risk.show', [
            'student' => $student,
            'prediction' => $prediction,
            'interventions' => $interventions,
            'studentCharts' => $studentCharts,
        ]);
    }
}

