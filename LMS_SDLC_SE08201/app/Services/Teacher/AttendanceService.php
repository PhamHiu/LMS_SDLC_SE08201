<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\AI\DropoutRiskService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Dữ liệu cho form điểm danh: danh sách SV + trạng thái đã lưu + cảnh báo nguy cơ AI + trạng thái khung giờ.
     */
    public function getSheet(ClassRoom $class, ClassSession $session, ?User $user = null): array
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

        $currentUser = $user ?? auth()->user();
        $windowStatus = $session->getAttendanceWindowStatus($currentUser);

        // Đếm số lượng theo trạng thái điểm danh hiện tại của buổi học này
        $currentPresentCount = $studentData->filter(fn ($item) => ($existing[$item['student']->user_id]->status ?? 'P') === 'P')->count();
        $currentLateCount = $studentData->filter(fn ($item) => ($existing[$item['student']->user_id]->status ?? '') === 'L')->count();
        $currentAbsentCount = $studentData->filter(fn ($item) => ($existing[$item['student']->user_id]->status ?? '') === 'A')->count();

        $realtimeService = app(\App\Services\Attendance\RealtimeScheduleService::class);
        $realtimeInfo = $realtimeService->getCurrentTimeInfo();

        return [
            'students' => $students,
            'student_data' => $studentData,
            'existing' => $existing,
            'total_students' => $students->count(),
            'high_risk_count' => $studentData->whereIn('risk_level', ['HIGH', 'VERY_HIGH'])->count(),
            'warning_count' => $studentData->where('risk_level', 'MEDIUM')->count(),
            'safe_count' => $studentData->where('risk_level', 'SAFE')->count(),
            'windowStatus' => $windowStatus,
            'realtimeInfo' => $realtimeInfo,
            'current_present_count' => $currentPresentCount,
            'current_late_count' => $currentLateCount,
            'current_absent_count' => $currentAbsentCount,
        ];
    }

    /**
     * Lưu điểm danh hàng loạt và kiểm tra chặt chẽ quy định khung giờ / quyền hạn Admin.
     */
    public function save(ClassSession $session, array $attendances, ?User $user = null): void
    {
        $currentUser = $user ?? auth()->user();
        $session->loadMissing('slot');
        $windowStatus = $session->getAttendanceWindowStatus($currentUser);

        $isAdmin = $currentUser && ($currentUser->isAdmin() || $currentUser->role === User::ROLE_ADMIN);

        // 1. Kiểm tra nếu chưa mở cửa sổ điểm danh (trước giờ bắt đầu 30 phút)
        if (! $isAdmin && $windowStatus['is_before_window']) {
            $openTimeStr = $windowStatus['open'] ? $windowStatus['open']->format('H:i d/m/Y') : 'trước 30 phút';
            throw ValidationException::withMessages([
                'attendance_window' => "Chưa đến thời gian điểm danh. Cửa sổ điểm danh sẽ mở lúc {$openTimeStr} (trước giờ bắt đầu ca học 30 phút).",
            ]);
        }

        // 2. Lấy dữ liệu điểm danh hiện tại trong CSDL để đối chiếu
        $existing = Attendance::where('session_id', $session->session_id)
            ->get()
            ->keyBy('user_id');

        // 3. Quy tắc sau khi hết giờ điểm danh (sau 15 phút vào ca học):
        // Giảng viên KHÔNG được phép cập nhật hoặc thay thế bằng "Có mặt" (P).
        // Chỉ được phép hủy điểm danh (Vắng - A) hoặc báo Đến muộn (L). Chỉ ADMIN mới có quyền xác nhận 'P'.
        if (! $isAdmin && $windowStatus['is_past_window']) {
            $studentLookup = User::whereIn('user_id', array_keys($attendances))->pluck('full_name', 'user_id');

            foreach ($attendances as $userId => $row) {
                $newStatus = $row['status'] ?? null;
                $existingStatus = $existing->get($userId)?->status;

                // Nếu học sinh chưa từng có trạng thái 'P' trước đó, hoặc đang bị sửa thành 'P':
                if ($newStatus === 'P' && $existingStatus !== 'P') {
                    $studentName = $studentLookup->get($userId, "ID #{$userId}");
                    throw ValidationException::withMessages([
                        "attendances.{$userId}.status" => "Đã hết thời gian điểm danh (sau khi vào ca 15 phút). Giảng viên không được phép đổi trạng thái học sinh \"{$studentName}\" thành Có mặt. Bạn chỉ được phép hủy điểm danh (Vắng - A) hoặc báo Đến muộn (L). Quyền xác nhận Có mặt sau giờ điểm danh thuộc về Quản trị viên (ADMIN).",
                    ]);
                }
            }
        }

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
