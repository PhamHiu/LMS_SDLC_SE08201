<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\RiskPrediction;
use App\Models\User;
use App\Services\AI\DropoutRiskRuleEngine;
use App\Services\AI\DropoutRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskApiController extends Controller
{
    public function __construct(
        private readonly DropoutRiskService $riskService,
        private readonly DropoutRiskRuleEngine $ruleEngine
    ) {
    }

    /**
     * GET /api/teacher/students/{id}/risk
     * Đánh giá nguy cơ bỏ học của 1 sinh viên.
     */
    public function studentRisk(int $id): JsonResponse
    {
        $student = User::where('role', User::ROLE_STUDENT)->find($id);

        if (! $student) {
            return response()->json(['error' => 'Sinh viên không tồn tại'], 404);
        }

        $prediction = $this->riskService->getLatestOrAnalyze($student);
        $features = $this->ruleEngine->extractFeatures($student);

        return response()->json([
            'student_id' => $student->student_code ?? ('STU_' . $student->user_id),
            'student_name' => $student->full_name,
            'analysis_date' => $prediction->analyzed_at->toIso8601String(),
            'analysis_method' => $prediction->analysis_method,
            'risk_level' => $prediction->risk_level,
            'risk_score' => (float) $prediction->risk_score,
            'data_quality_status' => $prediction->data_quality_status,
            'model_version' => $prediction->model_version,
            'features' => $features,
            'contributing_factors' => $prediction->factors->map(fn ($f) => [
                'factor_type' => $f->factor_type,
                'title' => $f->title,
                'description' => $f->description,
                'severity' => $f->severity,
                'impact_weight' => (float) $f->impact_weight,
            ]),
            'explanation' => $prediction->summary_explanation,
            'recommendations' => $prediction->recommendations,
        ]);
    }

    /**
     * GET /api/teacher/classes/{id}/risk-summary
     * Thống kê nguy cơ của toàn bộ học sinh trong 1 lớp học.
     */
    public function classRiskSummary(int $id): JsonResponse
    {
        $class = ClassRoom::with(['subject', 'students'])->find($id);

        if (! $class) {
            return response()->json(['error' => 'Lớp học không tồn tại'], 404);
        }

        $summary = $this->riskService->getClassRiskSummary($class);

        return response()->json([
            'class_id' => $class->class_id,
            'class_name' => $class->class_name,
            'subject_name' => $class->subject?->subject_name ?? '—',
            'students_count' => $summary['students_count'],
            'students' => collect($summary['students_data'])->map(function ($item) {
                return [
                    'user_id' => $item['student']->user_id,
                    'full_name' => $item['student']->full_name,
                    'student_code' => $item['student']->student_code,
                    'risk_level' => $item['overall_risk']->risk_level,
                    'risk_score' => (float) $item['overall_risk']->risk_score,
                    'absence_rate' => $item['subject_detail']['absence_rate'],
                    'absent_sessions' => $item['subject_detail']['absent_sessions'],
                    'total_sessions' => $item['subject_detail']['total_sessions'],
                    'attendance_failed' => $item['subject_detail']['attendance_failed'],
                    'subject_status' => $item['subject_detail']['status'],
                ];
            }),
        ]);
    }

    /**
     * GET /api/admin/risk/dashboard
     * Thống kê tổng quan toàn trường cho ban quản trị.
     */
    public function adminDashboard(): JsonResponse
    {
        $overview = $this->riskService->getAdminOverview();

        return response()->json([
            'total_students' => $overview['total_students'],
            'counts_by_level' => $overview['counts_by_level'],
            'percentage_high_risk' => $overview['total_students'] > 0
                ? round((($overview['counts_by_level'][RiskPrediction::LEVEL_HIGH] + $overview['counts_by_level'][RiskPrediction::LEVEL_VERY_HIGH]) / $overview['total_students']) * 100, 1)
                : 0.0,
            'students' => collect($overview['students'])->map(function ($item) {
                return [
                    'user_id' => $item['student']->user_id,
                    'student_code' => $item['student']->student_code,
                    'full_name' => $item['student']->full_name,
                    'risk_level' => $item['prediction']->risk_level,
                    'risk_score' => (float) $item['prediction']->risk_score,
                    'analysis_method' => $item['prediction']->analysis_method,
                    'analyzed_at' => $item['prediction']->analyzed_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * GET /api/students/{id}/attendance-summary
     * Thống kê chi tiết chuyên cần theo từng môn.
     */
    public function studentAttendanceSummary(int $id): JsonResponse
    {
        $student = User::where('role', User::ROLE_STUDENT)->find($id);

        if (! $student) {
            return response()->json(['error' => 'Sinh viên không tồn tại'], 404);
        }

        $enrolledClasses = $student->enrolledClasses()->with(['subject', 'sessions.attendances'])->get();
        $subjects = [];

        foreach ($enrolledClasses as $class) {
            $analysis = $this->ruleEngine->analyzeSubject($student, $class);
            $subjects[] = $analysis;
        }

        return response()->json([
            'student_id' => $student->student_code ?? ('STU_' . $student->user_id),
            'student_name' => $student->full_name,
            'subjects' => $subjects,
        ]);
    }

    /**
     * GET /api/students/{id}/academic-history
     * Lịch sử học tập, nợ môn, điểm trung bình.
     */
    public function studentAcademicHistory(int $id): JsonResponse
    {
        $student = User::where('role', User::ROLE_STUDENT)->find($id);

        if (! $student) {
            return response()->json(['error' => 'Sinh viên không tồn tại'], 404);
        }

        $features = $this->ruleEngine->extractFeatures($student);

        return response()->json([
            'student_id' => $student->student_code ?? ('STU_' . $student->user_id),
            'student_name' => $student->full_name,
            'academic_metrics' => $features,
        ]);
    }

    /**
     * GET /api/risk/predictions/{id}
     * Chi tiết bản ghi đánh giá rủi ro.
     */
    public function predictionDetail(int $id): JsonResponse
    {
        $prediction = RiskPrediction::with(['user', 'factors', 'interventions.teacher'])->find($id);

        if (! $prediction) {
            return response()->json(['error' => 'Bản ghi không tồn tại'], 404);
        }

        return response()->json([
            'prediction' => $prediction,
        ]);
    }

    /**
     * POST /api/risk/predictions/{id}/review
     * Ghi nhận can thiệp sư phạm từ giáo viên hoặc admin.
     */
    public function reviewPrediction(Request $request, int $id): JsonResponse
    {
        $prediction = RiskPrediction::with('user')->find($id);

        if (! $prediction) {
            return response()->json(['error' => 'Bản ghi không tồn tại'], 404);
        }

        $validated = $request->validate([
            'action_type' => 'required|string|max:100',
            'notes' => 'required|string|max:1000',
            'status' => 'nullable|string|in:PENDING,IN_PROGRESS,COMPLETED',
        ]);

        $teacher = Auth::user() ?? User::where('role', User::ROLE_TEACHER)->first();

        $intervention = $this->riskService->recordIntervention($teacher, $prediction->user, [
            'prediction_id' => $prediction->prediction_id,
            'action_type' => $validated['action_type'],
            'notes' => $validated['notes'],
            'status' => $validated['status'] ?? 'COMPLETED',
        ]);

        return response()->json([
            'message' => 'Đã ghi nhận can thiệp sư phạm thành công',
            'intervention' => $intervention,
        ]);
    }

    /**
     * GET /api/risk/configuration
     * Trả về cấu hình và quy tắc nghiệp vụ của Risk Engine.
     */
    public function configuration(): JsonResponse
    {
        return response()->json([
            'engine_name' => 'Smart LMS Dropout Risk Engine',
            'engine_version' => '2.0-BestCoder2026',
            'rules' => [
                'attendance_fail_operator' => '>',
                'attendance_fail_threshold_percent' => 20.0,
                'attendance_warning_threshold_percent' => 15.0,
                'passing_score' => 5.0,
                'calculation_mode' => 'PER_SUBJECT_INDEPENDENT',
            ],
            'risk_levels' => [
                RiskPrediction::LEVEL_SAFE => 'An toàn (Kết quả học tập và chuyên cần tốt)',
                RiskPrediction::LEVEL_MEDIUM => 'Cảnh báo trung bình (Có môn tiệm cận 20% hoặc 1 môn điểm yếu)',
                RiskPrediction::LEVEL_HIGH => 'Nguy cơ cao (Vắng quá 20% ở 1 môn hoặc nợ >= 2 môn)',
                RiskPrediction::LEVEL_VERY_HIGH => 'Nguy cơ rất cao (Vắng quá 20% ở nhiều môn hoặc vắng >= 35% kèm nợ môn)',
            ],
            'ml_service_status' => [
                'enabled' => filter_var(env('ML_SERVICE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
                'endpoint' => env('ML_SERVICE_URL', 'http://127.0.0.1:8001'),
                'fallback_strategy' => 'TRANSPARENT_RULE_ENGINE',
            ],
        ]);
    }
}

