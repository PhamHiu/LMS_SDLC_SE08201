<?php

require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\RiskApiController;
use App\Models\ClassRoom;
use App\Models\RiskPrediction;
use App\Models\User;
use App\Services\AI\DropoutRiskRuleEngine;
use App\Services\AI\DropoutRiskService;
use Illuminate\Http\Request;

echo "========================================================================\n";
echo "   BESTCODER 2026 — API & MACHINE LEARNING INTEGRATION TEST SUITE       \n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(string $testId, string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$testId}: {$desc}\n";
        if ($detail) echo "        Detail: {$detail}\n";
    } else {
        $failCount++;
        echo " [FAIL] {$testId}: {$desc}\n";
        if ($detail) echo "        Error: {$detail}\n";
    }
}

$ruleEngine = new DropoutRiskRuleEngine();
$riskService = new DropoutRiskService($ruleEngine);
$controller = new RiskApiController($riskService, $ruleEngine);

// --- TC-API-01: Configuration endpoint ---
$configResponse = $controller->configuration();
$configData = $configResponse->getData(true);
assertCheck(
    'TC-API-01',
    'API Configuration trả về đúng ngưỡng vắng 20% và engine_version',
    ($configResponse->getStatusCode() === 200 &&
     (float) $configData['rules']['attendance_fail_threshold_percent'] == 20.0 &&
     $configData['rules']['attendance_fail_operator'] === '>'),
    "Operator: {$configData['rules']['attendance_fail_operator']}, Threshold: {$configData['rules']['attendance_fail_threshold_percent']}%"
);

// --- TC-API-02: Extract ML Features vector ---
$studentSafe = User::where('email', 'student_safe@lms.vn')->first();
$features = $ruleEngine->extractFeatures($studentSafe);
$requiredKeys = ['student_id', 'gpa', 'passed_subjects', 'failed_subjects', 'retake_count', 'attendance_fail_count', 'max_absence_rate', 'overall_absence_rate', 'grade_trend'];
$hasAllKeys = count(array_intersect_key(array_flip($requiredKeys), $features)) === count($requiredKeys);
assertCheck(
    'TC-API-02',
    'Feature Extraction trả về đủ 9 đặc trưng ML chuẩn (Pydantic Schema)',
    $hasAllKeys,
    "Keys: " . implode(', ', array_keys($features))
);

// --- TC-API-03: Student Risk API Endpoint ---
$riskResponse = $controller->studentRisk($studentSafe->user_id);
$riskData = $riskResponse->getData(true);
assertCheck(
    'TC-API-03',
    'API GET /api/teacher/students/{id}/risk trả về đúng cấu trúc Section XI',
    ($riskResponse->getStatusCode() === 200 &&
     isset($riskData['risk_level']) &&
     isset($riskData['contributing_factors']) &&
     isset($riskData['explanation']) &&
     isset($riskData['recommendations'])),
    "Level: {$riskData['risk_level']}, Method: {$riskData['analysis_method']}"
);

// --- TC-API-04: Admin Dashboard API Endpoint ---
$adminResponse = $controller->adminDashboard();
$adminData = $adminResponse->getData(true);
assertCheck(
    'TC-API-04',
    'API GET /api/admin/risk/dashboard trả về đủ 4 mức nguy cơ toàn trường',
    ($adminResponse->getStatusCode() === 200 &&
     isset($adminData['counts_by_level']['SAFE']) &&
     isset($adminData['counts_by_level']['MEDIUM']) &&
     isset($adminData['counts_by_level']['HIGH']) &&
     isset($adminData['counts_by_level']['VERY_HIGH'])),
    "Total students: {$adminData['total_students']}, SAFE: {$adminData['counts_by_level']['SAFE']}, VERY_HIGH: {$adminData['counts_by_level']['VERY_HIGH']}"
);

// --- TC-API-05: Student Attendance Summary API ---
$attResponse = $controller->studentAttendanceSummary($studentSafe->user_id);
$attData = $attResponse->getData(true);
assertCheck(
    'TC-API-05',
    'API GET /api/students/{id}/attendance-summary trả về chi tiết từng môn',
    ($attResponse->getStatusCode() === 200 && is_array($attData['subjects'])),
    "Count subjects: " . count($attData['subjects'])
);

// --- TC-API-06: Student Academic History API ---
$acadResponse = $controller->studentAcademicHistory($studentSafe->user_id);
$acadData = $acadResponse->getData(true);
assertCheck(
    'TC-API-06',
    'API GET /api/students/{id}/academic-history trả về các chỉ số học thuật',
    ($acadResponse->getStatusCode() === 200 && isset($acadData['academic_metrics']['gpa'])),
    "GPA: {$acadData['academic_metrics']['gpa']}"
);

// --- TC-API-07: Graceful Fallback to Rule Engine when ML service offline ---
$prediction = $riskService->analyzeAndRecord($studentSafe);
assertCheck(
    'TC-API-07',
    'Hệ thống tự động Fallback sang Rule Engine minh bạch khi ML Service chưa chạy',
    ($prediction->analysis_method === 'RULE_ENGINE' && in_array($prediction->risk_level, ['SAFE', 'MEDIUM', 'HIGH', 'VERY_HIGH'])),
    "Method: {$prediction->analysis_method}, Risk: {$prediction->risk_level}"
);

// --- TC-API-08: Review and record Intervention API ---
$teacher = User::where('role', User::ROLE_TEACHER)->first();
$req = new Request();
$req->replace([
    'action_type' => 'COUNSELING',
    'notes' => 'Đã hẹn gặp sinh viên tại văn phòng khoa để tư vấn lộ trình học.',
    'status' => 'COMPLETED',
]);
$reviewResponse = $controller->reviewPrediction($req, $prediction->prediction_id);
$reviewData = $reviewResponse->getData(true);
assertCheck(
    'TC-API-08',
    'API POST /api/risk/predictions/{id}/review lưu thành công can thiệp sư phạm',
    ($reviewResponse->getStatusCode() === 200 && isset($reviewData['intervention']['intervention_id'])),
    "Intervention ID: " . ($reviewData['intervention']['intervention_id'] ?? 'N/A')
);

echo "\n------------------------------------------------------------------------\n";
echo " KẾT QUẢ KIỂM THỬ TÍCH HỢP API & ML: {$passCount}/" . ($passCount + $failCount) . " PASSED, {$failCount} FAILED\n";
echo "------------------------------------------------------------------------\n";

if ($failCount > 0) {
    exit(1);
}
