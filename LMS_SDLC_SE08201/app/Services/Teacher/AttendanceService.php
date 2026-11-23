<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public const MAX_RESTORATIONS_PER_SUBJECT = 1;

    /**
     * Đếm số lần sinh viên đã được khôi phục điểm danh trong cùng một môn học.
     */
    public function countRestorationsForSubject(int $studentId, int $subjectId): int
    {
        return Attendance::query()
            ->join('sessions as cs', 'cs.session_id', '=', 'attendances.session_id')
            ->join('classes as c', 'c.class_id', '=', 'cs.class_id')
            ->where('attendances.user_id', $studentId)
            ->where('attendances.is_restored', true)
            ->where('c.subject_id', $subjectId)
            ->count();
    }

    /**
     * Dữ liệu cho form điểm danh: danh sách SV + trạng thái đã lưu + lượt khôi phục điểm danh (1 lần/môn) + trạng thái khung giờ.
     */
    public function getSheet(ClassRoom $class, ClassSession $session, ?User $user = null): array
    {
        $students = $class->students()->orderBy('full_name')->get();
        $studentIds = $students->pluck('user_id');

        $existing = Attendance::where('session_id', $session->session_id)
            ->get()
            ->keyBy('user_id');

        $classSessionIds = $class->sessions()->pluck('session_id');
        $totalSessions = max(1, (int) $class->total_sessions);

        // Lấy tất cả lượt điểm danh của học sinh trong lớp này
        $allAttendances = Attendance::whereIn('session_id', $classSessionIds)
            ->whereIn('user_id', $studentIds)
            ->get()
            ->groupBy('user_id');

        // Đếm số lần đã khôi phục điểm danh theo MÔN HỌC (subject_id) cho từng sinh viên
        $restorationCounts = Attendance::query()
            ->join('sessions as cs', 'cs.session_id', '=', 'attendances.session_id')
            ->join('classes as c', 'c.class_id', '=', 'cs.class_id')
            ->whereIn('attendances.user_id', $studentIds)
            ->where('attendances.is_restored', true)
            ->where('c.subject_id', $class->subject_id)
            ->selectRaw('attendances.user_id, COUNT(*) as total')
            ->groupBy('attendances.user_id')
            ->pluck('total', 'attendances.user_id');

        $studentData = $students->map(function ($sv) use ($allAttendances, $totalSessions, $existing, $restorationCounts) {
            $userAttendances = $allAttendances->get($sv->user_id, collect());
            $absentCount = $userAttendances->where('status', 'A')->count();
            $lateCount = $userAttendances->where('status', 'L')->count();
            $presentCount = $userAttendances->where('status', 'P')->count();
            $absenceRate = round(($absentCount / $totalSessions) * 100, 1);

            // Quy chế đào tạo: nghỉ quá 20% tổng số buổi học → Trượt môn
            $attendanceFailed = $absenceRate > 20.0;
            $attendanceWarning = ($absenceRate >= 15.0 && $absenceRate <= 20.0);
            $effectiveStatusLevel = $attendanceFailed ? 'HIGH' : ($attendanceWarning ? 'MEDIUM' : 'SAFE');

            $currentRecord = $existing->get($sv->user_id);
            $currentStatus = $currentRecord->status ?? 'P';
            $restorationsUsed = (int) ($restorationCounts[$sv->user_id] ?? 0);
            $isRestoredThisSession = (bool) ($currentRecord?->is_restored ?? false);
            $canRestore = ! $isRestoredThisSession
                && $restorationsUsed < self::MAX_RESTORATIONS_PER_SUBJECT
                && in_array($currentStatus, [Attendance::ABSENT, Attendance::LATE], true);

            return [
                'student' => $sv,
                'existing_status' => $currentStatus,
                'existing_note' => $currentRecord->note ?? '',
                'is_restored' => $isRestoredThisSession,
                'restore_reason' => $currentRecord->restore_reason ?? null,
                'restorations_used' => $restorationsUsed,
                'max_restorations' => self::MAX_RESTORATIONS_PER_SUBJECT,
                'can_restore' => $canRestore,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'present_count' => $presentCount,
                'total_sessions' => $totalSessions,
                'max_allowed_absences' => (int) floor($totalSessions * 0.2),
                'absence_rate' => $absenceRate,
                'attendance_failed' => $attendanceFailed,
                'attendance_warning' => $attendanceWarning,
                'risk_level' => $effectiveStatusLevel,
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
    }

    /**
     * Khôi phục điểm danh cho 1 sinh viên trong 1 buổi học (tối đa 1 lần / 1 môn).
     */
    public function restoreAttendance(
        ClassRoom $class,
        ClassSession $session,
        User $student,
        User $actor,
        ?string $reason = null,
    ): Attendance {
        if ($session->class_id !== $class->class_id) {
            throw ValidationException::withMessages([
                'restore' => 'Buổi học không thuộc lớp này.',
            ]);
        }

        $isEnrolled = $class->students()->where('users.user_id', $student->user_id)->exists();
        if (! $isEnrolled) {
            throw ValidationException::withMessages([
                'restore' => 'Sinh viên không thuộc danh sách lớp học này.',
            ]);
        }

        $class->loadMissing('subject');
        $subjectName = $class->subject->subject_name ?? $class->class_name;
        $finalReason = trim((string) $reason) !== '' ? trim((string) $reason) : 'Đến muộn được giảng viên cho khôi phục điểm danh';

        $attendance = DB::transaction(function () use ($class, $session, $student, $actor, $subjectName, $finalReason) {
            $existing = Attendance::where('session_id', $session->session_id)
                ->where('user_id', $student->user_id)
                ->first();

            if ($existing && $existing->is_restored) {
                throw ValidationException::withMessages([
                    'restore' => "Buổi học này của sinh viên {$student->full_name} đã được khôi phục điểm danh trước đó.",
                ]);
            }

            if ($existing && $existing->status === Attendance::PRESENT) {
                throw ValidationException::withMessages([
                    'restore' => "Sinh viên {$student->full_name} đang ở trạng thái Có mặt (P), không cần khôi phục.",
                ]);
            }

            $usedCount = $this->countRestorationsForSubject($student->user_id, (int) $class->subject_id);
            if ($usedCount >= self::MAX_RESTORATIONS_PER_SUBJECT) {
                throw ValidationException::withMessages([
                    'restore' => "Sinh viên {$student->full_name} đã sử dụng hết " . self::MAX_RESTORATIONS_PER_SUBJECT . '/' . self::MAX_RESTORATIONS_PER_SUBJECT . " lượt khôi phục điểm danh cho môn {$subjectName}.",
                ]);
            }

            return Attendance::updateOrCreate(
                ['user_id' => $student->user_id, 'session_id' => $session->session_id],
                [
                    'status' => Attendance::PRESENT,
                    'attendance_time' => now(),
                    'note' => "[Khôi phục điểm danh 1/1] {$finalReason}",
                    'is_restored' => true,
                    'restored_at' => now(),
                    'restored_by' => $actor->user_id,
                    'restore_reason' => $finalReason,
                ],
            );
        });

        app(NotificationService::class)->notifyUser(
            author: $actor,
            recipientUserId: $student->user_id,
            title: "Đã khôi phục điểm danh: {$class->class_name} ({$session->session_date->format('d/m/Y')})",
            body: "Giảng viên {$actor->full_name} đã khôi phục điểm danh buổi {$session->session_date->format('d/m/Y')} môn {$subjectName} sang Có mặt (P). Lý do: {$finalReason}. Lưu ý: Bạn đã sử dụng 1/1 lượt khôi phục điểm danh cho môn học này.",
            targetUrl: route('student.attendance.index'),
            classId: $class->class_id,
        );

        return $attendance;
    }
}
