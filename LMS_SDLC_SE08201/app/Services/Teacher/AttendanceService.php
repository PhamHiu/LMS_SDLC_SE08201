<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\AI\DropoutRiskService;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Dữ liệu cho form điểm danh: danh sách SV + trạng thái đã lưu + cảnh báo nguy cơ AI.
     */
    public function getSheet(ClassRoom $class, ClassSession $session): array
    {
        $students = $class->students()->orderBy('full_name')->get();

        $existing = Attendance::where('session_id', $session->session_id)
            ->get()
            ->keyBy('user_id');

        $classSessionIds = $class->sessions()->pluck('session_id');
        $totalSessions = max(1, (int) $class->total_sessions);

        // Lấy tất cả lượt điểm danh của học sinh trong lớp này
        $allAttendances = Attendance::whereIn('session_id', $classSessionIds)
            ->whereIn('user_id', $students->pluck('user_id'))
            ->get()
            ->groupBy('user_id');

        $riskService = app(DropoutRiskService::class);

        $studentData = $students->map(function ($sv) use ($allAttendances, $totalSessions, $existing, $riskService) {
            $userAttendances = $allAttendances->get($sv->user_id, collect());
            $absentCount = $userAttendances->where('status', 'A')->count();
            $lateCount = $userAttendances->where('status', 'L')->count();
            $presentCount = $userAttendances->where('status', 'P')->count();
            $absenceRate = round(($absentCount / $totalSessions) * 100, 1);

            $attendanceFailed = $absenceRate > 20.0;
            $attendanceWarning = ($absenceRate >= 15.0 && $absenceRate <= 20.0);

            // Đánh giá nguy cơ từ Risk Service
            $prediction = $riskService->getLatestOrAnalyze($sv);

            // Mức nguy cơ hiệu lực theo môn học này
            $effectiveRiskLevel = $prediction->risk_level;
            if ($attendanceFailed && ! in_array($effectiveRiskLevel, ['HIGH', 'VERY_HIGH'])) {
                $effectiveRiskLevel = 'HIGH';
            } elseif ($attendanceWarning && $effectiveRiskLevel === 'SAFE') {
                $effectiveRiskLevel = 'MEDIUM';
            }

            return [
                'student' => $sv,
                'existing_status' => $existing[$sv->user_id]->status ?? 'P',
                'existing_note' => $existing[$sv->user_id]->note ?? '',
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'present_count' => $presentCount,
                'total_sessions' => $totalSessions,
                'absence_rate' => $absenceRate,
                'attendance_failed' => $attendanceFailed,
                'attendance_warning' => $attendanceWarning,
                'risk_prediction' => $prediction,
                'risk_level' => $effectiveRiskLevel,
            ];
        });

        // Sắp xếp danh sách sinh viên: đưa các bạn nguy cơ cao (VERY_HIGH, HIGH, MEDIUM) lên đầu hoặc theo tên
        return [
            'students' => $students,
            'student_data' => $studentData,
            'existing' => $existing,
            'total_students' => $students->count(),
            'high_risk_count' => $studentData->whereIn('risk_level', ['HIGH', 'VERY_HIGH'])->count(),
            'warning_count' => $studentData->where('risk_level', 'MEDIUM')->count(),
            'safe_count' => $studentData->where('risk_level', 'SAFE')->count(),
        ];
    }

    /**
     * Lưu điểm danh hàng loạt (upsert theo user + session) và tự động cập nhật AI Risk.
     */
    public function save(ClassSession $session, array $attendances): void
    {
        $updatedUserIds = [];

        DB::transaction(function () use ($session, $attendances, &$updatedUserIds) {
            foreach ($attendances as $userId => $row) {
                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'session_id' => $session->session_id],
                    [
                        'status' => $row['status'],
                        'note' => $row['note'] ?? null,
                        'attendance_time' => now(),
                    ],
                );
                $updatedUserIds[] = $userId;
            }
        });

        // Tự động phân tích lại nguy cơ AI cho các sinh viên được điểm danh
        $riskService = app(DropoutRiskService::class);
        $studentsToReevaluate = User::whereIn('user_id', $updatedUserIds)->get();
        foreach ($studentsToReevaluate as $st) {
            try {
                $riskService->analyzeAndRecord($st);
            } catch (\Throwable $e) {
                // Keep resilient
            }
        }
    }
}
