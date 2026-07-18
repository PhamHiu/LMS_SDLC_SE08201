# Checklist Task: Cải Tiến Chi Tiết Bài Tập (Student)

Tài liệu này dùng để theo dõi tiến độ công việc một cách chi tiết dựa trên kế hoạch cải tiến trang xem chi tiết bài tập tự luận của học sinh. Để đảm bảo an toàn và dễ dàng làm việc ngắt quãng, kế hoạch được chia nhỏ thành các module và task với yêu cầu tự kiểm thử cuối mỗi giai đoạn.

---

## Module 1: Cập Nhật Logic Backend & Cấu Hình Model

- [x] **Task 1.1 — Cập nhật Model và Request Validation**
  - [x] Thêm `student_submit` vào `$fillable` trong model `app/Models/Submission.php`.
  - [x] Cập nhật file `app/Http/Requests/Student/EssaySubmissionRequest.php`: đổi validation rule cho `file` thành `mimes:pdf|max:5120`.

- [x] **Task 1.2 — Sửa đổi logic upload file của học sinh**
  - [x] Cập nhật method `submitEssay` trong `app/Services/Student/SubmissionService.php`.
  - [x] Lưu đường dẫn upload vào cột `student_submit` thay vì `submit_file_path`.
  - [x] Bổ sung logic chặn ghi đè nếu đã nộp bài (vì hệ thống mới yêu cầu chỉ được nộp 1 lần, không có tính năng nộp lại).

- [x] **Tự Kiểm Thử (Verification) Module 1**
  - [x] Gửi thử request nộp bài với file không phải PDF hoặc file > 5MB, đảm bảo API chặn và trả lỗi validation đúng.
  - [x] Gửi thử request hợp lệ với file PDF, kiểm tra CSDL bảng `submissions` cột `student_submit` có lưu link, `submit_file_path` trống.

---

## Module 2: Cập Nhật Giao Diện Chi Tiết Bài Tập (Tự Luận)

- [x] **Task 2.1 — Hiển thị file tài liệu của giáo viên**
  - [x] Thêm nút "Tải tài liệu đính kèm" trong phần hiển thị thông tin chung tại file `resources/views/student/assignments/show.blade.php`.
  - [x] Chỉ hiển thị nút này nếu `$assignment->attachment_path` có dữ liệu.

- [x] **Task 2.2 — Thay đổi giao diện nộp bài (Khung nộp bài)**
  - [x] Nếu chưa nộp: Hiển thị form upload với thông báo định dạng "PDF · Tối đa 5MB".
  - [x] Nếu đã nộp: Ẩn form upload hoàn toàn (cùng nút submit).
  - [x] Thay form upload bằng khối hiển thị biểu tượng file và link để xem lại file đã nộp (lấy từ cột `student_submit`).
  - [x] Xóa chữ "Nộp lại bài (ghi đè bài cũ)" không còn sử dụng.

- [x] **Task 2.3 — Xây dựng Khung Feedback bên dưới khu vực nộp bài**
  - [x] Tạo một khối div mới `<div class="feedback-box">` nằm dưới vùng nộp bài.
  - [x] Nếu trạng thái đã nộp nhưng chưa chấm (`!$submission->isGraded()`): Hiển thị chữ "Vui lòng chờ kết quả".
  - [x] Nếu trạng thái đã chấm: Hiển thị Số điểm (`score`/`max_score`).
  - [x] Hiển thị Thời gian chấm (`graded_at`).
  - [x] Hiển thị Tên giáo viên chấm (Lấy từ `$assignment->classRoom->teacher->full_name`).
  - [x] Hiển thị nút Xem bài chấm (link tới file trong cột `submit_file_path` do giáo viên đẩy lên).
  - [x] Dưới cùng khung: Hiển thị văn bản nhận xét (`feedback`) nếu có.

- [x] **Tự Kiểm Thử (Verification) Module 2**
  - [x] Chạy lệnh `php artisan view:cache` để đảm bảo syntax file Blade không lỗi.
  - [x] Đóng vai sinh viên: Xác nhận hiển thị được link file của giáo viên đính kèm trên bài tập.
  - [x] Thực hiện nộp 1 bài: Form biến mất, hiện tên file sinh viên vừa nộp, và hiển thị "Vui lòng chờ kết quả" ở vùng feedback.
  - [x] Dùng công cụ CSDL hoặc Tinker chỉnh sửa dữ liệu để mô phỏng giáo viên đã chấm (điền `graded_at`, `score`, `submit_file_path`, `feedback`). Reload trang và xem giao diện cập nhật đầy đủ toàn bộ nhận xét, điểm và link file chấm của giáo viên.

---

## Tổng Kết & Hoàn Thiện
- [ ] Review lại tính năng tổng thể, đảm bảo toàn bộ luồng học sinh xem bài - lấy đề - nộp bài - chờ chấm - xem điểm diễn ra trơn tru.
- [ ] Chắc chắn rằng không có lỗi gây ảnh hưởng đến trang Danh sách Bài tập.
- [ ] Commit theo đúng convention `QUY_TAC_DU_AN.md`.
