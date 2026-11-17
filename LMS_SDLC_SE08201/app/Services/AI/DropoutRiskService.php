<?php

namespace App\Services\AI;

use App\Models\ClassRoom;
use App\Models\RiskFactor;
use App\Models\RiskIntervention;
use App\Models\RiskPrediction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropoutRiskService
{
    public function __construct(
        private readonly DropoutRiskRuleEngine $ruleEngine
    ) {
    }

    /**
     * Gọi service FastAPI để lấy kết quả Machine Learning (nếu service đang chạy).
     */
    public function callMlService(array $features): ?array
    {
        if (! filter_var(env('ML_SERVICE_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $mlUrl = rtrim(env('ML_SERVICE_URL', 'http://127.0.0.1:8001'), '/');

        // Chống tự gọi ngược vào chính cổng Laravel đang chạy (gây treo single-threaded server)
        $currentHost = app()->runningInConsole() ? null : request()?->getSchemeAndHttpHost();
        if ($currentHost && strcasecmp($mlUrl, rtrim($currentHost, '/')) === 0) {
            return null;
        }

        try {
            $response = Http::timeout(0.8)->connectTimeout(0.4)->post("{$mlUrl}/predict", $features);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::info("Smart LMS ML Service unreachable at {$mlUrl}: {$e->getMessage()} - Falling back to Rule Engine.");
        }

        return null;
    }

    /**
     * Phân tích và lưu lại kết quả đánh giá nguy cơ của sinh viên (Lưu lịch sử, không ghi đè).
     * Tự động ưu tiên Machine Learning khi service online, hoặc fallback liền mạch sang Rule Engine.
     */
    /** Thứ hạng mức độ nghiêm trọng để so sánh hai tầng (số lớn hơn = nguy hiểm hơn). */
    private const LEVEL_SEVERITY = [
        RiskPrediction::LEVEL_SAFE => 0,
        RiskPrediction::LEVEL_MEDIUM => 1,
        RiskPrediction::LEVEL_HIGH => 2,
        RiskPrediction::LEVEL_VERY_HIGH => 3,
    ];

    /** Trả về mức nguy cơ cao hơn giữa hai tầng Rule Engine và Machine Learning. */
    private function moreSevereLevel(string $levelA, string $levelB): string
    {
        $rankA = self::LEVEL_SEVERITY[$levelA] ?? 0;
        $rankB = self::LEVEL_SEVERITY[$levelB] ?? 0;

        return $rankA >= $rankB ? $levelA : $levelB;
    }

    public function analyzeAndRecord(User $student): RiskPrediction
    {
        // Tầng 1 — Rule Engine luôn chạy (minh bạch, tuân thủ quy chế chuyên cần 20%).
        $result = $this->ruleEngine->evaluate($student);

        // Tầng 2 — Machine Learning (nếu service online). Mức cuối lấy MỨC CAO HƠN của hai tầng.
        $features = $this->ruleEngine->extractFeatures($student);
        $mlResult = $this->callMlService($features);

        if ($mlResult && isset($mlResult['risk_level'])) {
            $mlLevel = $mlResult['risk_level'];
            $mlScore = (float) ($mlResult['risk_score'] ?? round(($mlResult['probability'] ?? 0) * 100, 1));

            $finalLevel = $this->moreSevereLevel($result['risk_level'], $mlLevel);

            // Giữ nguyên phân rã theo từng môn của Rule Engine (minh bạch), chỉ nâng mức/điểm theo tầng cao hơn.
            $result['risk_level'] = $finalLevel;
            $result['risk_score'] = max((float) ($result['risk_score'] ?? 0.0), $mlScore);
            $result['analysis_method'] = 'HYBRID_ML_RULE';
            $result['model_version'] = 'v2.0-hybrid-ml-rule';
            $result['data_quality_status'] = $mlResult['data_quality_status'] ?? ($result['data_quality_status'] ?? 'COMPLETE');

            // Bổ sung phần giải thích của ML (bao gồm kết luận SHAP) vào tóm tắt.
            $mlExplanation = trim((string) ($mlResult['explanation'] ?? ''));
            if ($mlExplanation !== '') {
                $result['summary_explanation'] = trim((string) ($result['summary_explanation'] ?? ''))
                    . ' [Mô hình ML] ' . $mlExplanation;
            }
        }

        return DB::transaction(function () use ($student, $result) {
            $prediction = RiskPrediction::create([
                'user_id' => $student->user_id,
                'risk_level' => $result['risk_level'],
                'risk_score' => $result['risk_score'],
                'analysis_method' => $result['analysis_method'],
                'model_version' => $result['model_version'],
                'summary_explanation' => $result['summary_explanation'],
                'recommendations' => $result['recommendations'],
                'data_quality_status' => $result['data_quality_status'],
                'analyzed_at' => now(),
            ]);

            foreach ($result['factors'] as $factorData) {
                RiskFactor::create([
                    'prediction_id' => $prediction->prediction_id,
                    'subject_id' => $factorData['subject_id'] ?? null,
                    'factor_type' => $factorData['factor_type'],
                    'title' => $factorData['title'],
                    'description' => $factorData['description'],
                    'severity' => $factorData['severity'] ?? 'INFO',
                    'impact_weight' => $factorData['impact_weight'] ?? 0.0,
                ]);
            }

            return $prediction;
        });
    }

    /**
     * Lấy kết quả phân tích mới nhất của một sinh viên (nếu chưa có hoặc phiên bản cũ thì tự động phân tích lại theo chuẩn toán học).
     */
    public function getLatestOrAnalyze(User $student): RiskPrediction
    {
        $latest = $student->latestRiskPrediction;

        $freshVersions = ['v2.0-academic-math', 'v2.0-hybrid-ml-rule'];
        if (! $latest || $latest->analyzed_at->diffInDays(now()) >= 3 || ! in_array($latest->model_version, $freshVersions, true)) {
            return $this->analyzeAndRecord($student);
        }

        return $latest->load('factors.subject');
    }

    /**
     * Thống kê tổng quan rủi ro cho Admin Dashboard.
     */
    public function getAdminOverview(): array
    {
        $students = User::where('role', User::ROLE_STUDENT)
            ->with(['major', 'latestRiskPrediction.factors.subject'])
            ->get();

        $levelCounts = [
            RiskPrediction::LEVEL_SAFE => 0,
            RiskPrediction::LEVEL_MEDIUM => 0,
            RiskPrediction::LEVEL_HIGH => 0,
            RiskPrediction::LEVEL_VERY_HIGH => 0,
        ];

        $studentSummaries = [];

        foreach ($students as $student) {
            $prediction = $this->getLatestOrAnalyze($student);
            $levelCounts[$prediction->risk_level] = ($levelCounts[$prediction->risk_level] ?? 0) + 1;

            $studentSummaries[] = [
                'student' => $student,
                'prediction' => $prediction,
            ];
        }

        return [
            'total_students' => $students->count(),
            'counts_by_level' => $levelCounts,
            'students' => $studentSummaries,
        ];
    }

    /**
     * Thống kê rủi ro trong một lớp học cụ thể cho Giảng viên.
     */
    public function getClassRiskSummary(ClassRoom $class): array
    {
        $students = $class->students()->with(['latestRiskPrediction.factors.subject'])->get();
        $classAnalysis = [];

        foreach ($students as $student) {
            $prediction = $this->getLatestOrAnalyze($student);
            $subjectAnalysis = $this->ruleEngine->analyzeSubject($student, $class);

            $classAnalysis[] = [
                'student' => $student,
                'overall_risk' => $prediction,
                'subject_detail' => $subjectAnalysis,
            ];
        }

        return [
            'class' => $class,
            'students_count' => $students->count(),
            'students_data' => $classAnalysis,
        ];
    }

    /**
     * Ghi nhận can thiệp sư phạm của giáo viên đối với sinh viên.
     */
    public function recordIntervention(User $teacher, User $student, array $data): RiskIntervention
    {
        return RiskIntervention::create([
            'user_id' => $student->user_id,
            'teacher_id' => $teacher->user_id,
            'prediction_id' => $data['prediction_id'] ?? null,
            'action_type' => $data['action_type'],
            'notes' => $data['notes'],
            'status' => $data['status'] ?? 'PENDING',
            'outcome_evaluation' => $data['outcome_evaluation'] ?? null,
        ]);
    }
}

