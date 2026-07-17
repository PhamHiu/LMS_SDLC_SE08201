# KẾ HOẠCH CODE CHI TIẾT — HỆ THỐNG LMS

Tech stack: **Laravel 11 · MySQL (XAMPP) · Tailwind CSS (CDN) · Blade**
Mô hình: **MVC mở rộng** — luồng xử lý bắt buộc cho mọi request:

```
Browser → Route → Middleware → Form Request → Controller → Service → Model → Database
Database → Model → Service → Controller → Blade View → Browser
```

---

## 1. Cấu trúc thư mục (phân theo 3 nhóm quyền)

Toàn bộ code nghiệp vụ chia theo 3 nhóm chính trong tài liệu phân quyền: **Admin / Teacher / Student** (+ Auth dùng chung).

```
lms/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/          # Login, Register, Logout, Profile (dùng chung)
│   │   │   ├── Admin/         # Dashboard, Users, Majors, Subjects, Classes, Notifications
│   │   │   ├── Teacher/       # Dashboard, Classes, Materials, Attendance, Assignments, Grading, Notifications
│   │   │   └── Student/       # Dashboard, Schedule, Materials, Assignments (Quiz/Essay), Grades, Discussions
│   │   ├── Middleware/
│   │   │   └── CheckRole.php  # Phân quyền ADMIN / TEACHER / STUDENT
│   │   └── Requests/          # Form Request validation, chia theo Auth/Admin/Teacher/Student
│   ├── Models/                # 14 Eloquent Model ↔ 15 bảng CSDL
│   ├── Services/              # Business logic, chia theo Admin/Teacher/Student
│   └── Providers/
├── database/
│   ├── migrations/            # 5 file migration theo 5 phân hệ CSDL
│   └── seeders/               # Dữ liệu demo (tài khoản mẫu, lớp, bài tập, quiz…)
├── resources/views/
│   ├── layouts/               # Layout chung (sidebar theo role)
│   ├── auth/                  # Login, Register
│   ├── profile/               # Hồ sơ cá nhân (dùng chung 3 role)
│   ├── admin/  teacher/  student/
│   └── shared/                # Thông báo, thảo luận (dùng chung)
├── routes/web.php             # Route nhóm theo prefix + middleware role
└── public/                    # Front controller
```

## 2. Ánh xạ Model ↔ 15 bảng CSDL

| Model | Bảng | Ghi chú |
|---|---|---|
| User | users | PK `user_id`, cột mật khẩu `hashpassword` |
| Major | majors | Chuyên ngành |
| Subject | subjects | Môn học |
| MajorSubject | majors_subjects | Pivot N-N |
| ClassRoom | classes | Tránh trùng từ khóa `class` của PHP |
| Enrollment | enrollments | Pivot SV ↔ Lớp |
| ClassSession | sessions | Buổi học (SESSION_DRIVER=file nên không đụng bảng session của Laravel) |
| Attendance | attendances | Điểm danh P/A/L |
| Material | materials | Học liệu |
| Assignment | assignments | Bài tập: `essay` / `quiz` |
| Question | questions | Ngân hàng câu hỏi trắc nghiệm |
| Submission | submissions | Lượt nộp + điểm |
| SubmissionDetail | submission_details | Đáp án từng câu quiz |
| Discussion | discussions | Bình luận phân cấp (parent_id) |
| Notification | notifications | class_id NULL = toàn hệ thống |

## 3. Kế hoạch code theo module (thứ tự thực hiện)

### Module 0 — Nền tảng
- Skeleton Laravel 11, cấu hình .env cho XAMPP (MySQL, session file).
- 5 migrations theo 5 phân hệ của tài liệu CSDL, 14 models kèm quan hệ Eloquent, seeder dữ liệu demo.

### Module 1 — Auth & Phân quyền (Sprint 1)
- Route: `/login`, `/register`, `/logout`, `/profile`.
- Middleware `CheckRole` → alias `role`, redirect theo role sau đăng nhập.
- Form Request: `LoginRequest`, `RegisterRequest`, `UpdateProfileRequest`.
- Service: `AuthService` (attempt, register mặc định STUDENT), `ProfileService`.

### Module 2 — Admin (Sprint 2)
| Chức năng | Controller | Service | Form Request |
|---|---|---|---|
| Dashboard thống kê | Admin\DashboardController | Admin\DashboardService | — |
| CRUD người dùng + role + reset MK | Admin\UserController | Admin\UserService | Admin\UserRequest |
| CRUD chuyên ngành | Admin\MajorController | Admin\MajorService | Admin\MajorRequest |
| CRUD môn học (gán ngành) | Admin\SubjectController | Admin\SubjectService | Admin\SubjectRequest |
| Tạo lớp, gán GV, xếp SV, sinh buổi học | Admin\ClassController | Admin\ClassService | Admin\ClassRequest |
| Thông báo toàn trường | Admin\NotificationController | Shared NotificationService | NotificationRequest |

### Module 3 — Teacher (Sprint 3 + 4)
| Chức năng | Controller | Service |
|---|---|---|
| Dashboard lịch dạy + tiến độ | Teacher\DashboardController | Teacher\TeacherDashboardService |
| Lớp phụ trách + DS sinh viên | Teacher\ClassController | Teacher\TeacherClassService |
| Upload học liệu (PDF/video) | Teacher\MaterialController | Teacher\MaterialService |
| Điểm danh theo buổi (P/A/L) | Teacher\AttendanceController | Teacher\AttendanceService |
| Tạo bài tập Tự luận / Quiz + câu hỏi | Teacher\AssignmentController | Teacher\AssignmentService |
| Chấm bài + nhận xét (quiz tự chấm) | Teacher\GradingController | Teacher\GradingService |
| Thông báo lớp + trả lời thảo luận | Teacher\NotificationController, Shared DiscussionController | NotificationService, DiscussionService |

### Module 4 — Student (Sprint 3 + 4)
| Chức năng | Controller | Service |
|---|---|---|
| Dashboard: bài sắp hạn + kết quả | Student\DashboardController | Student\StudentDashboardService |
| Thời khóa biểu + lịch sử điểm danh | Student\ScheduleController | Student\ScheduleService |
| Xem / tải học liệu | Student\MaterialController | Student\StudentMaterialService |
| Làm quiz (tự chấm) / nộp file tự luận | Student\AssignmentController | Student\SubmissionService |
| Xem điểm & nhận xét | Student\GradeController | Student\GradeService |
| Thảo luận + nhận thông báo | Shared DiscussionController, Student\NotificationController | DiscussionService, NotificationService |

### Module 5 — Kiểm thử & đóng gói
- Kiểm tra syntax toàn bộ file PHP, rà soát route ↔ view ↔ service.
- README hướng dẫn cài trên XAMPP, seeder tài khoản demo, file SQL dự phòng.

## 4. Quy ước bảo mật & chất lượng
- CSRF token trên mọi form (`@csrf`), Blade auto-escape chống XSS.
- Eloquent ORM 100% — không raw query → chống SQL Injection.
- Validate server-side bằng Form Request; upload giới hạn loại file & dung lượng.
- Middleware kiểm tra quyền sở hữu (GV chỉ thao tác lớp của mình, SV chỉ xem lớp đã ghi danh).
