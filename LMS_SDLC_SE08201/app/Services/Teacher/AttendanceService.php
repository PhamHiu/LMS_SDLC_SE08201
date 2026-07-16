<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Dữ liệu cho form điểm danh: danh sách SV + trạng thái đã lưu (nếu có).
     */
    public function getSheet(ClassRoom $class, ClassSession $session): array
    {
        $students = $class->students()->orderBy('full_name')->get();

        $existing = Attendance::where('session_id', $session->session_id)
            ->get()
            ->keyBy('user_id');

        return [
            'students' => $students,
            'existing' => $existing,
        ];
    }

    /**
     * Lưu điểm danh hàng loạt (upsert theo user + session).
     */
    public function save(ClassSession $session, array $attendances): void
    {
        DB::transaction(function () use ($session, $attendances) {
            foreach ($attendances as $userId => $row) {
                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'session_id' => $session->session_id],
                    [
                        'status' => $row['status'],
                        'note' => $row['note'] ?? null,
                        'attendance_time' => now(),
                    ],
                );
            }
        });
    }
}
