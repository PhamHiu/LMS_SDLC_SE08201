<?php

namespace App\Services\Admin;

use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClassService
{
    public function paginate(?string $search = null, ?int $status = null): LengthAwarePaginator
    {
        return ClassRoom::with(['teacher', 'subject', 'major'])
            ->withCount('enrollments')
            ->when($search, fn ($q) => $q->where('class_name', 'like', "%{$search}%"))
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->orderByDesc('class_id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Tạo lớp + tự động sinh danh sách buổi học (mỗi tuần 1 buổi).
     */
    public function create(array $data): ClassRoom
    {
        return DB::transaction(function () use ($data) {
            $class = ClassRoom::create($data);
            $selectedSlots = json_decode($data['selected_slots'], true) ?? [];
            $this->generateSessions($class, $selectedSlots, $data['room_id'] ?? null);

            return $class;
        });
    }

    public function update(ClassRoom $class, array $data): ClassRoom
    {
        $class->update($data);

        return $class;
    }

    public function delete(ClassRoom $class): void
    {
        $class->delete();
    }

    /**
     * Sinh buổi học xoay vòng theo các slot đã chọn từ start_date theo total_sessions.
     */
    public function generateSessions(ClassRoom $class, array $selectedSlots, ?int $roomId): void
    {
        if (empty($selectedSlots)) return;

        $currentDate = Carbon::parse($class->start_date);
        $totalSessions = $class->total_sessions;
        $count = 0;

        // Group selected slots by day of week
        $slotsByDay = [];
        foreach ($selectedSlots as $slot) {
            $slotsByDay[$slot['day_of_week']][] = $slot['slot_id'];
        }

        $endDate = $currentDate->copy();

        while ($count < $totalSessions) {
            $dayOfWeek = $currentDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
            
            if (isset($slotsByDay[$dayOfWeek])) {
                sort($slotsByDay[$dayOfWeek]);
                foreach ($slotsByDay[$dayOfWeek] as $slotId) {
                    if ($count >= $totalSessions) break;
                    
                    ClassSession::create([
                        'class_id' => $class->class_id,
                        'session_date' => $currentDate->copy(),
                        'slot_id' => $slotId,
                        'room_id' => $roomId,
                    ]);
                    $endDate = $currentDate->copy();
                    $count++;
                }
            }
            $currentDate->addDay();
        }

        // Cập nhật ngày kết thúc cho class
        $class->update(['end_date' => $endDate]);
    }

    public function checkConflicts(string $sessionDate, int $slotId, int $teacherId, int $roomId): array
    {
        $conflicts = [];

        // Check room conflict
        $roomConflict = ClassSession::with('classRoom')
            ->where('session_date', $sessionDate)
            ->where('slot_id', $slotId)
            ->where('room_id', $roomId)
            ->first();

        if ($roomConflict) {
            $conflicts[] = "Phòng đã có lớp " . $roomConflict->classRoom->class_name;
        }

        // Check teacher conflict
        $teacherConflict = ClassSession::with('classRoom')
            ->where('session_date', $sessionDate)
            ->where('slot_id', $slotId)
            ->whereHas('classRoom', function ($query) use ($teacherId) {
                $query->where('user_id', $teacherId);
            })
            ->first();

        if ($teacherConflict) {
            $conflicts[] = "GV đã có lịch dạy lớp " . $teacherConflict->classRoom->class_name;
        }

        return [
            'is_conflict' => count($conflicts) > 0,
            'messages' => $conflicts,
        ];
    }

    public function getExistingTimetable(string $startDate, ?int $teacherId = null, ?int $roomId = null): array
    {
        $start = Carbon::parse($startDate);
        
        $query = ClassSession::with('classRoom')
            ->where('session_date', '>=', $start);

        if ($teacherId || $roomId) {
            $query->where(function($q) use ($teacherId, $roomId) {
                if ($roomId) {
                    $q->orWhere('room_id', $roomId);
                }
                if ($teacherId) {
                    $q->orWhereHas('classRoom', function($q2) use ($teacherId) {
                        $q2->where('user_id', $teacherId);
                    });
                }
            });
        }
            
        $sessions = $query->get();
            
        $result = [];
        foreach ($sessions as $session) {
            $date = Carbon::parse($session->session_date);
            $result[] = [
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $date->dayOfWeekIso,
                'slot_id' => $session->slot_id,
                'room_id' => $session->room_id,
                'class_name' => $session->classRoom->class_name,
                'teacher_id' => $session->classRoom->user_id,
            ];
        }
        
        return $result;
    }

    /**
     * Xếp sinh viên vào lớp (bỏ qua sinh viên đã ghi danh).
     */
    public function enrollStudents(ClassRoom $class, array $studentIds): int
    {
        $existing = $class->enrollments()->pluck('user_id')->all();
        $count = 0;

        foreach ($studentIds as $id) {
            if (! in_array((int) $id, $existing, true)) {
                Enrollment::create([
                    'user_id' => $id,
                    'class_id' => $class->class_id,
                    'enrolled_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function removeStudent(ClassRoom $class, User $student): void
    {
        $class->enrollments()->where('user_id', $student->user_id)->delete();
    }

    /**
     * Danh sách sinh viên chưa ghi danh vào lớp (để hiển thị form xếp lớp).
     */
    public function availableStudents(ClassRoom $class)
    {
        $enrolledIds = $class->enrollments()->pluck('user_id');

        return User::where('role', User::ROLE_STUDENT)
            ->whereNotIn('user_id', $enrolledIds)
            ->orderBy('full_name')
            ->get();
    }
}
