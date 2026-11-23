<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\Notification;
use App\Models\PolicyRule;
use App\Models\Room;
use App\Models\Slot;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RealisticLmsDataSyncSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // 1. Bảo đảm quy chế chuyên cần chuẩn (> 20%) đã xuất bản
        if (! PolicyRule::where('rule_code', PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE)->where('status', PolicyRule::STATUS_PUBLISHED)->exists()) {
            PolicyRule::create([
                'rule_code' => PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE,
                'title' => 'Quy chế chuyên cần chuẩn: Vắng quá 20% tổng số buổi bị trượt môn',
                'source_document' => 'Sổ tay sinh viên BTEC FPT ver.BTEC.9.2024',
                'clause_ref' => 'Mục Điểm danh & Chuyên cần',
                'excerpt' => 'Sinh viên vắng quá 20% tổng số buổi học của môn sẽ không đủ điều kiện dự thi / trượt môn.',
                'scope_type' => PolicyRule::SCOPE_GLOBAL,
                'effective_from' => $now->copy()->subYear()->toDateString(),
                'status' => PolicyRule::STATUS_PUBLISHED,
                'priority' => 100,
                'rule_type' => PolicyRule::TYPE_ATTENDANCE_THRESHOLD,
                'threshold_value' => 20.00,
                'operator' => PolicyRule::OP_GT,
                'unit' => 'PERCENT',
                'basis' => 'TOTAL_CLASS_SESSIONS',
            ]);
        }

        // 2. Đồng bộ total_sessions cho tất cả các lớp hiện có với số buổi thực tế
        foreach (ClassRoom::all() as $cls) {
            $actual = $cls->sessions()->count();
            if ($actual > 0 && (int) $cls->total_sessions !== $actual) {
                $cls->update(['total_sessions' => $actual]);
            } elseif ($actual === 0 && (int) $cls->total_sessions <= 0) {
                $cls->update(['total_sessions' => 15]);
            }
        }

        // 3. Tạo lớp học + 15 buổi học + điểm danh + bài tập cho 3 môn đang thiếu lớp (OOP102, MKT101, SWE2011)
        $teachers = User::where('role', User::ROLE_TEACHER)->orderBy('user_id')->get();
        $students = User::where('role', User::ROLE_STUDENT)->orderBy('user_id')->take(8)->get();
        $slots = Slot::orderBy('slot_number')->get()->keyBy('slot_number');
        $rooms = Room::orderBy('room_id')->get();
        $defaultMajor = Major::first();

        if ($teachers->isEmpty() || $slots->isEmpty() || $rooms->isEmpty() || ! $defaultMajor) {
            return;
        }

        $missingConfigs = [
            [
                'subject_code' => 'OOP102',
                'class_name' => 'OOP19305',
                'total_sessions' => 15,
                'days_of_week' => [1, 4], // Thứ 2 & Thứ 5
                'slot_number' => 2,
                'room_idx' => 0,
                'teacher_idx' => 0,
            ],
            [
                'subject_code' => 'MKT101',
                'class_name' => 'MKT19306',
                'total_sessions' => 15,
                'days_of_week' => [2, 5], // Thứ 3 & Thứ 6
                'slot_number' => 2,
                'room_idx' => 1,
                'teacher_idx' => 1 % $teachers->count(),
            ],
            [
                'subject_code' => 'SWE2011',
                'class_name' => 'SWE19307',
                'total_sessions' => 15,
                'days_of_week' => [1, 3], // Thứ 2 & Thứ 4
                'slot_number' => 4,
                'room_idx' => 2 % $rooms->count(),
                'teacher_idx' => 0,
            ],
        ];

        foreach ($missingConfigs as $cfg) {
            $subject = Subject::where('subject_code', $cfg['subject_code'])->first();
            if (! $subject) {
                continue;
            }

            $class = ClassRoom::where('class_name', $cfg['class_name'])->first();
            $teacher = $teachers[$cfg['teacher_idx']];
            $slot = $slots[$cfg['slot_number']] ?? $slots->first();
            $room = $rooms[$cfg['room_idx'] % $rooms->count()];

            if (! $class) {
                $startDate = $now->copy()->subDays(14)->startOfWeek(Carbon::MONDAY);
                $class = ClassRoom::create([
                    'user_id' => $teacher->user_id,
                    'subject_id' => $subject->subject_id,
                    'major_id' => $subject->major_id ?? $defaultMajor->major_id,
                    'class_name' => $cfg['class_name'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $startDate->copy()->addWeeks(8)->toDateString(),
                    'total_sessions' => $cfg['total_sessions'],
                    'status' => ClassRoom::STATUS_ACTIVE,
                ]);
            }

            // Sinh đủ 15 buổi học nếu lớp chưa có đủ lịch
            if ($class->sessions()->count() < $cfg['total_sessions']) {
                $class->sessions()->delete();
                $cursor = Carbon::parse($class->start_date, 'Asia/Ho_Chi_Minh');
                $created = 0;
                $lastDate = $cursor->copy();

                while ($created < $cfg['total_sessions']) {
                    if (in_array($cursor->dayOfWeekIso, $cfg['days_of_week'], true)) {
                        ClassSession::create([
                            'class_id' => $class->class_id,
                            'session_date' => $cursor->toDateString(),
                            'slot_id' => $slot->slot_id,
                            'room_id' => $room->room_id,
                        ]);
                        $lastDate = $cursor->copy();
                        $created++;
                    }
                    $cursor->addDay();
                }

                $class->update([
                    'total_sessions' => $created,
                    'end_date' => $lastDate->toDateString(),
                ]);
            }

            // Ghi danh sinh viên (bao gồm student1@lms.vn) vào các lớp mới
            foreach ($students as $sv) {
                Enrollment::firstOrCreate([
                    'user_id' => $sv->user_id,
                    'class_id' => $class->class_id,
                ]);
            }

            // Sinh điểm danh thực tế cho các buổi đã qua (<= hôm nay)
            $pastSessions = $class->sessions()->whereDate('session_date', '<=', $now->toDateString())->orderBy('session_date')->get();
            foreach ($pastSessions as $sIdx => $sess) {
                foreach ($students as $uIdx => $sv) {
                    $status = Attendance::PRESENT;
                    $note = null;
                    if ($sIdx === 1 && $uIdx === 0) {
                        $status = Attendance::LATE;
                        $note = 'Đến muộn 10 phút';
                    } elseif ($sIdx === 2 && $uIdx === 0) {
                        $status = Attendance::ABSENT;
                        $note = 'Nghỉ có việc gia đình';
                    } elseif ($uIdx === 2 && $sIdx < 4) {
                        $status = Attendance::ABSENT;
                        $note = 'Vắng không phép';
                    }

                    Attendance::firstOrCreate(
                        ['user_id' => $sv->user_id, 'session_id' => $sess->session_id],
                        ['status' => $status, 'note' => $note, 'attendance_time' => $sess->session_date]
                    );
                }
            }

            // Sinh bài tập Assignment (đã công bố điểm) + 1 Assignment (chờ công bố điểm)
            $asgPublished = Assignment::firstOrCreate(
                ['class_id' => $class->class_id, 'title' => "Assignment 1: Thực hành {$subject->subject_code}"],
                [
                    'description' => "Hoàn thành bài tập thực hành môn {$subject->subject_name} và nộp báo cáo PDF/DOCX.",
                    'type' => 'essay',
                    'due_date' => $now->copy()->subDays(2),
                    'grades_published_at' => $now->copy()->subDay(),
                ]
            );

            $asgDraft = Assignment::firstOrCreate(
                ['class_id' => $class->class_id, 'title' => "Assignment 2: Đồ án giữa kỳ {$subject->subject_code}"],
                [
                    'description' => "Nộp báo cáo đồ án giữa kỳ môn {$subject->subject_name}.",
                    'type' => 'essay',
                    'due_date' => $now->copy()->addDays(5),
                    'grades_published_at' => null,
                ]
            );

            foreach ($students->take(4) as $idx => $sv) {
                Submission::firstOrCreate(
                    ['user_id' => $sv->user_id, 'assignment_id' => $asgPublished->assignment_id],
                    [
                        'submitted_at' => $now->copy()->subDays(3),
                        'score' => 7.5 + ($idx % 3) * 0.5,
                        'feedback' => 'Bài làm rõ ràng, đáp ứng tốt yêu cầu đề bài.',
                        'graded_at' => $now->copy()->subDay(),
                    ]
                );

                Submission::firstOrCreate(
                    ['user_id' => $sv->user_id, 'assignment_id' => $asgDraft->assignment_id],
                    [
                        'submitted_at' => $now->copy()->subHours(6),
                        'score' => 8.0 + ($idx % 2) * 0.5,
                        'feedback' => 'Đã chấm, chờ giảng viên bấm Công bố điểm của lớp.',
                        'graded_at' => $now->copy()->subHours(2),
                    ]
                );
            }

            // Tạo thông báo chào mừng lớp học
            Notification::firstOrCreate(
                ['class_id' => $class->class_id, 'title' => "Khai giảng lớp {$class->class_name} — {$subject->subject_name}"],
                [
                    'user_id' => $teacher->user_id,
                    'body' => "Chào mừng các em đến với lớp {$class->class_name} ({$subject->subject_name}). Tổng thời lượng môn học gồm {$class->total_sessions} buổi. Các em chú ý đi học đầy đủ để không vượt ngưỡng vắng 20%.",
                ]
            );
        }
    }
}
