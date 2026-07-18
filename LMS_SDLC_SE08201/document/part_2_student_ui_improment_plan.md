# Kế Hoạch Cải Tiến Giao Diện & Chức Năng Student (Phần 2) — Hệ Thống LMS

## Bối Cảnh

Dự án LMS tiếp tục giai đoạn cải tiến với 2 module thuộc phân hệ **Student**: Trang Học Liệu và Bảng Điểm. Mục tiêu là tối ưu hóa trải nghiệm tìm kiếm, duyệt tài liệu và xem điểm với các bộ lọc tự động kích hoạt (real-time) và giao diện trực quan hơn.

**Phạm vi tác động:** Chỉ thay đổi các file thuộc module Student. Tuân thủ đúng quy tắc mở rộng và không tác động sai phạm vi.

---

## User Review Required

> [!IMPORTANT]
> **Về tìm kiếm lớp trong Học Liệu:** Bảng `materials` trong CSDL hiện tại chỉ liên kết với môn học (`subject_id`), không liên kết trực tiếp với lớp (`class_id`). Do đó thanh tìm kiếm học liệu sẽ hỗ trợ tìm theo **Tiêu đề tài liệu, Mã môn học và Tên môn học** (thay vì tìm theo lớp). Xin xác nhận.

> [!IMPORTANT]
> **Về giao diện Bảng Điểm:** Yêu cầu "mặc định chỉ hiển thị điểm trung bình vào bộ lọc, không hiện bảng điểm tổng" được tôi phân tích thành: Sẽ bỏ phần thống kê "Điểm trung bình toàn khóa" trên cùng. Hệ thống sẽ chỉ hiển thị điểm trung bình của từng lớp riêng biệt. Khi người dùng lọc theo một lớp cụ thể, họ sẽ chỉ thấy điểm trung bình của lớp đó. Xin xác nhận.

---

## Phân Tích Hiện Trạng

### Các file hiện tại liên quan

| Thành phần | File | Vai trò |
|---|---|---|
| Controller | `MaterialController.php` | Xử lý logic hiển thị Học liệu |
| Controller | `GradeController.php` | Xử lý logic hiển thị Bảng điểm |
| Service | `StudentMaterialService.php` | Xử lý truy vấn Học liệu |
| Service | `GradeService.php` | Xử lý gom nhóm và tính điểm |
| View | `materials/index.blade.php` | Giao diện Danh sách Học liệu |
| View | `grades.blade.php` | Giao diện Bảng điểm tổng hợp |

---

## Proposed Changes

Kế hoạch được chia thành **2 Module độc lập** có thể thực hiện song song.

---

### Module 4: Cải Tiến Trang Học Liệu

**Mục tiêu:** Thêm thanh tìm kiếm text, biến dropdown chọn môn thành tự động kích hoạt (auto-submit), hiển thị tất cả nếu không chọn môn.

#### Task 4.1 — Cập nhật Service
**[MODIFY] `app/Services/Student/StudentMaterialService.php`**
- Cập nhật method `listFor()`: nhận thêm tham số `?string $search = null`.
- Thêm logic lọc `where` gom nhóm (`whereHas('subject', ...)` và `where('title', ...)`): Cho phép tìm kiếm theo `materials.title`, `subjects.subject_code`, `subjects.subject_name`.
- Mặc định method này đang filter theo `$subjectIds` của user nên yêu cầu "hiển thị tất cả tài liệu liên quan môn đang học" đã được đáp ứng sẵn.

#### Task 4.2 — Cập nhật Controller
**[MODIFY] `app/Http/Controllers/Student/MaterialController.php`**
- Cập nhật method `index()`: Nhận thêm param `search` từ request.
- Gửi biến `$searchQuery` xuống view để giữ trạng thái cho text input.

