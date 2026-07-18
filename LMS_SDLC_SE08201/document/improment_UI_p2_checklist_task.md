# Cải Tiến Giao Diện & Tính Năng Phân Hệ Student Phần 2 (Checklist)

*Trạng thái: Chưa bắt đầu*

---

## Module 4: Cải Tiến Trang Học Liệu

- [x] **Task 4.1 — Cập nhật Service (`app/Services/Student/StudentMaterialService.php`)**
  - [x] Cập nhật method `listFor()` nhận thêm tham số `?string $search = null`.
  - [x] Thêm logic lọc tìm kiếm theo `materials.title`, `subjects.subject_code`, `subjects.subject_name`.

- [x] **Task 4.2 — Cập nhật Controller (`app/Http/Controllers/Student/MaterialController.php`)**
  - [x] Cập nhật method `index()` lấy thêm biến `search` từ request.
  - [x] Truyền biến `$searchQuery` xuống view để giữ trạng thái cho ô tìm kiếm.

- [x] **Task 4.3 — Cập nhật Giao Diện (`resources/views/student/materials/index.blade.php`)**
  - [x] Bọc dropdown "Chọn môn học" vào form GET và thêm thuộc tính `onchange="this.form.submit()"` để tự động lọc.
  - [x] Thêm thanh tìm kiếm text (`input name="search"`) hỗ trợ tính năng debounce delay 400ms (`oninput="debounceSearch()"`).

- [x] **Tự Kiểm Thử (Verification) Module 4**
  - [x] Chạy lệnh `php artisan view:cache` đảm bảo syntax Blade không bị lỗi.
  - [x] Truy cập `/student/materials`: Giao diện hiển thị đúng.
  - [x] Kiểm thử thủ công: Dropdown lọc theo môn tự động kích hoạt form submit.
  - [x] Kiểm thử thủ công: Nhập nội dung vào thanh tìm kiếm tự load lại danh sách tài liệu chính xác.

---

## Module 5: Cải Tiến Trang Bảng Điểm

- [x] **Task 5.1 — Cập nhật Service (`app/Services/Student/GradeService.php`)**
  - [x] Bổ sung method `enrolledClassesForFilter(User $student)` để lấy danh sách lớp học cho thẻ dropdown.
  - [x] Cập nhật method `transcript()` thêm tham số `?int $classId = null`, `?string $search = null`.
  - [x] Logic lọc điểm theo `class_id` nếu có.
  - [x] Logic lọc điểm tìm kiếm theo `$search` (mã môn học, tên môn học, tên bài tập).
  - [x] Gỡ bỏ việc tính toán hiển thị điểm tổng khóa học (`overallAvg`), chỉ trả lại dữ liệu nhóm theo lớp.

- [x] **Task 5.2 — Cập nhật Controller (`app/Http/Controllers/Student/GradeController.php`)**
  - [x] Cập nhật method `index()` nhận `class_id`, `search` từ request.
  - [x] Gọi lấy danh sách `$enrolledClasses` và gửi kèm `$selectedClassId`, `$searchQuery` ra view.

- [x] **Task 5.3 — Cập nhật Giao Diện (`resources/views/student/grades.blade.php`)**
  - [x] Thêm thanh tìm kiếm (debounce 400ms) và dropdown chọn lớp (tự động submit form).
  - [x] Xóa bỏ phần hiển thị "Điểm trung bình toàn khóa".
  - [x] Bổ sung cột/nút "Chi tiết bài làm" ở mỗi dòng của bảng điểm bài tập, link dẫn đến `route('student.assignments.show', $assignment)`.

- [x] **Tự Kiểm Thử (Verification) Module 5**
  - [x] Chạy lệnh `php artisan view:cache` đảm bảo không lỗi cú pháp.
  - [x] Truy cập `/student/grades`: Giao diện load trơn tru, không còn xuất hiện thẻ điểm tổng.
  - [x] Kiểm thử thủ công: Bộ lọc chọn lớp chạy đúng, điểm trung bình thay đổi đúng theo lớp chọn.
  - [x] Kiểm thử thủ công: Click nút "Chi tiết bài làm" chuyển đến trang chi tiết bài nộp chính xác.

---

## Tổng Kết & Hoàn Thiện
- [x] Chạy `php artisan route:list --path=student` check lại để chắc chắn các route không có thay đổi sai sót.
- [x] Review lại toàn bộ giao diện đảm bảo tính thẩm mỹ, hiệu ứng mượt mà và không sinh side-effect ra Admin/Teacher.
- [x] Commit source code với convention đã quy định trong `QUY_TAC_DU_AN.md`.
