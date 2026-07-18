# Checklist Task: Cải Tiến Giao Diện & Chức Năng Student

Tài liệu này dùng để theo dõi tiến độ công việc một cách chi tiết dựa trên kế hoạch cải tiến UI Student. Để đảm bảo an toàn và dễ dàng làm việc ngắt quãng, mỗi module được chia nhỏ thành các task với yêu cầu tự kiểm thử cuối mỗi giai đoạn.

---

## Module 1: Cải Tiến Trang Thời Khóa Biểu

- [x] **Task 1.1 — Cập nhật Service (`app/Services/Student/ScheduleService.php`)**
  - [x] Thêm method `availableYears()` trả danh sách năm có buổi học.
  - [x] Thêm method `weeksOfYear(int $year)` lấy danh sách tuần trong năm.
  - [x] Cập nhật method `weeklyTimetable()`: eager-load thêm `attendances` để lọc theo `user_id`.
  - [x] Thêm method `allSlots()` lấy danh sách ca học (`slots`).

- [x] **Task 1.2 — Cập nhật Controller (`app/Http/Controllers/Student/ScheduleController.php`)**
  - [x] Cập nhật method `index()` nhận tham số `year` và đổi `week` thành `week_start`.
  - [x] Truyền các biến mới vào view: `$years`, `$weeksOfYear`, `$selectedYear`, `$selectedWeekStart`, `$slots`, `$sessionGrid`.

- [x] **Task 1.3 — Viết lại giao diện TKB (`resources/views/student/schedule.blade.php`)**
  - [x] Xóa 3 nút "Tuần trước/Tuần này/Tuần sau".
  - [x] Thêm thanh dropdown "Chọn năm", "Chọn tuần", tự submit form (`onchange`) và nút "Làm mới".
  - [x] Xây dựng bảng lưới: cột thứ (Thứ 2 - CN), hàng dọc ca học.
  - [x] Hiển thị thông tin lớp, môn học, phòng vào đúng ô (nếu có lịch).
  - [x] Tô màu theo trạng thái: Xanh (P/L), Đỏ (A), Tím (Chưa điểm danh), không màu (Trống).

- [x] **Tự Kiểm Thử (Verification) Module 1**
  - [x] Chạy lệnh `php artisan view:cache` để đảm bảo không lỗi syntax Blade.
  - [x] Truy cập `/student/schedule`: Đảm bảo không lỗi 500.
  - [x] Kiểm thử thủ công: Thử thay đổi các dropdown Năm/Tuần xem TKB có chuyển đổi đúng không.
  - [x] Kiểm thử thủ công: Bảng lưới hiện đúng cấu trúc, màu sắc điểm danh chính xác. Nút "Làm mới" hoạt động đúng.

---

## Module 2: Cải Tiến Trang Lịch Sử Điểm Danh

- [x] **Task 2.1 — Cập nhật Service (`app/Services/Student/ScheduleService.php`)**
  - [x] Thêm method `enrolledClassesForFilter(User $student)` trả về list lớp cho dropdown.
  - [x] Cập nhật method `attendanceHistory()` nhận thêm `?int $classId`, `?string $search`.
  - [x] Thêm logic lọc theo `class_id` nếu có.
  - [x] Thêm logic search text theo ngày, tên lớp, hoặc trạng thái (Có mặt/Vắng/Đi muộn).
  - [x] Xử lý lấy `defaultClassId` nếu không có truyền lên.

- [x] **Task 2.2 — Cập nhật Controller (`app/Http/Controllers/Student/ScheduleController.php`)**
  - [x] Cập nhật method `attendance()` nhận tham số query `class_id`, `search`.
  - [x] Truyền thêm `$enrolledClasses`, `$selectedClassId`, `$searchQuery` ra view.

- [x] **Task 2.3 — Cập nhật giao diện Điểm danh (`resources/views/student/attendance.blade.php`)**
  - [x] Thêm thanh bộ lọc (Dropdown chọn lớp mặc định, text search với debounce form submission / js ~400ms).
  - [x] Thêm cột "Tuần dd/mm - dd/mm" ở đầu bảng, sử dụng `rowspan` để gộp dòng.
  - [x] Giữ nguyên các thẻ thống kê tổng quan bên trên.

- [x] **Tự Kiểm Thử (Verification) Module 2**
  - [x] Chạy lệnh `php artisan view:cache` không lỗi.
  - [x] Truy cập `/student/attendance`: Không lỗi server.
  - [x] Kiểm thử thủ công: Đổi dropdown lớp → Bảng list hiển thị đúng môn đó.
  - [x] Kiểm thử thủ công: Thử gõ tìm kiếm real-time → List cập nhật chính xác. Cột tuần rowspan hiển thị không bị vỡ giao diện.

---

## Module 3: Cải Tiến Trang Danh Sách Bài Tập

- [x] **Task 3.1 — Cập nhật Service (`app/Services/Student/SubmissionService.php`)**
  - [x] Cập nhật method `assignmentsFor()` thêm `?int $classId = null`, `?string $search = null`.
  - [x] Logic lọc `class_id` (nếu truyền) và search `title`/`class_name`.
  - [x] Sửa lại logic `orderBy` mặc định: Sắp xếp các bài chưa hết hạn `due_date ASC` đưa lên trước, các bài hết hạn `due_date DESC` xếp sau.

- [x] **Task 3.2 — Cập nhật Controller (`app/Http/Controllers/Student/AssignmentController.php`)**
  - [x] Cập nhật method `index()` nhận `class_id`, `search`.
  - [x] Lấy danh sách lớp đã học từ Service để đẩy ra dropdown.
  - [x] Truyền `$enrolledClasses`, `$selectedClassId`, `$searchQuery` ra view.

- [x] **Task 3.3 — Cập nhật giao diện Bài tập (`resources/views/student/assignments/index.blade.php`)**
  - [x] Thêm thanh bộ lọc (Dropdown lớp, input search debounce ~400ms) đặt phía trên các tabs bài tập.
  - [x] Giữ nguyên cấu trúc các tab Tự luận/Trắc nghiệm/Tất cả và card item hiện tại.

- [x] **Tự Kiểm Thử (Verification) Module 3**
  - [x] Chạy lệnh `php artisan view:cache` không lỗi.
  - [x] Truy cập `/student/assignments`: Đảm bảo danh sách load bình thường.
  - [x] Kiểm thử thủ công: Dropdown và ô search hoạt động đúng.
  - [x] Kiểm thử thủ công: Nhìn danh sách thấy ưu tiên bài sắp đến hạn trước, bài quá hạn đẩy xuống sau.

---

## Tổng Kết & Hoàn Thiện
- [x] Chạy `php artisan route:list --path=student` check lại không có lỗi Route.
- [x] Review lại toàn bộ tính năng tổng thể, đảm bảo không có side-effect sang các role Admin/Teacher.
- [x] Commit với convention đã quy định trong `QUY_TAC_DU_AN.md`.
