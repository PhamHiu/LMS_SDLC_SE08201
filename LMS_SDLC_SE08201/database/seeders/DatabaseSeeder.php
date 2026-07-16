<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Discussion;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\Material;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\SubmissionDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Dữ liệu demo đầy đủ cho 3 role.
     * Tài khoản mặc định (mật khẩu chung: password):
     *  - admin@lms.vn / teacher1..3@lms.vn / student1..12@lms.vn
     */
    public function run(): void
    {
        $pw = Hash::make('password');

        // ===== 1. Chuyên ngành =====
        $majors = collect([
            ['major_name' => 'Công nghệ thông tin', 'major_code' => 'IT', 'description' => 'Đào tạo kỹ sư CNTT tổng quát.'],
            ['major_name' => 'Kỹ thuật phần mềm', 'major_code' => 'SE', 'description' => 'Chuyên sâu quy trình phát triển phần mềm.'],
            ['major_name' => 'Thương mại điện tử', 'major_code' => 'EC', 'description' => 'Kinh doanh trên nền tảng số.'],
        ])->map(fn ($m) => Major::create($m));

        // ===== 2. Môn học =====
        $subjects = collect([
            ['subject_code' => 'WEB301', 'subject_name' => 'Lập trình Web với Laravel', 'description' => 'Xây dựng ứng dụng web MVC với PHP/Laravel.'],
            ['subject_code' => 'DB201', 'subject_name' => 'Cơ sở dữ liệu MySQL', 'description' => 'Thiết kế và tối ưu CSDL quan hệ.'],
            ['subject_code' => 'DSA101', 'subject_name' => 'Cấu trúc dữ liệu & Giải thuật', 'description' => 'Nền tảng thuật toán cho lập trình viên.'],
            ['subject_code' => 'OOP102', 'subject_name' => 'Lập trình hướng đối tượng', 'description' => 'Tư duy OOP với PHP/Java.'],
            ['subject_code' => 'UIX201', 'subject_name' => 'Thiết kế giao diện UI/UX', 'description' => 'Nguyên tắc thiết kế trải nghiệm người dùng.'],
            ['subject_code' => 'MKT101', 'subject_name' => 'Digital Marketing', 'description' => 'Tiếp thị số căn bản.'],
        ])->map(fn ($s) => Subject::create($s));

        // Gán môn ↔ ngành (môn nền tảng thuộc nhiều ngành)
        $majors[0]->subjects()->attach([$subjects[0]->subject_id, $subjects[1]->subject_id, $subjects[2]->subject_id, $subjects[3]->subject_id]);
        $majors[1]->subjects()->attach([$subjects[0]->subject_id, $subjects[1]->subject_id, $subjects[2]->subject_id, $subjects[3]->subject_id, $subjects[4]->subject_id]);
        $majors[2]->subjects()->attach([$subjects[0]->subject_id, $subjects[4]->subject_id, $subjects[5]->subject_id]);

        // ===== 3. Người dùng =====
        $admin = User::create([
            'role' => 'ADMIN', 'email' => 'admin@lms.vn', 'hashpassword' => $pw,
            'full_name' => 'Quản Trị Hệ Thống', 'gender' => 1, 'phone' => '0900000001',
        ]);

        $teacherNames = ['Nguyễn Văn Đăng', 'Trần Thị Hiếu', 'Lê Minh Ngọc'];
        $teachers = collect($teacherNames)->map(fn ($name, $i) => User::create([
            'role' => 'TEACHER', 'email' => 'teacher'.($i + 1).'@lms.vn', 'hashpassword' => $pw,
            'full_name' => $name, 'major_id' => $majors[$i % 3]->major_id,
            'gender' => $i % 2, 'phone' => '09111111'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
        ]));

        $studentNames = [
            'Phạm Quốc Thắng', 'Đỗ Thùy Linh', 'Vũ Đức Anh', 'Hoàng Mai Phương',
            'Bùi Tiến Dũng', 'Ngô Thanh Hà', 'Đặng Hữu Phúc', 'Lý Khánh Vy',
            'Trịnh Công Sơn', 'Mai Anh Tuấn', 'Chu Ngọc Bích', 'Tạ Quang Huy',
        ];
        $students = collect($studentNames)->map(fn ($name, $i) => User::create([
            'role' => 'STUDENT', 'email' => 'student'.($i + 1).'@lms.vn', 'hashpassword' => $pw,
            'full_name' => $name, 'major_id' => $majors[$i % 3]->major_id,
            'date_of_birth' => Carbon::create(2004, ($i % 12) + 1, ($i % 27) + 1),
            'gender' => $i % 2, 'phone' => '09222222'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
        ]));

        // ===== 4. Lớp học + buổi học =====
        $classDefs = [
            ['class_name' => 'WD19301', 'subject' => 0, 'teacher' => 0, 'major' => 0, 'room' => 'P301', 'shift' => 'Ca 1 (07:15 - 09:15)'],
            ['class_name' => 'DB19302', 'subject' => 1, 'teacher' => 1, 'major' => 0, 'room' => 'P205', 'shift' => 'Ca 2 (09:25 - 11:25)'],
            ['class_name' => 'DS19303', 'subject' => 2, 'teacher' => 0, 'major' => 1, 'room' => 'P108', 'shift' => 'Ca 3 (12:30 - 14:30)'],
            ['class_name' => 'UX19304', 'subject' => 4, 'teacher' => 2, 'major' => 2, 'room' => 'Lab A2', 'shift' => 'Ca 4 (14:40 - 16:40)'],
        ];

        $classes = collect();
        foreach ($classDefs as $i => $def) {
            $start = Carbon::now()->subWeeks(6)->startOfWeek()->addDays($i % 5);
            $class = ClassRoom::create([
                'user_id' => $teachers[$def['teacher']]->user_id,
                'subject_id' => $subjects[$def['subject']]->subject_id,
                'major_id' => $majors[$def['major']]->major_id,
                'class_name' => $def['class_name'],
                'start_date' => $start,
                'end_date' => $start->copy()->addWeeks(11),
                'total_sessions' => 12,
                'status' => ClassRoom::STATUS_ACTIVE,
            ]);
            // 12 buổi học, mỗi tuần 1 buổi
            for ($s = 0; $s < 12; $s++) {
                ClassSession::create([
                    'class_id' => $class->class_id,
                    'session_date' => $start->copy()->addWeeks($s),
                    'room' => $def['room'],
                    'shift' => $def['shift'],
                ]);
            }
            $classes->push($class);
        }

        // ===== 5. Ghi danh: mỗi lớp 6-8 sinh viên =====
        foreach ($classes as $i => $class) {
            $group = $students->slice(($i * 3) % 6, 8)->values();
            foreach ($group as $sv) {
                Enrollment::create([
                    'user_id' => $sv->user_id,
                    'class_id' => $class->class_id,
                    'enrolled_at' => $class->start_date->copy()->subDays(3),
                ]);
            }
        }

        // ===== 6. Điểm danh các buổi đã diễn ra =====
        foreach ($classes as $class) {
            $pastSessions = $class->sessions()->where('session_date', '<', Carbon::today())->get();
            $studentIds = $class->students()->pluck('users.user_id');
            foreach ($pastSessions as $si => $session) {
                foreach ($studentIds as $idx => $uid) {
                    $status = (($idx + $si) % 7 === 3) ? 'A' : ((($idx + $si) % 9 === 5) ? 'L' : 'P');
                    Attendance::create([
                        'user_id' => $uid,
                        'session_id' => $session->session_id,
                        'attendance_time' => $session->session_date->copy()->setTime(7, 20),
                        'status' => $status,
                        'note' => $status === 'A' ? 'Vắng không phép' : null,
                    ]);
                }
            }
        }

        // ===== 7. Học liệu =====
        foreach ($subjects as $subject) {
            $teacher = $teachers[$subject->subject_id % 3];
            Material::create([
                'subject_id' => $subject->subject_id, 'user_id' => $teacher->user_id,
                'title' => 'Giáo trình '.$subject->subject_name,
                'file_path' => 'materials/demo-giao-trinh.pdf',
                'description' => 'Giáo trình chính thức của môn '.$subject->subject_name.'.',
            ]);
            Material::create([
                'subject_id' => $subject->subject_id, 'user_id' => $teacher->user_id,
                'title' => 'Slide bài giảng tuần 1 - '.$subject->subject_code,
                'file_path' => 'materials/demo-slide-tuan1.pdf',
                'description' => 'Slide giới thiệu tổng quan môn học.',
            ]);
        }

        // ===== 8. Bài tập: mỗi lớp 1 quiz + 1 tự luận =====
        $quizBank = [
            ['MVC là viết tắt của gì?', 'Model - View - Controller', 'Main - View - Cache', 'Model - Vue - CSS', 'Module - View - Class', 'A'],
            ['Lệnh nào tạo migration trong Laravel?', 'php artisan make:migration', 'php artisan new:table', 'composer make:migration', 'php artisan db:create', 'A'],
            ['Eloquent ORM giúp phòng chống lỗ hổng nào?', 'XSS', 'SQL Injection', 'CSRF', 'DDoS', 'B'],
            ['Blade directive nào dùng để lặp?', '@if', '@csrf', '@foreach', '@yield', 'C'],
            ['Khóa ngoại (FK) dùng để làm gì?', 'Tăng tốc truy vấn', 'Mã hóa dữ liệu', 'Đánh index toàn bảng', 'Liên kết dữ liệu giữa hai bảng', 'D'],
        ];

        foreach ($classes as $ci => $class) {
            // Quiz đã hết hạn (đã có bài làm, tự chấm)
            $quiz = Assignment::create([
                'class_id' => $class->class_id,
                'title' => 'Quiz 1: Kiến thức nền tảng '.$class->subject->subject_code,
                'type' => 'quiz',
                'description' => 'Bài trắc nghiệm 5 câu, làm 1 lần duy nhất. Hệ thống tự chấm điểm.',
                'due_date' => Carbon::now()->addDays(5)->setTime(23, 59),
                'max_score' => 10,
            ]);
            $questions = collect($quizBank)->map(fn ($q) => Question::create([
                'assignment_id' => $quiz->assignment_id,
                'content' => $q[0], 'option_a' => $q[1], 'option_b' => $q[2],
                'option_c' => $q[3], 'option_d' => $q[4], 'correct_answer' => $q[5],
            ]));

            // Tự luận sắp đến hạn
            $essay = Assignment::create([
                'class_id' => $class->class_id,
                'title' => 'Assignment 1: Báo cáo giai đoạn 1',
                'type' => 'essay',
                'description' => "Nộp file PDF/Word báo cáo giai đoạn 1 của môn học.\nYêu cầu: trình bày rõ ràng, tối đa 10 trang.",
                'due_date' => Carbon::now()->addDays(3 + $ci)->setTime(23, 59),
                'max_score' => 10,
            ]);

            $classStudents = $class->students()->get();

            // Một nửa sinh viên đã làm quiz (hệ thống tự chấm)
            foreach ($classStudents->take(4) as $k => $sv) {
                $submission = Submission::create([
                    'user_id' => $sv->user_id,
                    'assignment_id' => $quiz->assignment_id,
                    'submitted_at' => Carbon::now()->subDays(2)->addMinutes($k * 17),
                    'graded_at' => Carbon::now()->subDays(2)->addMinutes($k * 17),
                ]);
                $correct = 0;
                foreach ($questions as $qi => $question) {
                    $selected = ($qi + $k) % 5 === 4 ? 'A' : $question->correct_answer; // sai ngẫu nhiên 1 vài câu
                    if ($selected === $question->correct_answer) {
                        $correct++;
                    }
                    SubmissionDetail::create([
                        'submission_id' => $submission->submission_id,
                        'question_id' => $question->question_id,
                        'selected_option' => $selected,
                    ]);
                }
                $submission->update(['score' => round($correct / count($quizBank) * $quiz->max_score, 2)]);
            }

            // Vài sinh viên đã nộp tự luận, giảng viên chấm 2 bài
            foreach ($classStudents->take(3) as $k => $sv) {
                Submission::create([
                    'user_id' => $sv->user_id,
                    'assignment_id' => $essay->assignment_id,
                    'submit_file_path' => 'submissions/demo-baitap.pdf',
                    'submitted_at' => Carbon::now()->subHours(20 + $k * 5),
                    'graded_at' => $k < 2 ? Carbon::now()->subHours(3) : null,
                    'score' => $k < 2 ? 8.5 - $k : null,
                    'feedback' => $k < 2 ? 'Bài làm tốt, trình bày rõ ràng. Cần bổ sung phần kết luận.' : null,
                ]);
            }

            // Thảo luận dưới bài tự luận
            $q1 = Discussion::create([
                'user_id' => $classStudents->first()->user_id,
                'assignment_id' => $essay->assignment_id,
                'comment' => 'Thầy/cô ơi, bài này nộp file Word có được không ạ?',
            ]);
            Discussion::create([
                'user_id' => $class->user_id,
                'parent_id' => $q1->discussion_id,
                'assignment_id' => $essay->assignment_id,
                'comment' => 'Được em nhé, PDF hoặc Word đều hợp lệ.',
            ]);

            // Thông báo nội bộ lớp
            Notification::create([
                'user_id' => $class->user_id,
                'class_id' => $class->class_id,
                'title' => 'Lớp '.$class->class_name.': Nhắc hạn nộp Assignment 1',
                'body' => 'Các em chú ý hạn nộp Assignment 1 vào '.$essay->due_date->format('d/m/Y H:i').'. Nộp muộn sẽ bị trừ điểm.',
            ]);
        }

        // ===== 9. Thông báo toàn hệ thống =====
        Notification::create([
            'user_id' => $admin->user_id, 'class_id' => null,
            'title' => 'Chào mừng đến với hệ thống LMS',
            'body' => 'Hệ thống quản lý học tập chính thức đi vào hoạt động. Sinh viên vui lòng cập nhật hồ sơ cá nhân sau lần đăng nhập đầu tiên.',
        ]);
        Notification::create([
            'user_id' => $admin->user_id, 'class_id' => null,
            'title' => 'Lịch bảo trì hệ thống',
            'body' => 'Hệ thống bảo trì định kỳ vào 23:00 Chủ nhật hàng tuần. Thời gian gián đoạn dự kiến 30 phút.',
        ]);
    }
}
