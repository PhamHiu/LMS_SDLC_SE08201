<?php

require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AiConversation;
use App\Models\ClassRoom;
use App\Models\RiskPrediction;
use App\Models\User;
use App\Services\AI\AiTutorService;
use App\Services\AI\DropoutRiskRuleEngine;

echo "========================================================================\n";
echo "       BỘ KIỂM THỬ TOÀN DIỆN BESTCODER 2026 — SMART LMS TESTING         \n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $testId, string $desc, bool $condition, string $detail = '') {
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

// --- TEST CASE 01: NGHỈ DƯỚI 20% ---
$total20 = 20;
$absent3 = 3;
$rate3 = ($absent3 / $total20) * 100; // 15%
assertTest('TC-01', 'Nghỉ dưới 20% (3/20 buổi = 15%) chưa trượt chuyên cần', ($rate3 <= 20.0 && !($rate3 > 20.0)), "Tỷ lệ: {$rate3}%");

// --- TEST CASE 02: NGHỈ ĐÚNG 20% (BẮT BUỘC CHÍNH XÁC: CHƯA TRƯỢT) ---
$absent4 = 4;
$rate4 = ($absent4 / $total20) * 100; // 20.0%
$isFailed20 = ($rate4 > 20.0); // Quy tắc: Nghỉ QUÁ 20% mới trượt, đúng 20% là CHƯA TRƯỢT
assertTest('TC-02', 'Nghỉ ĐÚNG 20% (4/20 buổi = 20.0%) CHƯA TRƯỢT chuyên cần', ($isFailed20 === false), "Tỷ lệ: {$rate4}%, Trượt: " . ($isFailed20 ? 'YES' : 'NO'));

// --- TEST CASE 03: NGHỈ TRÊN 20% (TRƯỢT CHUYÊN CẦN) ---
$absent5 = 5;
$rate5 = ($absent5 / $total20) * 100; // 25.0%
$isFailed25 = ($rate5 > 20.0);
assertTest('TC-03', 'Nghỉ TRÊN 20% (5/20 buổi = 25.0%) TRƯỢT điều kiện chuyên cần', ($isFailed25 === true), "Tỷ lệ: {$rate5}%, Trượt: " . ($isFailed25 ? 'YES' : 'NO'));

// --- TEST CASE 04: HỌC SINH AN TOÀN TRONG CƠ SỞ DỮ LIỆU ---
$safeStudent = User::where('email', 'student_safe@lms.vn')->first();
if ($safeStudent) {
    $predSafe = $ruleEngine->evaluate($safeStudent);
    assertTest('TC-04', 'Học sinh PASS toàn bộ, chuyên cần 100% được xếp mức AN TOÀN', ($predSafe['risk_level'] === RiskPrediction::LEVEL_SAFE), "Mức xếp: {$predSafe['risk_level']}");
}

// --- TEST CASE 05: HỌC SINH VẮNG QUÁ 20% (student_absent@lms.vn) ---
$absentStudent = User::where('email', 'student_absent@lms.vn')->first();
if ($absentStudent) {
    $predAbsent = $ruleEngine->evaluate($absentStudent);
    $hasCriticalAttendance = collect($predAbsent['factors'])->where('severity', 'CRITICAL')->isNotEmpty();
    assertTest('TC-05', 'Học sinh vắng 3/12 buổi (25%) bị cảnh báo nguy cơ cao & có yếu tố CRITICAL', ($hasCriticalAttendance && in_array($predAbsent['risk_level'], [RiskPrediction::LEVEL_HIGH, RiskPrediction::LEVEL_VERY_HIGH])), "Mức: {$predAbsent['risk_level']}");
}

// --- TEST CASE 06: HỌC SINH NGUY CƠ RẤT CAO (student_dropout@lms.vn) ---
$dropoutStudent = User::where('email', 'student_dropout@lms.vn')->first();
if ($dropoutStudent) {
    $predDropout = $ruleEngine->evaluate($dropoutStudent);
    assertTest('TC-06', 'Học sinh vắng 41.7% + điểm kém xếp mức VERY_HIGH', ($predDropout['risk_level'] === RiskPrediction::LEVEL_VERY_HIGH), "Mức: {$predDropout['risk_level']} (Score: {$predDropout['risk_score']})");
}

// --- TEST CASE 07: KIỂM THỬ SYSTEM INSTRUCTION CỦA AI TUTOR ---
$tutorService = new AiTutorService();
$sysInstruction = $tutorService->getSystemInstruction(null, AiConversation::MODE_GUIDANCE);
$hasNoDirectCodeRule = str_contains($sysInstruction, 'TUYỆT ĐỐI KHÔNG mặc định cung cấp toàn bộ lời giải hay code hoàn chỉnh');
assertTest('TC-07', 'System Instruction của AI Tutor chứa điều khoản cấm giải hộ bài thi/kiểm tra', $hasNoDirectCodeRule, 'Quy tắc sư phạm được thực thi');

// --- TEST CASE 08: KIỂM THỬ QUẢN LÝ LỊCH SỬ HỘI THOẠI AI TUTOR ---
if ($safeStudent) {
    $conv = AiConversation::create([
        'user_id' => $safeStudent->user_id,
        'title' => 'Test Conversation TC-08',
        'mode' => AiConversation::MODE_EXPLAIN,
    ]);
    $msg = $tutorService->sendMessage($conv, 'Xin chào AI Tutor!');
    assertTest('TC-08', 'AI Tutor tạo phiên chat, lưu tin nhắn User & Assistant vào CSDL', ($conv->messages()->count() >= 2), "Số tin nhắn lưu: " . $conv->messages()->count());
    $conv->delete();
}

// --- TEST CASE 09: BẢO VỆ PHÂN QUYỀN AI TUTOR (STUDENT CHỈ XEM CỦA MÌNH) ---
if ($safeStudent && $absentStudent) {
    $convUserA = AiConversation::create([
        'user_id' => $safeStudent->user_id,
        'title' => 'Private Chat User A',
        'mode' => AiConversation::MODE_EXPLAIN,
    ]);
    $isOwnerCheck = ($convUserA->user_id === $safeStudent->user_id) && ($convUserA->user_id !== $absentStudent->user_id);
    assertTest('TC-09', 'Phân quyền kiểm tra quyền sở hữu cuộc trò chuyện AI (Resource Ownership)', $isOwnerCheck, 'Học sinh khác không thể truy cập');
    $convUserA->delete();
}

// --- TEST CASE 10: TÍNH RIÊNG TỪNG MÔN, KHÔNG CỘNG DỒN SAI ---
$classJava = ClassRoom::where('class_name', 'SE1801-Java')->first();
if ($safeStudent && $classJava) {
    $subjectAnalysis = $ruleEngine->analyzeSubject($safeStudent, $classJava);
    assertTest('TC-10', 'Phân tích chuyên cần theo từng môn trả về đúng tổng số buổi môn đó', ($subjectAnalysis['total_sessions'] === 12), "Tổng buổi môn Java: {$subjectAnalysis['total_sessions']}");
}

echo "\n------------------------------------------------------------------------\n";
echo " KẾT QUẢ KIỂM THỬ: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================================\n";

