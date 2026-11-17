<?php

namespace App\Services\AI;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\RiskFactor;
use App\Models\RiskPrediction;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * CORE MATHEMATICAL RULE ENGINE — STUDENT DROPOUT RISK ANALYSIS
 * ============================================================================
 * Xây dựng dựa trên tài liệu nghiên cứu học thuật (17/11/2026):
 * 1. KHÔNG FIX CỨNG: Mọi chỉ số (vắng, điểm, nợ môn, điểm rủi ro, tỷ lệ đóng góp)
 *    đều được tính toán ĐỘNG 100% bằng công thức giải tích chuẩn hóa.
 * 2. CÔNG THỨC CHUẨN HOÁ:
 *    RiskScore = 100 * (w_a * A + w_g * G + w_r * R)
 *    - w_a = 0.40 (40% Chuyên cần & Vắng học)
 *    - w_g = 0.35 (35% Kết quả học tập & Điểm số GPA)
 *    - w_r = 0.25 (25% Trượt môn & Học lại)
 * 3. BỐN BẬC CẢNH BÁO VẬN HÀNH:
 *    - 0.0  - 24.9: An toàn (SAFE)
 *    - 25.0 - 49.9: Cần theo dõi (MEDIUM / WATCHLIST)
 *    - 50.0 - 74.9: Nguy cơ cao (HIGH)
 *    - 75.0 - 100.0: Nguy cơ rất cao (VERY_HIGH / CRITICAL)
 * 4. PHÂN RÃ TỶ LỆ ĐÓNG GÓP ĐIỂM (Feature Attribution / Score Decomposition)
 *    Giải thích minh bạch mức độ ảnh hưởng của từng yếu tố đến điểm rủi ro.
 * 5. TUÂN THỦ QUY CHẾ ĐÀO TẠO:
 *    Vắng quá 20% (> 20.0%) mới trượt chuyên cần môn học (nghỉ đúng 20% = CHƯA TRƯỢT).
 * ============================================================================
 */
class DropoutRiskRuleEngine
{
    public const PASSING_SCORE = 5.0; // Thang điểm 10, >= 5.0 là PASS

