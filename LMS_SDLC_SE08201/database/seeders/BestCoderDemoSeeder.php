<?php

namespace Database\Seeders;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\RiskIntervention;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use App\Services\AI\DropoutRiskService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SEEDER DỮ LIỆU DEMO QUY MÔ LỚN BESTCODER 2026
 * Đầy đủ giảng viên, sinh viên tên người thật, 4 môn học, 4 lớp học,
 * điểm danh 12-15 buổi, bài tập, điểm số, phân tích nguy cơ AI và lịch sử can thiệp.
 */
class BestCoderDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CÁC CHUYÊN NGÀNH (MAJORS)
        $majorSE = Major::firstOrCreate(
            ['major_code' => 'SE'],
            ['major_name' => 'Kỹ thuật phần mềm', 'description' => 'Software Engineering']
        );

        $majorWD = Major::firstOrCreate(
            ['major_code' => 'WD'],
            ['major_name' => 'Phát triển Ứng dụng Web', 'description' => 'Web Development']
        );

        // 2. TÀI KHOẢN QUẢN TRỊ VIÊN (ADMIN)
        User::firstOrCreate(
            ['email' => 'admin@lms.vn'],
            [
                'full_name' => 'Ban Đào Tạo BTEC FPT',
                'hashpassword' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'major_id' => $majorSE->major_id,
                'student_code' => 'ADM001',
            ]
        );

        // 3. GIẢNG VIÊN THẬT (TEACHERS)
        $teacherDong = User::firstOrCreate(
            ['email' => 'dongdv@lms.vn'],
            [
                'full_name' => 'ThS. Đỗ Văn Đông',
                'hashpassword' => Hash::make('password'),
                'role' => User::ROLE_TEACHER,
                'major_id' => $majorSE->major_id,
                'student_code' => 'GV001',
                'phone' => '0912345678',
            ]
        );

        $teacherThao = User::firstOrCreate(
            ['email' => 'thaonp@lms.vn'],
            [
                'full_name' => 'ThS. Nguyễn Phương Thảo',
                'hashpassword' => Hash::make('password'),
                'role' => User::ROLE_TEACHER,
                'major_id' => $majorWD->major_id,
                'student_code' => 'GV002',
                'phone' => '0923456789',
            ]
        );

        $teacherNam = User::firstOrCreate(
            ['email' => 'namth@lms.vn'],
            [
                'full_name' => 'ThS. Trần Hải Nam',
                'hashpassword' => Hash::make('password'),
                'role' => User::ROLE_TEACHER,
                'major_id' => $majorSE->major_id,
                'student_code' => 'GV003',
                'phone' => '0934567890',
            ]
        );

        // Tài khoản teacher mặc định để test cũ hoạt động
        $teacherDefault = User::firstOrCreate(
            ['email' => 'teacher@lms.vn'],
            [
                'full_name' => 'Giảng viên Bộ môn CNTT',
                'hashpassword' => Hash::make('password'),
                'role' => User::ROLE_TEACHER,
                'major_id' => $majorSE->major_id,
                'student_code' => 'GV000',
            ]
        );

        // 4. MÔN HỌC (SUBJECTS)
        $subjJava = Subject::firstOrCreate(
            ['subject_code' => 'PRO192'],
            ['subject_name' => 'Lập trình Hướng đối tượng Java', 'description' => 'Object Oriented Programming with Java']
        );

        $subjDB = Subject::firstOrCreate(
            ['subject_code' => 'DBI202'],
            ['subject_name' => 'Cơ sở dữ liệu & Hệ Quản trị MySQL', 'description' => 'Relational Database Management Systems']
        );

        $subjWeb = Subject::firstOrCreate(
            ['subject_code' => 'WEB2013'],
            ['subject_name' => 'Lập trình Web Backend với Laravel', 'description' => 'Enterprise Web Development with PHP & Laravel']
        );

        $subjSys = Subject::firstOrCreate(
            ['subject_code' => 'SWE2011'],
            ['subject_name' => 'Phân tích và Thiết kế Hệ thống (SDLC)', 'description' => 'System Analysis and Software Architecture']
        );

        // 5. CÁC LỚP HỌC (CLASSROOMS)
        $classJava = ClassRoom::firstOrCreate(
            ['class_name' => 'SE1801-Java'],
            [
                'user_id' => $teacherDong->user_id,
                'subject_id' => $subjJava->subject_id,
                'major_id' => $majorSE->major_id,
                'total_sessions' => 12,
                'status' => 1,
            ]
        );

        $classDB = ClassRoom::firstOrCreate(
            ['class_name' => 'SE1802-Database'],
            [
                'user_id' => $teacherNam->user_id,
                'subject_id' => $subjDB->subject_id,
                'major_id' => $majorSE->major_id,
                'total_sessions' => 12,
                'status' => 1,
            ]
        );

        $classWeb = ClassRoom::firstOrCreate(
            ['class_name' => 'SE1803-WebLaravel'],
            [
                'user_id' => $teacherThao->user_id,
                'subject_id' => $subjWeb->subject_id,
                'major_id' => $majorWD->major_id,
                'total_sessions' => 12,
                'status' => 1,
            ]
        );

        // 6. TẠO BUỔI HỌC (SESSIONS) CHO TỪNG LỚP
        $classes = [$classJava, $classDB, $classWeb];
        foreach ($classes as $cls) {
            if ($cls->sessions()->count() < 12) {
                for ($i = 1; $i <= 12; $i++) {
                    ClassSession::create([
                        'class_id' => $cls->class_id,
                        'session_date' => now()->subDays(13 - $i)->toDateString(),
                        'room_id' => ($cls->class_id % 5) + 1,
                        'slot_id' => (($cls->class_id + $i) % 4) + 1,
                    ]);
                }
            }
        }

        // 7. TẠO BÀI TẬP (ASSIGNMENTS) CHO LỚP JAVA
        $assignmentJavaLab = Assignment::firstOrCreate(
            ['class_id' => $classJava->class_id, 'title' => 'Lab 1: Kế thừa và Đa hình trong Java'],
            [
                'type' => Assignment::TYPE_ESSAY,
                'description' => 'Xây dựng hệ thống quản lý nhân sự áp dụng tính kế thừa và đa hình (Inheritance & Polymorphism).',
                'due_date' => now()->addDays(7),
                'max_score' => 10.0,
                'created_at' => now()->subDays(5),
            ]
        );

        $assignmentJavaQuiz = Assignment::firstOrCreate(
            ['class_id' => $classJava->class_id, 'title' => 'Quiz 1: Trắc nghiệm Kiến thức Java OOP'],
            [
                'type' => Assignment::TYPE_QUIZ,
                'description' => '10 câu trắc nghiệm kiểm tra kiến thức về Interface, Abstract Class và Exception Handling.',
                'due_date' => now()->addDays(3),
                'max_score' => 10.0,
                'show_answers' => true,
                'created_at' => now()->subDays(3),
            ]
        );

        // 8. DANH SÁCH SINH VIÊN THẬT (Bao gồm 4 bạn nhóm dự thi & 32 sinh viên thật với 4 nhóm hồ sơ học tập)
        $studentDataset = [
            // --- 4 THÀNH VIÊN ĐỘI THI BESTCODER 2026 TẠI BTEC FPT ---
            [
                'email' => 'dangbh02199@lms.vn',
                'full_name' => 'Bùi Hải Đăng',
                'student_code' => 'BH02199',
                'absences' => 0,
                'score' => 9.5,
                'group' => 'SAFE',
            ],
            [
                'email' => 'ngocntb02391@lms.vn',
                'full_name' => 'Nguyễn Thị Bích Ngọc',
                'student_code' => 'BH02391',
                'absences' => 0,
                'score' => 9.2,
                'group' => 'SAFE',
            ],
            [
                'email' => 'hieupq02348@lms.vn',
                'full_name' => 'Phạm Quang Hiếu',
                'student_code' => 'BH02348',
                'absences' => 0,
                'score' => 9.0,
                'group' => 'SAFE',
            ],
            [
                'email' => 'thangnq02178@lms.vn',
                'full_name' => 'Nông Quốc Thắng',
                'student_code' => 'BH02178',
                'absences' => 0,
                'score' => 8.8,
                'group' => 'SAFE',
            ],

            // --- 4 TÀI KHOẢN MẪU BẢO ĐẢM 100% CÁC TEST CASES CỐT LÕI ---
            [
                'email' => 'student_safe@lms.vn',
                'full_name' => 'Trần Văn An Toàn',
                'student_code' => 'BH02100',
                'absences' => 0,
                'score' => 9.0,
                'group' => 'SAFE',
            ],
            [
                'email' => 'student_warning@lms.vn',
                'full_name' => 'Lê Thị Cảnh Báo',
                'student_code' => 'BH02101',
                'absences' => 2, // Vắng 2/12 = 16.7% (dưới 20%, tiệm cận) -> MEDIUM
                'score' => 6.5,
                'group' => 'MEDIUM',
            ],
            [
                'email' => 'student_att_fail@lms.vn',
                'full_name' => 'Phạm Văn Vắng Quá Hạn',
                'student_code' => 'BH02102',
                'absences' => 3, // Vắng 3/12 = 25.0% (> 20% -> TRƯỢT CHUYÊN CẦN) -> HIGH
                'score' => 5.5,
                'group' => 'HIGH',
            ],
            [
                'email' => 'student_dropout@lms.vn',
                'full_name' => 'Hoàng Minh Nguy Cơ Cao',
                'student_code' => 'BH02103',
                'absences' => 5, // Vắng 5/12 = 41.7% (> 20%) + điểm 2.0 -> VERY_HIGH
                'score' => 2.0,
                'group' => 'VERY_HIGH',
            ],

            // --- NHÓM 1: SINH VIÊN XUẤT SẮC & CHUYÊN CẦN 100% (SAFE) ---
            ['email' => 'namtv.bh02104@lms.vn', 'full_name' => 'Trần Văn Nam', 'student_code' => 'BH02104', 'absences' => 0, 'score' => 8.8, 'group' => 'SAFE'],
            ['email' => 'mailt.bh02105@lms.vn', 'full_name' => 'Lê Thị Mai', 'student_code' => 'BH02105', 'absences' => 0, 'score' => 9.4, 'group' => 'SAFE'],
            ['email' => 'kietvt.bh02106@lms.vn', 'full_name' => 'Vũ Tuấn Kiệt', 'student_code' => 'BH02106', 'absences' => 0, 'score' => 8.2, 'group' => 'SAFE'],
            ['email' => 'hadt.bh02107@lms.vn', 'full_name' => 'Đặng Thu Hà', 'student_code' => 'BH02107', 'absences' => 0, 'score' => 8.6, 'group' => 'SAFE'],
            ['email' => 'ducpm.bh02108@lms.vn', 'full_name' => 'Phan Minh Đức', 'student_code' => 'BH02108', 'absences' => 1, 'score' => 7.8, 'group' => 'SAFE'],
            ['email' => 'baohg.bh02109@lms.vn', 'full_name' => 'Hoàng Gia Bảo', 'student_code' => 'BH02109', 'absences' => 0, 'score' => 8.5, 'group' => 'SAFE'],
            ['email' => 'linhtp.bh02110@lms.vn', 'full_name' => 'Trịnh Phương Linh', 'student_code' => 'BH02110', 'absences' => 1, 'score' => 8.0, 'group' => 'SAFE'],
            ['email' => 'huyendk.bh02111@lms.vn', 'full_name' => 'Đỗ Khánh Huyền', 'student_code' => 'BH02111', 'absences' => 0, 'score' => 9.1, 'group' => 'SAFE'],
            ['email' => 'trongdd.bh02112@lms.vn', 'full_name' => 'Dương Đình Trọng', 'student_code' => 'BH02112', 'absences' => 0, 'score' => 7.9, 'group' => 'SAFE'],
            ['email' => 'vannt.bh02113@lms.vn', 'full_name' => 'Ngô Thanh Vân', 'student_code' => 'BH02113', 'absences' => 1, 'score' => 8.3, 'group' => 'SAFE'],
            ['email' => 'anhbq.bh02114@lms.vn', 'full_name' => 'Bùi Quốc Anh', 'student_code' => 'BH02114', 'absences' => 0, 'score' => 8.7, 'group' => 'SAFE'],
            ['email' => 'nhiny.bh02115@lms.vn', 'full_name' => 'Nguyễn Yến Nhi', 'student_code' => 'BH02115', 'absences' => 0, 'score' => 8.9, 'group' => 'SAFE'],

            // --- NHÓM 2: CẢNH BÁO SỚM TIỆM CẬN 20% (MEDIUM - Vắng 2/12 = 16.7%) ---
            ['email' => 'uyenchu.bh02116@lms.vn', 'full_name' => 'Chu Mỹ Uyên', 'student_code' => 'BH02116', 'absences' => 2, 'score' => 6.8, 'group' => 'MEDIUM'],
            ['email' => 'buutq.bh02117@lms.vn', 'full_name' => 'Tạ Quang Bửu', 'student_code' => 'BH02117', 'absences' => 2, 'score' => 7.0, 'group' => 'MEDIUM'],
            ['email' => 'thangnd.bh02118@lms.vn', 'full_name' => 'Nguyễn Đức Thắng', 'student_code' => 'BH02118', 'absences' => 2, 'score' => 6.2, 'group' => 'MEDIUM'],
            ['email' => 'chilk.bh02119@lms.vn', 'full_name' => 'Lâm Khánh Chi', 'student_code' => 'BH02119', 'absences' => 2, 'score' => 6.5, 'group' => 'MEDIUM'],
            ['email' => 'datvt.bh02120@lms.vn', 'full_name' => 'Võ Thành Đạt', 'student_code' => 'BH02120', 'absences' => 2, 'score' => 5.8, 'group' => 'MEDIUM'],
            ['email' => 'tuanma.bh02121@lms.vn', 'full_name' => 'Mai Anh Tuấn', 'student_code' => 'BH02121', 'absences' => 2, 'score' => 6.0, 'group' => 'MEDIUM'],
            ['email' => 'hungdt.bh02122@lms.vn', 'full_name' => 'Đinh Trọng Hưng', 'student_code' => 'BH02122', 'absences' => 2, 'score' => 6.4, 'group' => 'MEDIUM'],
            ['email' => 'vinhlt.bh02123@lms.vn', 'full_name' => 'Lương Thế Vinh', 'student_code' => 'BH02123', 'absences' => 2, 'score' => 5.9, 'group' => 'MEDIUM'],

            // --- NHÓM 3: TRƯỢT CHUYÊN CẦN DO VẮNG > 20% (HIGH - Vắng 3-4 buổi = 25% - 33.3%) ---
            ['email' => 'longnh.bh02124@lms.vn', 'full_name' => 'Nguyễn Hoàng Long', 'student_code' => 'BH02124', 'absences' => 3, 'score' => 6.0, 'group' => 'HIGH'],
            ['email' => 'toantq.bh02125@lms.vn', 'full_name' => 'Trần Quốc Toản', 'student_code' => 'BH02125', 'absences' => 3, 'score' => 5.5, 'group' => 'HIGH'],
            ['email' => 'hopd.bh02126@lms.vn', 'full_name' => 'Phạm Đình Hổ', 'student_code' => 'BH02126', 'absences' => 4, 'score' => 6.2, 'group' => 'HIGH'],
            ['email' => 'donlq.bh02127@lms.vn', 'full_name' => 'Lê Quý Đôn', 'student_code' => 'BH02127', 'absences' => 3, 'score' => 5.0, 'group' => 'HIGH'],
            ['email' => 'dunghq.bh02128@lms.vn', 'full_name' => 'Hà Quốc Dũng', 'student_code' => 'BH02128', 'absences' => 3, 'score' => 5.8, 'group' => 'HIGH'],
            ['email' => 'cuongvd.bh02129@lms.vn', 'full_name' => 'Vũ Đình Cường', 'student_code' => 'BH02129', 'absences' => 4, 'score' => 5.2, 'group' => 'HIGH'],

            // --- NHÓM 4: NGUY CƠ RẤT CAO (VERY_HIGH - Vắng 5-7 buổi = 41.7% - 58.3% kèm điểm kém) ---
            ['email' => 'tuanhm.bh02130@lms.vn', 'full_name' => 'Hoàng Minh Tuấn', 'student_code' => 'BH02130', 'absences' => 5, 'score' => 2.5, 'group' => 'VERY_HIGH'],
            ['email' => 'yenvh.bh02131@lms.vn', 'full_name' => 'Vũ Hoàng Yến', 'student_code' => 'BH02131', 'absences' => 6, 'score' => 3.0, 'group' => 'VERY_HIGH'],
            ['email' => 'quandm.bh02132@lms.vn', 'full_name' => 'Đỗ Minh Quân', 'student_code' => 'BH02132', 'absences' => 5, 'score' => 2.0, 'group' => 'VERY_HIGH'],
            ['email' => 'tung.bh02133@lms.vn', 'full_name' => 'Ngô Gia Tự', 'student_code' => 'BH02133', 'absences' => 7, 'score' => 1.5, 'group' => 'VERY_HIGH'],
            ['email' => 'tudd.bh02134@lms.vn', 'full_name' => 'Đào Duy Từ', 'student_code' => 'BH02134', 'absences' => 6, 'score' => 2.5, 'group' => 'VERY_HIGH'],
        ];

        $riskService = app(DropoutRiskService::class);
        $sessionsJava = $classJava->sessions;

        foreach ($studentDataset as $item) {
            $student = User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'full_name' => $item['full_name'],
                    'hashpassword' => Hash::make('password'),
                    'role' => User::ROLE_STUDENT,
                    'major_id' => $majorSE->major_id,
                    'student_code' => $item['student_code'],
                    'gender' => str_contains($item['full_name'], 'Thị') || str_contains($item['full_name'], 'Huyền') || str_contains($item['full_name'], 'Linh') || str_contains($item['full_name'], 'Nhi') || str_contains($item['full_name'], 'Uyên') || str_contains($item['full_name'], 'Chi') || str_contains($item['full_name'], 'Yến') ? 0 : 1,
                    'date_of_birth' => '2005-08-15',
                    'phone' => '098' . rand(1000000, 9999999),
                    'address' => 'Hà Nội, Việt Nam',
                ]
            );

            // Ghi danh vào lớp Java
            Enrollment::firstOrCreate([
                'user_id' => $student->user_id,
                'class_id' => $classJava->class_id,
            ]);

            // Điểm danh 12 buổi học môn Java
            for ($sIndex = 0; $sIndex < 12; $sIndex++) {
                $status = ($sIndex < $item['absences']) ? 'A' : 'P';
                Attendance::updateOrCreate(
                    [
                        'user_id' => $student->user_id,
                        'session_id' => $sessionsJava[$sIndex]->session_id,
                    ],
                    [
                        'status' => $status,
                        'attendance_time' => now()->subDays(12 - $sIndex),
                        'note' => $status === 'A' ? 'Vắng không phép' : 'Có mặt đúng giờ',
                    ]
                );
            }

            // Ghi nhận bài nộp và điểm số
            Submission::updateOrCreate(
                [
                    'user_id' => $student->user_id,
                    'assignment_id' => $assignmentJavaLab->assignment_id,
                ],
                [
                    'score' => $item['score'],
                    'feedback' => $item['score'] >= 8.0
                        ? 'Code rõ ràng, thiết kế hướng đối tượng tốt, bài làm xuất sắc.'
                        : ($item['score'] >= 5.0 ? 'Đã hoàn thành các yêu cầu cơ bản, cần tối ưu thêm xử lý ngoại lệ.' : 'Chưa đạt yêu cầu, thiếu các chức năng cốt lõi. Cần ôn tập lại với AI Tutor.'),
                    'submitted_at' => now()->subDays(2),
                    'graded_at' => now()->subDays(1),
                ]
            );

            // Đánh giá nguy cơ bỏ học qua AI Service
            $prediction = $riskService->analyzeAndRecord($student);

            // Với sinh viên nhóm HIGH và VERY_HIGH, tạo sẵn can thiệp sư phạm mẫu để demo
            if (in_array($item['group'], ['HIGH', 'VERY_HIGH'])) {
                RiskIntervention::firstOrCreate(
                    [
                        'user_id' => $student->user_id,
                        'prediction_id' => $prediction->prediction_id,
                    ],
                    [
                        'teacher_id' => $teacherDong->user_id,
                        'action_type' => $item['group'] === 'VERY_HIGH' ? 'MEETING_PARENT' : 'COUNSELING',
                        'notes' => $item['group'] === 'VERY_HIGH'
                            ? "Đã hẹn gặp sinh viên {$item['full_name']} và phụ huynh. Thông báo tình trạng vắng {$item['absences']}/12 buổi và điểm bài tập chưa đạt. Yêu cầu làm kế hoạch học phụ đạo với AI Tutor."
                            : "Thầy Đông đã gặp riêng sinh viên {$item['full_name']} nhắc nhở về việc vắng {$item['absences']}/12 buổi (đã vượt 20%). Hướng dẫn ôn tập qua LMS.",
                        'status' => 'IN_PROGRESS',
                        'outcome_evaluation' => 'Sinh viên đã tiếp thu và cam kết tham gia đầy đủ các buổi phụ đạo còn lại.',
                    ]
                );
            }
        }

        // 9. TẠO HỘI THOẠI MẪU VỚI AI LEARNING TUTOR CHO BÙI HẢI ĐĂNG
        $studentDang = User::where('email', 'dangbh02199@lms.vn')->first();
        if ($studentDang) {
            $conv = AiConversation::create([
                'user_id' => $studentDang->user_id,
                'subject_id' => $subjJava->subject_id,
                'title' => 'Giải thích tính Đa hình (Polymorphism) trong Java',
                'mode' => AiConversation::MODE_EXPLAIN,
                'model_used' => 'gemini-1.5-flash',
            ]);

            AiMessage::create([
                'conversation_id' => $conv->conversation_id,
                'role' => 'user',
                'content' => 'Thưa AI Tutor, giải thích cho em tính Đa hình (Polymorphism) trong Java và cho ví dụ thực tế dễ hiểu nhất ạ?',
            ]);

            AiMessage::create([
                'conversation_id' => $conv->conversation_id,
                'role' => 'assistant',
                'content' => "Chào Hải Đăng! Thầy rất vui được giải thích cho em về **Tính Đa hình (Polymorphism)** – một trong bốn trụ cột quan trọng nhất của Lập trình Hướng đối tượng (OOP).\n\n### 1. Bản chất của Đa hình\nĐa hình có nghĩa là *\"một tên gọi, nhiều hình thái biểu hiện khác nhau\"*. Trong Java, nó cho phép một hành vi (phương thức) hoạt động khác nhau tùy thuộc vào đối tượng cụ thể đang thực thi nó.\n\n### 2. Ví dụ thực tế: Cổng thanh toán (Payment Gateway)\nHãy tưởng tượng em đang viết module thanh toán cho website:\n\n```java\n// Lớp cha hoặc Interface\ninterface PhuongThucThanhToan {\n    void thanhToan(double soTien);\n}\n\n// Các lớp con hiện thực đa hình\nclass ThanhToanMoMo implements PhuongThucThanhToan {\n    public void thanhToan(double soTien) {\n        System.out.println(\"Quét mã QR MoMo trừ ví: \" + soTien + \" VNĐ\");\n    }\n}\n\nclass ThanhToanTheTinDung implements PhuongThucThanhToan {\n    public void thanhToan(double soTien) {\n        System.out.println(\"Gửi OTP xác thực thanh toán thẻ Visa: \" + soTien + \" VNĐ\");\n    }\n}\n```\n\n### 3. Ưu điểm vượt trội:\nKhi có phương thức mới (ví dụ thanh toán Apple Pay), em chỉ cần tạo thêm class mới mà **không cần sửa đổi code xử lý giỏ hàng cũ** (tuân thủ nguyên lý Open/Closed trong SOLID).\n\nEm đã nắm rõ phần này chưa, hay muốn thử làm một bài tập nhỏ về Overriding để kiểm tra kiến thức nào?",
            ]);
        }
    }
}
