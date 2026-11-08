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
 * CORE RULE ENGINE — STUDENT DROPOUT RISK ANALYSIS
 * Tuân thủ 100% quy tắc nghiệp vụ BestCoder 2026:
 * 1. Nghỉ QUÁ 20% (> 20%) tổng số buổi môn học mới trượt chuyên cần (nghỉ đúng 20% = CHƯA TRƯỢT).
 * 2. Tính riêng biệt từng môn, không cộng gộp tổng số buổi nghỉ các môn.
 * 3. 4 mức nguy cơ chuẩn xác: SAFE, MEDIUM, HIGH, VERY_HIGH.
 * 4. Luôn kèm giải thích minh bạch (Explainable AI) và khuyến nghị sư phạm.
 */
class DropoutRiskRuleEngine
{
    public const PASSING_SCORE = 5.0; // Thang điểm 10, >= 5.0 là PASS

    /**
     * Phân tích nguy cơ bỏ học toàn diện cho 1 sinh viên.
     */
    public function evaluate(User $student): array
    {
        $enrolledClasses = $student->enrolledClasses()->with(['subject', 'sessions.attendances'])->get();

        if ($enrolledClasses->isEmpty()) {
            return [
                'risk_level' => RiskPrediction::LEVEL_SAFE,
                'risk_score' => 0.0,
                'analysis_method' => 'RULE_ENGINE',
                'model_version' => 'v1.0-rules',
                'summary_explanation' => 'Học sinh chưa đăng ký lớp học nào hoặc chưa có dữ liệu đào tạo.',
                'recommendations' => 'Cần đăng ký môn học và hoàn thiện kế hoạch học tập.',
                'data_quality_status' => 'INSUFFICIENT',
                'factors' => [],
                'subjects_summary' => [],
            ];
        }

        $subjectAnalyses = [];
        $factors = [];
        $totalSubjects = $enrolledClasses->count();
        $passedSubjects = 0;
        $failedSubjects = 0;
        $attendanceFailedSubjects = 0;
        $warningAttendanceSubjects = 0; // Tiệm cận 20% (ví dụ 15% - 20%)

        $totalScoreSum = 0;
        $gradedSubjectsCount = 0;

        foreach ($enrolledClasses as $class) {
            $analysis = $this->analyzeSubject($student, $class);
            $subjectAnalyses[] = $analysis;

            if ($analysis['attendance_failed']) {
                $attendanceFailedSubjects++;
                $factors[] = [
                    'subject_id' => $class->subject_id,
                    'factor_type' => RiskFactor::TYPE_ATTENDANCE,
                    'title' => 'Trượt điều kiện chuyên cần môn ' . $class->subject->subject_name,
                    'description' => sprintf(
                        'Học sinh đã nghỉ %d/%d buổi (%.1f%%), vượt quá ngưỡng 20%% theo quy định đào tạo.',
                        $analysis['absent_sessions'],
                        $analysis['total_sessions'],
                        $analysis['absence_rate']
                    ),
                    'severity' => 'CRITICAL',
                    'impact_weight' => 35.0,
                ];
            } elseif ($analysis['absence_rate'] >= 15.0 && $analysis['absence_rate'] <= 20.0) {
                $warningAttendanceSubjects++;
                $factors[] = [
                    'subject_id' => $class->subject_id,
                    'factor_type' => RiskFactor::TYPE_ATTENDANCE,
                    'title' => 'Chuyên cần tiệm cận ngưỡng nghỉ 20% môn ' . $class->subject->subject_name,
                    'description' => sprintf(
                        'Học sinh đã nghỉ %d/%d buổi (%.1f%%). Đang ở ngưỡng cảnh báo sớm, nếu nghỉ thêm 1 buổi nữa sẽ vượt giới hạn.',
                        $analysis['absent_sessions'],
                        $analysis['total_sessions'],
                        $analysis['absence_rate']
                    ),
                    'severity' => 'WARNING',
                    'impact_weight' => 15.0,
                ];
            }

            if ($analysis['status'] === 'FAIL') {
                $failedSubjects++;
                $factors[] = [
                    'subject_id' => $class->subject_id,
                    'factor_type' => RiskFactor::TYPE_GRADE,
                    'title' => 'Chưa đạt điểm chuẩn môn ' . $class->subject->subject_name,
                    'description' => sprintf(
                        'Điểm tổng kết/trung bình bài tập hiện tại là %.2f/10 (dưới chuẩn 5.0).',
                        $analysis['average_score']
                    ),
                    'severity' => 'WARNING',
                    'impact_weight' => 25.0,
                ];
            } elseif ($analysis['status'] === 'PASS') {
                $passedSubjects++;
            }

            if ($analysis['average_score'] !== null) {
                $totalScoreSum += $analysis['average_score'];
                $gradedSubjectsCount++;
            }
        }

        $overallGpa = $gradedSubjectsCount > 0 ? round($totalScoreSum / $gradedSubjectsCount, 2) : null;

        // KIỂM TRA LỊCH SỬ HỌC LẠI (Nếu học cùng 1 môn học ở nhiều hơn 1 lớp)
        $subjectIds = $enrolledClasses->pluck('subject_id');
        $duplicateSubjects = $subjectIds->duplicates();
        $retakeCount = $duplicateSubjects->count();

        if ($retakeCount > 0) {
            $factors[] = [
                'subject_id' => null,
                'factor_type' => RiskFactor::TYPE_RETAKE,
                'title' => 'Lịch sử học lại môn học',
                'description' => sprintf('Sinh viên có %d môn đang trong trạng thái học lại/đăng ký lại.', $retakeCount),
                'severity' => 'WARNING',
                'impact_weight' => 20.0,
            ];
        }

        // MA TRẬN PHÂN LOẠI 4 MỨC NGUY CƠ TUÂN THỦ NGHIÊM NGẶT QUY TẮC BESTCODER
        $riskLevel = RiskPrediction::LEVEL_SAFE;
        $riskScore = 0.0;
        $summaryExplanation = '';
        $recommendations = '';

        $maxAbsenceRate = collect($subjectAnalyses)->max('absence_rate') ?? 0;

        // Tình huống A: PASS toàn bộ, không học lại, không môn nào > 20%
        if ($failedSubjects === 0 && $attendanceFailedSubjects === 0 && $retakeCount === 0 && $warningAttendanceSubjects === 0) {
            $riskLevel = RiskPrediction::LEVEL_SAFE;
            $riskScore = max(5.0, round(10.0 - ($overallGpa ?? 8.0), 1));
            $summaryExplanation = sprintf(
                'Học sinh có kết quả học tập ổn định: PASS %d/%d môn, không có môn học lại và chuyên cần mọi môn đều dưới ngưỡng 20%%. Chưa ghi nhận dấu hiệu rủi ro học tập đáng kể.',
                $passedSubjects,
                $totalSubjects
            );
            $recommendations = 'Duy trì nhịp độ học tập hiện tại; khuyến khích tham gia các chuyên đề nâng cao và hỗ trợ các bạn trong lớp.';
        }
        // Tình huống RẤT CAO (VERY_HIGH): Vừa trượt chuyên cần nhiều môn, vừa FAIL nhiều môn, hoặc vắng cực cao >= 35% kèm điểm trượt
        elseif ($attendanceFailedSubjects >= 2 || ($attendanceFailedSubjects >= 1 && $failedSubjects >= 2) || ($failedSubjects >= 3 && $retakeCount >= 1) || ($attendanceFailedSubjects >= 1 && $failedSubjects >= 1 && $maxAbsenceRate >= 35.0)) {
            $riskLevel = RiskPrediction::LEVEL_VERY_HIGH;
            $riskScore = min(98.0, 75.0 + ($attendanceFailedSubjects * 8.0) + ($failedSubjects * 5.0) + ($retakeCount * 4.0));
            $summaryExplanation = sprintf(
                'Nguy cơ bỏ học rất cao: Học sinh trượt chuyên cần nghiêm trọng (vắng cao nhất %.1f%%, vượt xa ngưỡng 20%%), đồng thời điểm số không đạt chuẩn. Tích lũy nhiều dấu hiệu suy giảm học lực nghiêm trọng.',
                $maxAbsenceRate
            );
            $recommendations = 'Cần tổ chức buổi gặp mặt khẩn cấp giữa Cố vấn học tập, Giảng viên bộ môn và Sinh viên; xây dựng kế hoạch cứu vãn học tập cá nhân hóa ngay lập tức.';
        }
        // Tình huống CAO (HIGH): Có 1 môn trượt chuyên cần (> 20%) HOẶC FAIL từ 2 môn trở lên
        elseif ($attendanceFailedSubjects >= 1 || $failedSubjects >= 2 || ($failedSubjects >= 1 && $retakeCount >= 1)) {
            $riskLevel = RiskPrediction::LEVEL_HIGH;
            $riskScore = min(74.0, 50.0 + ($attendanceFailedSubjects * 12.0) + ($failedSubjects * 8.0));
            $summaryExplanation = sprintf(
                'Mức cảnh báo cao: Học sinh đang gặp rào cản học tập rõ rệt (có %d môn trượt điểm danh hoặc chưa đạt %d môn). Cần can thiệp sư phạm sớm để tránh tích lũy học phần nợ.',
                $attendanceFailedSubjects,
                $failedSubjects
            );
            $recommendations = 'Giảng viên cần gặp gỡ nhắc nhở trực tiếp; kết nối sinh viên với AI Tutor để phụ đạo các phần kiến thức hổng.';
        }
        // Tình huống TRUNG BÌNH (MEDIUM): Có môn tiệm cận ngưỡng 20% (15-20%) hoặc FAIL 1 môn hoặc điểm GPA thấp
        else {
            $riskLevel = RiskPrediction::LEVEL_MEDIUM;
            $riskScore = min(49.0, 25.0 + ($warningAttendanceSubjects * 6.0) + ($failedSubjects * 10.0));
            $summaryExplanation = sprintf(
                'Mức cảnh báo trung bình: Kết quả học tập chưa thực sự ổn định (%d môn tiệm cận ngưỡng nghỉ 20%% hoặc điểm bài tập chưa đạt). Chưa có nguy cơ bỏ học ngay nhưng cần theo dõi sát.',
                $warningAttendanceSubjects
            );
            $recommendations = 'Nhắc nhở học sinh đảm bảo tham gia đầy đủ các buổi học tiếp theo và hoàn thành các bài tập bổ sung trên hệ thống.';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'analysis_method' => 'RULE_ENGINE',
            'model_version' => 'v1.0-rules',
            'summary_explanation' => $summaryExplanation,
            'recommendations' => $recommendations,
            'data_quality_status' => $gradedSubjectsCount > 0 ? 'COMPLETE' : 'PARTIAL',
            'factors' => $factors,
            'subjects_summary' => $subjectAnalyses,
            'overall_gpa' => $overallGpa,
            'passed_count' => $passedSubjects,
            'failed_count' => $failedSubjects,
            'attendance_failed_count' => $attendanceFailedSubjects,
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
        $attendanceFailed = $absenceRate > 20.0;

        // Tính điểm trung bình các bài tập trong lớp
        $assignmentIds = $class->assignments()->pluck('assignment_id');
        $submissions = Submission::where('user_id', $student->user_id)
            ->whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('score')
            ->get();

        $averageScore = $submissions->isNotEmpty() ? round($submissions->avg('score'), 2) : null;

        $status = 'IN_PROGRESS';
        if ($attendanceFailed) {
            $status = 'FAIL'; // Trượt chuyên cần thì môn đó tính là FAIL
        } elseif ($averageScore !== null) {
            $status = $averageScore >= self::PASSING_SCORE ? 'PASS' : 'FAIL';
        }

        return [
            'class_id' => $class->class_id,
            'class_name' => $class->class_name,
            'subject_id' => $class->subject_id,
            'subject_code' => $class->subject->subject_code,
            'subject_name' => $class->subject->subject_name,
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
        ];
    }
}