    /**
     * Đánh giá nguy cơ bỏ học toàn diện cho 1 sinh viên bằng công thức toán học động.
     */
    public function evaluate(User $student): array
    {
        $enrolledClasses = $student->enrolledClasses()->with(['subject', 'sessions.attendances'])->get();

        if ($enrolledClasses->isEmpty()) {
            return [
                'risk_level' => RiskPrediction::LEVEL_SAFE,
                'risk_score' => 0.0,
                'analysis_method' => 'RULE_ENGINE',
                'model_version' => 'v2.0-academic-math',
                'summary_explanation' => 'Học sinh chưa đăng ký lớp học nào hoặc chưa có dữ liệu đào tạo.',
                'recommendations' => 'Cần đăng ký môn học và hoàn thiện kế hoạch học tập.',
                'data_quality_status' => 'INSUFFICIENT',
                'factors' => [],
                'subjects_summary' => [],
                'score_breakdown' => [
                    'attendance' => ['normalized' => 0.0, 'points' => 0.0, 'percent' => 0.0, 'weight' => 40.0],
                    'academic' => ['normalized' => 0.0, 'points' => 0.0, 'percent' => 0.0, 'weight' => 35.0],
                    'retake' => ['normalized' => 0.0, 'points' => 0.0, 'percent' => 0.0, 'weight' => 25.0],
                ],
                'overall_gpa' => null,
                'overall_absence_rate' => 0.0,
                'max_absence_rate' => 0.0,
            ];
        }

        // Đọc cấu hình trọng số và ngưỡng (nếu không có thì lấy giá trị mặc định chuẩn nghiên cứu)
        $wAttendance = (float) config('risk.weights.attendance', 0.40);
        $wAcademic = (float) config('risk.weights.academic', 0.35);
        $wRetake = (float) config('risk.weights.retake', 0.25);

        // Chuẩn hóa tổng trọng số = 1.0 (tránh sai số cấu hình)
        $weightSum = $wAttendance + $wAcademic + $wRetake;
        if ($weightSum <= 0) {
            $wAttendance = 0.40;
            $wAcademic = 0.35;
            $wRetake = 0.25;
            $weightSum = 1.0;
        }
        $wAttendance /= $weightSum;
        $wAcademic /= $weightSum;
        $wRetake /= $weightSum;

        $thresholdAbsenceFail = (float) config('risk.thresholds.attendance_fail_rate', 0.20); // 0.20 (20%)
        $gpaSafe = (float) config('risk.thresholds.gpa_safe', 7.0); // 7.0
        $gpaCritical = (float) config('risk.thresholds.gpa_critical', 4.0); // 4.0
        $passingScore = (float) config('risk.thresholds.passing_score', self::PASSING_SCORE); // 5.0

        // 1. Phân tích từng môn học thực tế
        $subjectAnalyses = [];
        $totalSessionsCohort = 0;
        $totalAbsentCohort = 0;
        $totalLateCohort = 0;
        $totalScoreSum = 0;
        $gradedSubjectsCount = 0;
        $passedSubjectsCount = 0;
        $failedSubjectsCount = 0;
        $attendanceFailedSubjectsCount = 0;
        $warningAttendanceSubjectsCount = 0;

        foreach ($enrolledClasses as $class) {
            $analysis = $this->analyzeSubject($student, $class);
            $subjectAnalyses[] = $analysis;

            $totalSessionsCohort += $analysis['total_sessions'];
            $totalAbsentCohort += $analysis['absent_sessions'];
            $totalLateCohort += $analysis['late_sessions'];

            if ($analysis['attendance_failed']) {
                $attendanceFailedSubjectsCount++;
            } elseif ($analysis['absence_rate'] >= 15.0 && $analysis['absence_rate'] <= 20.0) {
                $warningAttendanceSubjectsCount++;
            }

            if ($analysis['status'] === 'FAIL') {
                $failedSubjectsCount++;
            } elseif ($analysis['status'] === 'PASS') {
                $passedSubjectsCount++;
            }

            if ($analysis['average_score'] !== null) {
                $totalScoreSum += $analysis['average_score'];
                $gradedSubjectsCount++;
            }
        }

        // Tỷ lệ vắng chung toàn bộ các môn: a_overall
        $overallAbsenceRate = $totalSessionsCohort > 0 ? ($totalAbsentCohort / $totalSessionsCohort) : 0.0;
        // Tỷ lệ vắng cao nhất trong một môn bất kỳ: a_max
        $maxAbsenceRatePercent = collect($subjectAnalyses)->max('absence_rate') ?? 0.0;
        $maxAbsenceRateRatio = $maxAbsenceRatePercent / 100.0;

        // Điểm GPA tổng hợp
        $overallGpa = $gradedSubjectsCount > 0 ? round($totalScoreSum / $gradedSubjectsCount, 2) : null;

        // Lịch sử học lại (các môn đăng ký nhiều hơn 1 lớp)
        $subjectIds = $enrolledClasses->pluck('subject_id');
        $duplicateSubjects = $subjectIds->duplicates();
        $retakeCount = $duplicateSubjects->count();

        // ---------------------------------------------------------------------
        // 2. TÍNH TOÁN CÁC CHỈ SỐ RỦI RO CHUẨN HÓA VỀ ĐOẠN [0, 1] THEO MỤC 5.2
        // ---------------------------------------------------------------------

        // A) Mức rủi ro chuyên cần A = min(1, a / 0.20)
        // Kết hợp giữa tỷ lệ vắng môn nghiêm trọng nhất và tỷ lệ vắng tổng thể
        $effectiveAbsenceRatio = max($overallAbsenceRate, $maxAbsenceRateRatio);
        $normAttendanceRisk = ($thresholdAbsenceFail > 0)
            ? min(1.0, max(0.0, $effectiveAbsenceRatio / $thresholdAbsenceFail))
            : 0.0;
        $normAttendanceRisk = round($normAttendanceRisk, 4);

        // B) Mức rủi ro điểm trung bình G = max(0, min(1, (7 - GPA) / 3))
        // Nếu GPA >= 7.0 => G = 0. Nếu GPA <= 4.0 => G = 1. Điểm giữa nội suy tuyến tính.
        if ($overallGpa !== null) {
            $gpaRange = max(0.1, $gpaSafe - $gpaCritical);
            $normGradeRisk = max(0.0, min(1.0, ($gpaSafe - $overallGpa) / $gpaRange));
        } else {
            // Chưa có điểm bài tập nào: gán mức an toàn ban đầu (G = 0)
            $normGradeRisk = 0.0;
        }
        $normGradeRisk = round($normGradeRisk, 4);

        // C) Mức rủi ro trượt môn / học lại R = min(1, môn trượt hoặc học lại / tổng môn)
        // Đếm các môn có kết quả không đạt (vắng > 20% hoặc điểm < 5.0) cộng với các môn đang học lại
        $distinctEvaluatedSubjects = max(1, $enrolledClasses->pluck('subject_id')->unique()->count());
        $failedOrRetakeCount = min($distinctEvaluatedSubjects, $failedSubjectsCount + $retakeCount);
        $normRetakeRisk = min(1.0, max(0.0, $failedOrRetakeCount / $distinctEvaluatedSubjects));
        $normRetakeRisk = round($normRetakeRisk, 4);

        // ---------------------------------------------------------------------
        // 3. TÍNH ĐIỂM NGUY CƠ TỔNG HỢP: RiskScore = 100 * (0.40*A + 0.35*G + 0.25*R)
        // ---------------------------------------------------------------------
        $attendancePoints = round(100.0 * $wAttendance * $normAttendanceRisk, 1);
        $gradePoints = round(100.0 * $wAcademic * $normGradeRisk, 1);
        $retakePoints = round(100.0 * $wRetake * $normRetakeRisk, 1);

        $rawRiskScore = $attendancePoints + $gradePoints + $retakePoints;
        $riskScore = min(100.0, max(0.0, round($rawRiskScore, 1)));

        // ---------------------------------------------------------------------
        // 4. PHÂN RÃ TỶ LỆ ĐÓNG GÓP ĐIỂM THÀNH PHẦN (%) (Mục 4.4 & 5.3)
        // Trả lời câu hỏi: "Phần nào của điểm cảnh báo đến từ yếu tố này?"
        // ---------------------------------------------------------------------
        if ($riskScore > 0) {
            $attendanceContribPct = round(($attendancePoints / $riskScore) * 100.0, 1);
            $gradeContribPct = round(($gradePoints / $riskScore) * 100.0, 1);
            $retakeContribPct = round(($retakePoints / $riskScore) * 100.0, 1);

            // Cân đối làm tròn để tổng đúng 100.0%
            $roundingDiff = round(100.0 - ($attendanceContribPct + $gradeContribPct + $retakeContribPct), 1);
            if (abs($roundingDiff) > 0 && abs($roundingDiff) <= 0.5) {
                // Điều chỉnh phần dư vào yếu tố có điểm đóng góp cao nhất
                if ($attendancePoints >= $gradePoints && $attendancePoints >= $retakePoints) {
                    $attendanceContribPct += $roundingDiff;
                } elseif ($gradePoints >= $retakePoints) {
                    $gradeContribPct += $roundingDiff;
                } else {
                    $retakeContribPct += $roundingDiff;
                }
            }
        } else {
            $attendanceContribPct = 0.0;
            $gradeContribPct = 0.0;
            $retakeContribPct = 0.0;
        }

        // ---------------------------------------------------------------------
        // 5. ÁNH XẠ 4 BẬC VẬN HÀNH CHUẨN NGHIÊN CỨU (Mục 5.4)
        // 0 - 24.9: SAFE | 25.0 - 49.9: MEDIUM | 50.0 - 74.9: HIGH | 75.0 - 100: VERY_HIGH
        // ---------------------------------------------------------------------
        if ($riskScore < 25.0) {
            $riskLevel = RiskPrediction::LEVEL_SAFE;
        } elseif ($riskScore < 50.0) {
            $riskLevel = RiskPrediction::LEVEL_MEDIUM;
        } elseif ($riskScore < 75.0) {
            $riskLevel = RiskPrediction::LEVEL_HIGH;
        } else {
            $riskLevel = RiskPrediction::LEVEL_VERY_HIGH;
        }

        // ---------------------------------------------------------------------
        // 6. TẠO CÁC YẾU TỐ RỦI RO (FACTORS) MINH BẠCH GIẢI THÍCH CHO AI
        // ---------------------------------------------------------------------
        $factors = [];

        // Yếu tố A: Chuyên cần
        if ($attendancePoints > 0) {
            $severity = $normAttendanceRisk >= 1.0 ? 'CRITICAL' : ($normAttendanceRisk >= 0.5 ? 'WARNING' : 'INFO');
            $factors[] = [
                'subject_id' => null,
                'factor_type' => RiskFactor::TYPE_ATTENDANCE,
                'title' => sprintf('Chuyên cần & Vắng học (Trọng số %.0f%%) — Đóng góp %.1f điểm (%.1f%%)', $wAttendance * 100, $attendancePoints, $attendanceContribPct),
                'description' => sprintf(
                    'Tỷ lệ vắng thực tế %.1f%% (môn cao nhất %.1f%%). Chỉ số chuẩn hóa A = %.2f. Đóng góp %.1f/%.1f điểm rủi ro.',
                    $overallAbsenceRate * 100,
                    $maxAbsenceRatePercent,
                    $normAttendanceRisk,
                    $attendancePoints,
                    $wAttendance * 100
                ),
                'severity' => $severity,
                'impact_weight' => $attendancePoints,
            ];
        }

        // Yếu tố G: Học lực GPA
        if ($gradePoints > 0) {
            $severity = $normGradeRisk >= 0.8 ? 'CRITICAL' : ($normGradeRisk >= 0.4 ? 'WARNING' : 'INFO');
            $factors[] = [
                'subject_id' => null,
                'factor_type' => RiskFactor::TYPE_GRADE,
                'title' => sprintf('Kết quả học tập & GPA (Trọng số %.0f%%) — Đóng góp %.1f điểm (%.1f%%)', $wAcademic * 100, $gradePoints, $gradeContribPct),
                'description' => sprintf(
                    'Điểm trung bình GPA hiện tại là %.2f/10. Chỉ số chuẩn hóa G = %.2f. Đóng góp %.1f/%.1f điểm rủi ro.',
                    $overallGpa ?? 0.0,
                    $normGradeRisk,
                    $gradePoints,
                    $wAcademic * 100
                ),
                'severity' => $severity,
                'impact_weight' => $gradePoints,
            ];
        }

        // Yếu tố R: Trượt môn / Học lại
        if ($retakePoints > 0) {
            $severity = $normRetakeRisk >= 0.5 ? 'CRITICAL' : 'WARNING';
            $factors[] = [
                'subject_id' => null,
                'factor_type' => RiskFactor::TYPE_RETAKE,
                'title' => sprintf('Trượt môn & Học lại (Trọng số %.0f%%) — Đóng góp %.1f điểm (%.1f%%)', $wRetake * 100, $retakePoints, $retakeContribPct),
                'description' => sprintf(
                    'Sinh viên có %d môn chưa đạt/trượt chuyên cần và %d môn học lại trên tổng %d môn. Chỉ số chuẩn hóa R = %.2f. Đóng góp %.1f/%.1f điểm rủi ro.',
                    $failedSubjectsCount,
                    $retakeCount,
                    $distinctEvaluatedSubjects,
                    $normRetakeRisk,
                    $retakePoints,
                    $wRetake * 100
                ),
                'severity' => $severity,
                'impact_weight' => $retakePoints,
            ];
        }

        // Bổ sung chi tiết từng môn nếu có vi phạm chuyên cần hoặc điểm kém
        foreach ($subjectAnalyses as $sub) {
            if ($sub['attendance_failed']) {
                $factors[] = [
                    'subject_id' => $sub['subject_id'],
                    'factor_type' => RiskFactor::TYPE_ATTENDANCE,
                    'title' => 'Trượt chuyên cần môn ' . $sub['subject_name'],
                    'description' => sprintf('Học sinh vắng %d/%d buổi (%.1f%%), vượt giới hạn 20%% theo quy chế đào tạo.', $sub['absent_sessions'], $sub['total_sessions'], $sub['absence_rate']),
                    'severity' => 'CRITICAL',
                    'impact_weight' => 20.0,
                ];
            } elseif ($sub['absence_rate'] >= 15.0) {
                $factors[] = [
                    'subject_id' => $sub['subject_id'],
                    'factor_type' => RiskFactor::TYPE_ATTENDANCE,
                    'title' => 'Chuyên cần tiệm cận mốc cấm thi: ' . $sub['subject_name'],
                    'description' => sprintf('Học sinh vắng %d/%d buổi (%.1f%%). Nếu nghỉ thêm sẽ vượt giới hạn 20%%.', $sub['absent_sessions'], $sub['total_sessions'], $sub['absence_rate']),
                    'severity' => 'WARNING',
                    'impact_weight' => 10.0,
                ];
            }

            if ($sub['status'] === 'FAIL' && ! $sub['attendance_failed']) {
                $factors[] = [
                    'subject_id' => $sub['subject_id'],
                    'factor_type' => RiskFactor::TYPE_GRADE,
                    'title' => 'Chưa đạt điểm môn ' . $sub['subject_name'],
                    'description' => sprintf('Điểm trung bình hiện tại là %.2f/10 (dưới ngưỡng chuẩn %.1f).', $sub['average_score'] ?? 0, $passingScore),
                    'severity' => 'WARNING',
                    'impact_weight' => 15.0,
                ];
            }
        }

        // ---------------------------------------------------------------------
        // 7. SINH TỔNG HỢP GIẢI THÍCH VÀ KHUYẾN NGHỊ SƯ PHẠM (Explainable AI)
        // ---------------------------------------------------------------------
        $summaryExplanation = sprintf(
            'Điểm nguy cơ tổng hợp: %.1f/100 (Mức %s). Điểm thành phần: Chuyên cần %.1fđ (%.1f%%), Điểm số %.1fđ (%.1f%%), Nợ môn %.1fđ (%.1f%%). ' .
            'Lưu ý khoa học: Điểm số này là công cụ sắp xếp thứ tự ưu tiên cảnh báo học vụ, không đồng nghĩa sinh viên có %.1f%% xác suất bỏ học.',
            $riskScore,
            $riskLevel,
            $attendancePoints,
            $attendanceContribPct,
            $gradePoints,
            $gradeContribPct,
            $retakePoints,
            $retakeContribPct,
            $riskScore
        );

        $recommendations = match ($riskLevel) {
            RiskPrediction::LEVEL_VERY_HIGH => 'Cần can thiệp khẩn cấp (Tầng 2): Tổ chức buổi gặp mặt trực tiếp giữa Cố vấn học tập, Giảng viên và Sinh viên để xác minh hoàn cảnh thực tế (Tài chính / Đi làm / Tâm lý / Sức khỏe). Lập kế hoạch cứu vãn học tập hoặc hướng dẫn thủ tục bảo lưu hợp lệ.',
            RiskPrediction::LEVEL_HIGH => 'Cần can thiệp sớm: Cố vấn học tập liên hệ tìm hiểu khó khăn; giảng viên bộ môn phối hợp phụ đạo kiến thức hổng và cho phép nộp bù bài tập; kết nối với AI Tutor để ôn tập chuyên sâu.',
            RiskPrediction::LEVEL_MEDIUM => 'Cần theo dõi sát: Gửi thông báo nhắc nhở qua LMS về các buổi học còn lại; cảnh báo sinh viên về các môn tiệm cận ngưỡng nghỉ 20%; khuyến khích hoàn thành bài tập đúng hạn.',
            default => 'Duy trì kết quả học tập tốt: Điểm chuyên cần và GPA đều ổn định; khuyến khích phát huy và hỗ trợ học tập cho các bạn cùng lớp.',
        };

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'analysis_method' => 'RULE_ENGINE',
            'model_version' => 'v2.0-academic-math',
            'summary_explanation' => $summaryExplanation,
            'recommendations' => $recommendations,
            'data_quality_status' => $gradedSubjectsCount > 0 ? 'COMPLETE' : 'PARTIAL',
            'factors' => $factors,
            'subjects_summary' => $subjectAnalyses,
            'overall_gpa' => $overallGpa,
            'passed_count' => $passedSubjectsCount,
            'failed_count' => $failedSubjectsCount,
            'attendance_failed_count' => $attendanceFailedSubjectsCount,
            'score_breakdown' => [
                'attendance' => [
                    'normalized' => $normAttendanceRisk,
                    'points' => $attendancePoints,
                    'percent' => $attendanceContribPct,
                    'weight' => round($wAttendance * 100, 1),
                ],
                'academic' => [
                    'normalized' => $normGradeRisk,
                    'points' => $gradePoints,
                    'percent' => $gradeContribPct,
                    'weight' => round($wAcademic * 100, 1),
                ],
                'retake' => [
                    'normalized' => $normRetakeRisk,
                    'points' => $retakePoints,
                    'percent' => $retakeContribPct,
                    'weight' => round($wRetake * 100, 1),
                ],
            ],
            'overall_absence_rate' => round($overallAbsenceRate * 100, 2),
            'max_absence_rate' => $maxAbsenceRatePercent,
        ];
    }

    /**
     * Phân tích chi tiết một môn học cụ thể của sinh viên.
     */
    public function analyzeSubject(User $student, ClassRoom $class): array
    {
        $totalSessions = max(1, (int) $class->total_sessions);

        // Lấy lịch sử điểm danh của sinh viên trong lớp này
        $sessionIds = $class->sessions->pluck('session_id');
        $attendances = Attendance::where('user_id', $student->user_id)
            ->whereIn('session_id', $sessionIds)
            ->get();

        $absentCount = $attendances->where('status', 'A')->count();
        $lateCount = $attendances->where('status', 'L')->count();
        $presentCount = $attendances->where('status', 'P')->count();

        // Tỷ lệ nghỉ học tính theo công thức chuẩn: (Số buổi vắng / Tổng số buổi) * 100
        $absenceRate = round(($absentCount / $totalSessions) * 100, 2);

        // QUY TẮC 20% BẮT BUỘC CHÍNH XÁC:
        // Nghỉ QUÁ 20% (> 20.0%) mới trượt chuyên cần.
        // Nghỉ đúng 20% (ví dụ 4/20 = 20.0%) -> CHƯA TRƯỢT!
        $threshold = (float) config('risk.thresholds.attendance_fail_rate', 0.20) * 100.0;
        $attendanceFailed = $absenceRate > $threshold;

        // Tính điểm trung bình các bài tập trong lớp
        $assignmentIds = $class->assignments()->pluck('assignment_id');
        $submissions = Submission::where('user_id', $student->user_id)
            ->whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('score')
            ->get();

        $averageScore = $submissions->isNotEmpty() ? round($submissions->avg('score'), 2) : null;
        $passingScore = (float) config('risk.thresholds.passing_score', self::PASSING_SCORE);

        $status = 'IN_PROGRESS';
        if ($attendanceFailed) {
            $status = 'FAIL'; // Trượt chuyên cần thì môn đó tính là FAIL
        } elseif ($averageScore !== null) {
            $status = $averageScore >= $passingScore ? 'PASS' : 'FAIL';
        }

        return [
            'class_id' => $class->class_id,
            'class_name' => $class->class_name,
            'subject_id' => $class->subject_id,
            'subject_code' => $class->subject->subject_code ?? '',
            'subject_name' => $class->subject->subject_name ?? '',
            'total_sessions' => $totalSessions,
            'absent_sessions' => $absentCount,
            'late_sessions' => $lateCount,
            'present_sessions' => $presentCount,
            'absence_rate' => $absenceRate,
            'attendance_failed' => $attendanceFailed,
            'average_score' => $averageScore,
            'status' => $status,
        ];
    }

    /**
     * Trích xuất vector đặc trưng học tập phục vụ Machine Learning Service.
     */
    public function extractFeatures(User $student): array
    {
        $eval = $this->evaluate($student);
        $subjects = $eval['subjects_summary'] ?? [];

        $maxAbsenceRate = !empty($subjects) ? (float) collect($subjects)->max('absence_rate') : 0.0;
        $totalSessions = !empty($subjects) ? (int) collect($subjects)->sum('total_sessions') : 0;
        $totalAbsent = !empty($subjects) ? (int) collect($subjects)->sum('absent_sessions') : 0;
        $overallAbsenceRate = $totalSessions > 0 ? round(($totalAbsent / $totalSessions) * 100, 2) : 0.0;

        return [
            'student_id' => $student->student_code ?? ('STU_' . $student->user_id),
            'gpa' => (float) ($eval['overall_gpa'] ?? 7.0),
            'passed_subjects' => (int) ($eval['passed_count'] ?? 0),
            'failed_subjects' => (int) ($eval['failed_count'] ?? 0),
            'retake_count' => (int) ($student->enrolledClasses()->pluck('subject_id')->duplicates()->count()),
            'attendance_fail_count' => (int) ($eval['attendance_failed_count'] ?? 0),
            'max_absence_rate' => $maxAbsenceRate,
            'overall_absence_rate' => $overallAbsenceRate,
            'grade_trend' => 0.0,
            'overdue_assignments' => $this->countOverdueAssignments($student),
        ];
    }

    /**
     * Đếm số bài tập đã quá hạn mà sinh viên CHƯA nộp hoặc nộp TRỄ (sau hạn).
     * Đây là tín hiệu hành vi phục vụ mô hình ML (đặc trưng overdue_assignments).
     * Luôn phòng vệ: mọi lỗi dữ liệu đều trả về 0 để không làm gián đoạn phân tích rủi ro.
     */
    public function countOverdueAssignments(User $student): int
    {
        try {
            $classIds = $student->enrolledClasses()->pluck('classes.class_id')->all();
            if (empty($classIds)) {
                return 0;
            }

            $pastAssignments = \App\Models\Assignment::whereIn('class_id', $classIds)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->get(['assignment_id', 'due_date']);

            if ($pastAssignments->isEmpty()) {
                return 0;
            }

            $submissions = Submission::whereIn('assignment_id', $pastAssignments->pluck('assignment_id'))
                ->where('user_id', $student->user_id)
                ->get(['assignment_id', 'submitted_at'])
                ->keyBy('assignment_id');

            $overdue = 0;
            foreach ($pastAssignments as $assignment) {
                $submission = $submissions->get($assignment->assignment_id);
                // Chưa nộp, hoặc nộp sau hạn => tính là bài quá hạn
                if (! $submission
                    || $submission->submitted_at === null
                    || $submission->submitted_at->gt($assignment->due_date)) {
                    $overdue++;
                }
            }

            return $overdue;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
