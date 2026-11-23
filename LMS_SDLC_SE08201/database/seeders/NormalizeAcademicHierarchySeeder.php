<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\Material;
use App\Models\Room;
use App\Models\Slot;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NormalizeAcademicHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Chuẩn hóa 4 Chuyên ngành
        $majorsData = [
            'IT' => 'Công nghệ thông tin',
            'SE' => 'Kỹ thuật phần mềm',
            'WD' => 'Phát triển Ứng dụng Web',
            'EC' => 'Thương mại điện tử & Digital Marketing',
        ];

        $majors = [];
        foreach ($majorsData as $code => $name) {
            $majors[$code] = Major::updateOrCreate(
                ['major_code' => $code],
                ['major_name' => $name]
            );
        }

        // 2. Đảm bảo đủ 12 môn học cho 4 chuyên ngành
        $extraSubjects = [
            ['subject_code' => 'NET201', 'subject_name' => 'Mạng máy tính & An ninh cơ bản'],
            ['subject_code' => 'ECM201', 'subject_name' => 'Quản trị Thương mại điện tử'],
        ];
        foreach ($extraSubjects as $sub) {
            Subject::firstOrCreate(
                ['subject_code' => $sub['subject_code']],
                ['subject_name' => $sub['subject_name']]
            );
        }

        // 3. Ánh xạ chuẩn Môn học <-> Chuyên ngành (major_subject)
        $majorSubjectsMap = [
            'WD' => ['WEB301', 'WEB2013', 'UIX201', 'DB201'],
            'SE' => ['DSA101', 'PRO192', 'SWE2011', 'DBI202'],
            'IT' => ['OOP102', 'DB201', 'DSA101', 'NET201'],
            'EC' => ['MKT101', 'UIX201', 'ECM201'],
        ];

        foreach ($majorSubjectsMap as $majorCode => $subjectCodes) {
            $subjectIds = Subject::whereIn('subject_code', $subjectCodes)->pluck('subject_id')->all();
            $majors[$majorCode]->subjects()->sync($subjectIds);
        }

        // 4. Đảm bảo ngành WD cũng có sinh viên để test đầy đủ cả 4 ngành (IT, SE, WD, EC)
        User::whereIn('email', ['student4@lms.vn', 'student7@lms.vn', 'student10@lms.vn'])
            ->update(['major_id' => $majors['WD']->major_id]);

        // 5. Tạo lớp học đầy đủ cho 2 môn mới (NET201 & ECM201) nếu chưa có lớp
        $teachersByMajor = [
            'IT' => User::where('email', 'teacher1@lms.vn')->first() ?? User::where('role', User::ROLE_TEACHER)->first(),
            'SE' => User::where('email', 'teacher2@lms.vn')->first() ?? User::where('role', User::ROLE_TEACHER)->first(),
            'EC' => User::where('email', 'teacher3@lms.vn')->first() ?? User::where('role', User::ROLE_TEACHER)->first(),
            'WD' => User::where('email', 'thaonp@lms.vn')->first() ?? User::where('role', User::ROLE_TEACHER)->first(),
        ];

        $rooms = Room::orderBy('room_id')->get();
        $slots = Slot::orderBy('slot_number')->get();

        $ensureClasses = [
            ['subject_code' => 'NET201', 'major_code' => 'IT', 'class_name' => 'IT19303-NET201', 'days' => [3, 6], 'slot_idx' => 4, 'room_idx' => 3],
            ['subject_code' => 'ECM201', 'major_code' => 'EC', 'class_name' => 'EC19303-ECM201', 'days' => [2, 5], 'slot_idx' => 4, 'room_idx' => 4],
        ];

        foreach ($ensureClasses as $cfg) {
            $subject = Subject::where('subject_code', $cfg['subject_code'])->first();
            if (! $subject) {
                continue;
            }
            $class = ClassRoom::where('subject_id', $subject->subject_id)->first();
            if (! $class) {
                $teacher = $teachersByMajor[$cfg['major_code']];
                $start = Carbon::today()->subDays(10);
                $class = ClassRoom::create([
                    'class_name' => $cfg['class_name'],
                    'subject_id' => $subject->subject_id,
                    'major_id' => $majors[$cfg['major_code']]->major_id,
                    'user_id' => $teacher->user_id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->addDays(42)->toDateString(),
                    'total_sessions' => 12,
                    'status' => 1,
                ]);

                // Sinh 12 buổi học cho lớp mới
                $slot = $slots[$cfg['slot_idx']] ?? $slots->first();
                $room = $rooms[$cfg['room_idx']] ?? $rooms->first();
                $cur = $start->copy();
                $created = 0;
                $lastDate = $start->copy();
                while ($created < 12) {
                    if (in_array($cur->dayOfWeekIso, $cfg['days'], true)) {
                        ClassSession::create([
                            'class_id' => $class->class_id,
                            'session_date' => $cur->toDateString(),
                            'slot_id' => $slot?->slot_id,
                            'room_id' => $room?->room_id,
                        ]);
                        $lastDate = $cur->copy();
                        $created++;
                    }
                    $cur->addDay();
                }
                $class->update(['end_date' => $lastDate->toDateString()]);
            }
        }

        // 6. Chuẩn hóa quan hệ Ngành -> Môn -> Tên lớp cho toàn bộ các lớp
        $subjectPrimaryMajor = [
            'WEB301'  => 'WD',
            'WEB2013' => 'WD',
            'DB201'   => 'IT',
            'OOP102'  => 'IT',
            'NET201'  => 'IT',
            'DSA101'  => 'SE',
            'PRO192'  => 'SE',
            'DBI202'  => 'SE',
            'SWE2011' => 'SE',
            'UIX201'  => 'EC',
            'MKT101'  => 'EC',
            'ECM201'  => 'EC',
        ];

        $counters = [];
        $allClasses = ClassRoom::with('subject')->orderBy('class_id')->get();
        foreach ($allClasses as $class) {
            $subCode = $class->subject?->subject_code;
            if (! $subCode) {
                continue;
            }
            $majorCode = $subjectPrimaryMajor[$subCode] ?? 'IT';
            $major = $majors[$majorCode];

            $counters[$majorCode] = ($counters[$majorCode] ?? 0) + 1;
            $seq = str_pad((string) $counters[$majorCode], 2, '0', STR_PAD_LEFT);
            $standardizedName = "{$majorCode}193{$seq}-{$subCode}";

            $class->update([
                'major_id' => $major->major_id,
                'class_name' => $standardizedName,
            ]);
        }

        // 7. Đảm bảo 100% các môn đều có tối thiểu 2 học liệu & 100% các lớp đều có sinh viên + 2 bài tập để test
        $defaultTeacherId = $teachersByMajor['IT']->user_id;
        foreach (Subject::all() as $subject) {
            $matCount = Material::where('subject_id', $subject->subject_id)->count();
            if ($matCount < 2) {
                Material::firstOrCreate(
                    ['subject_id' => $subject->subject_id, 'title' => "Đề cương & Slide tổng quan {$subject->subject_name}"],
                    [
                        'user_id' => $defaultTeacherId,
                        'description' => "Tài liệu nền tảng và mục tiêu chuẩn đầu ra môn {$subject->subject_name} ({$subject->subject_code}).",
                        'file_path' => 'materials/sample_syllabus.txt',
                    ]
                );
                Material::firstOrCreate(
                    ['subject_id' => $subject->subject_id, 'title' => "Bài tập thực hành & Case Study {$subject->subject_code}"],
                    [
                        'user_id' => $defaultTeacherId,
                        'description' => "Bộ bài tập thực hành chuẩn BTEC cho môn {$subject->subject_name}.",
                        'file_path' => 'materials/sample_lab.txt',
                    ]
                );
            }
        }

        $allStudents = User::where('role', User::ROLE_STUDENT)->orderBy('user_id')->get();
        foreach (ClassRoom::with('subject')->get() as $class) {
            // Đảm bảo lớp có ít nhất 6 sinh viên
            if ($class->enrollments()->count() === 0) {
                $sameMajorStudents = $allStudents->where('major_id', $class->major_id)->take(6);
                if ($sameMajorStudents->count() < 6) {
                    $sameMajorStudents = $sameMajorStudents->merge($allStudents->take(6))->unique('user_id')->take(6);
                }
                foreach ($sameMajorStudents as $sv) {
                    Enrollment::firstOrCreate(
                        ['user_id' => $sv->user_id, 'class_id' => $class->class_id],
                        ['enrolled_at' => now()->subDays(10)]
                    );
                }
            }

            // Đảm bảo lớp có ít nhất 1 bài tự luận + 1 bài Quiz
            if ($class->assignments()->count() < 2) {
                $subName = $class->subject?->subject_name ?? $class->class_name;
                Assignment::firstOrCreate(
                    ['class_id' => $class->class_id, 'type' => Assignment::TYPE_ESSAY],
                    [
                        'title' => "Bài tập tự luận 1 — {$subName}",
                        'description' => "Phân tích và triển khai bài tập ứng dụng cho môn {$subName}.",
                        'due_date' => now()->addDays(10),
                        'max_score' => 10,
                    ]
                );
                Assignment::firstOrCreate(
                    ['class_id' => $class->class_id, 'type' => Assignment::TYPE_QUIZ],
                    [
                        'title' => "Quiz kiểm tra kiến thức — {$subName}",
                        'description' => "Trắc nghiệm ôn tập kiến thức trọng tâm môn {$subName}.",
                        'due_date' => now()->addDays(7),
                        'max_score' => 10,
                    ]
                );
            }
        }

        // 8. Tự động khử 100% xung đột phòng học và xung đột giảng viên trong bảng sessions
        $allSlots = Slot::orderBy('slot_number')->pluck('slot_id')->all();
        $allRooms = Room::orderBy('room_id')->pluck('room_id')->all();

        $usedRoomSlots = [];
        $usedTeacherSlots = [];

        $sessions = ClassSession::with('classRoom')->orderBy('session_date')->orderBy('session_id')->get();
        foreach ($sessions as $s) {
            if (! $s->classRoom || ! $s->slot_id || ! $s->room_id) {
                continue;
            }
            $date = $s->session_date->toDateString();
            $tid = (int) $s->classRoom->user_id;
            $slotId = (int) $s->slot_id;
            $roomId = (int) $s->room_id;

            $roomKey = "{$date}|{$slotId}|{$roomId}";
            $teacherKey = "{$date}|{$slotId}|{$tid}";

            if (isset($usedRoomSlots[$roomKey]) || isset($usedTeacherSlots[$teacherKey])) {
                // Tìm cặp (slot_id, room_id) trống trên cùng ngày cho cả GV lẫn phòng
                $resolved = false;
                foreach ($allSlots as $candSlot) {
                    $tKey = "{$date}|{$candSlot}|{$tid}";
                    if (isset($usedTeacherSlots[$tKey])) {
                        continue;
                    }
                    foreach ($allRooms as $candRoom) {
                        $rKey = "{$date}|{$candSlot}|{$candRoom}";
                        if (! isset($usedRoomSlots[$rKey])) {
                            $s->update([
                                'slot_id' => $candSlot,
                                'room_id' => $candRoom,
                            ]);
                            $slotId = $candSlot;
                            $roomId = $candRoom;
                            $roomKey = $rKey;
                            $teacherKey = $tKey;
                            $resolved = true;
                            break 2;
                        }
                    }
                }
                // Nếu cùng ngày kín hết ca cho GV -> đẩy sang ngày kế tiếp
                if (! $resolved) {
                    $nextDate = $s->session_date->copy()->addDay();
                    for ($d = 0; $d < 14 && ! $resolved; $d++) {
                        $dStr = $nextDate->toDateString();
                        foreach ($allSlots as $candSlot) {
                            $tKey = "{$dStr}|{$candSlot}|{$tid}";
                            if (isset($usedTeacherSlots[$tKey])) {
                                continue;
                            }
                            foreach ($allRooms as $candRoom) {
                                $rKey = "{$dStr}|{$candSlot}|{$candRoom}";
                                if (! isset($usedRoomSlots[$rKey])) {
                                    $s->update([
                                        'session_date' => $dStr,
                                        'slot_id' => $candSlot,
                                        'room_id' => $candRoom,
                                    ]);
                                    $roomKey = $rKey;
                                    $teacherKey = $tKey;
                                    $resolved = true;
                                    break 3;
                                }
                            }
                        }
                        $nextDate->addDay();
                    }
                }
            }

            $usedRoomSlots[$roomKey] = true;
            $usedTeacherSlots[$teacherKey] = true;
        }

        // Đồng bộ lại total_sessions và end_date cho toàn bộ các lớp
        foreach (ClassRoom::all() as $class) {
            $cnt = $class->sessions()->count();
            $maxDate = $class->sessions()->max('session_date');
            $class->update([
                'total_sessions' => $cnt,
                'end_date' => $maxDate ?: $class->end_date,
            ]);
        }
    }
}
