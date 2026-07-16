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
            $this->generateSessions($class, $data['room'] ?? null, $data['shift'] ?? null);

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
     * Sinh buổi học hàng tuần từ start_date theo total_sessions.
     */
    public function generateSessions(ClassRoom $class, ?string $room, ?string $shift): void
    {
        $start = Carbon::parse($class->start_date);
        for ($i = 0; $i < $class->total_sessions; $i++) {
            ClassSession::create([
                'class_id' => $class->class_id,
                'session_date' => $start->copy()->addWeeks($i),
                'room' => $room,
                'shift' => $shift,
            ]);
        }
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