#### Task 4.3 — Cập nhật Giao Diện
**[MODIFY] `resources/views/student/materials/index.blade.php`**
- Bọc dropdown "Chọn môn" hiện tại vào trong một `<form>` dùng method GET.
- Thêm thuộc tính `onchange="this.form.submit()"` vào dropdown để tự kích hoạt khi chọn.
- Thêm ô `<input type="text" name="search">` với `oninput="debounceSearch()"` (delay 400ms tương tự module trước).
- Giữ nguyên hiển thị danh sách tài liệu.

---

### Module 5: Cải Tiến Trang Bảng Điểm

**Mục tiêu:** Thêm bộ lọc lớp học, thanh tìm kiếm text, nút "Chi tiết" ở mỗi bài tập, loại bỏ điểm tổng thể khóa học.

#### Task 5.1 — Cập nhật Service
**[MODIFY] `app/Services/Student/GradeService.php`**
- Bổ sung method `enrolledClassesForFilter(User $student)` để lấy list lớp học cho dropdown (tái sử dụng logic giống ScheduleService).
- Cập nhật method `transcript()`: nhận thêm `?int $classId = null`, `?string $search = null`.
- Logic lọc:
  - Lọc theo `class_id` nếu có chọn.
  - Lọc theo `$search` (chứa từ khóa trong mã môn học, tên môn học, tên bài tập).
- Cập nhật định dạng trả về: Gỡ bỏ key `overallAvg` (điểm trung bình tổng khóa), chỉ trả ra `$byClass` (kèm avg của riêng lớp đó).

#### Task 5.2 — Cập nhật Controller
**[MODIFY] `app/Http/Controllers/Student/GradeController.php`**
- Cập nhật method `index()`: lấy `class_id` và `search` từ request.
- Truyền danh sách lớp `$enrolledClasses`, `$selectedClassId` và `$searchQuery` xuống view.

#### Task 5.3 — Cập nhật Giao Diện
**[MODIFY] `resources/views/student/grades.blade.php`**
- Thêm thanh tìm kiếm và bộ lọc (tương tự form của Module 4). Dropdown chọn lớp tự kích hoạt.
- Xóa các block UI hiển thị "Điểm trung bình toàn khóa".
- Trong bảng điểm của mỗi lớp:
  - Thêm một cột (hoặc nút bấm ở cuối dòng) "Chi tiết bài làm".
  - Link dẫn tới `route('student.assignments.show', $assignment_id)`.

---

## Tóm Tắt Files Thay Đổi

| # | File | Loại thay đổi | Module |
|---|---|---|---|
| 1 | `app/Services/Student/StudentMaterialService.php` | MODIFY — cập nhật method `listFor()` | M4 |
| 2 | `app/Http/Controllers/Student/MaterialController.php` | MODIFY — truyền thêm biến query | M4 |
| 3 | `resources/views/student/materials/index.blade.php` | MODIFY — thêm bộ lọc form submit | M4 |
| 4 | `app/Services/Student/GradeService.php` | MODIFY — thêm lọc, gỡ điểm tổng | M5 |
| 5 | `app/Http/Controllers/Student/GradeController.php` | MODIFY — truyền tham số view | M5 |
| 6 | `resources/views/student/grades.blade.php` | MODIFY — thêm bộ lọc, cột nút chi tiết | M5 |

---

## Verification Plan

### Automated Tests
```bash
php artisan route:list --path=student
php artisan view:cache
```

### Manual Verification
- **Trang Học Liệu (`/student/materials`):** 
  - Đổi dropdown "Chọn môn học" -> list tài liệu cập nhật tương ứng.
  - Nhập mã môn học hoặc tên tài liệu vào ô tìm kiếm -> kết quả load lại đúng nội dung.
- **Trang Bảng Điểm (`/student/grades`):** 
  - Đảm bảo điểm số tổng khóa học không còn xuất hiện.
  - Lọc theo lớp cụ thể -> chỉ hiện điểm trung bình và bài làm của lớp đó.
  - Bấm nút "Chi tiết" -> chuyển hướng thành công sang giao diện xem lại bài làm.
