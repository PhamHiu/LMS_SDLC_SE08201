# Kế Hoạch Cải Tiến Trang Chi Tiết Bài Tập (Student)

## Bối Cảnh
Nâng cấp giao diện và chức năng trang xem chi tiết bài tập (Tự luận) của học sinh (`/student/assignments/{id}`).
Theo yêu cầu mới:
- Cho phép tải file đính kèm của giáo viên.
- Đổi cột lưu file nộp của học sinh sang `student_submit` thay vì `submit_file_path`.
- Học sinh nộp file (chỉ PDF, max 5MB), nộp xong không được nộp lại, hiển thị tên file.
- Cột `submit_file_path` sẽ được dùng để lưu file nhận xét/chấm điểm của giáo viên.
- Thiết kế một khung feedback ngay dưới khu vực nộp bài, thay đổi nội dung tuỳ theo trạng thái (chờ chấm, đã chấm).

## Các file hiện tại liên quan
- `app/Services/Student/SubmissionService.php` (Logic lưu file nộp)
- `resources/views/student/assignments/show.blade.php` (Giao diện chi tiết bài tập)
- `app/Http/Requests/Student/EssaySubmissionRequest.php` (Validation nộp file)
- `app/Models/Submission.php` (Model kết nối bảng `submissions`)

## Phân Tích Các Task Cần Thực Hiện

### Module 1: Cập Nhật Logic Backend & Cấu Hình Model
#### Task 1.1: Cập nhật Model và Request Validation
- **Mục tiêu:** Cập nhật file Model và Validation cho file nộp.
- **Chi tiết:**
  - `Submission.php`: Đảm bảo `student_submit` nằm trong `$fillable` để có thể ghi dữ liệu bằng Eloquent.
  - `EssaySubmissionRequest.php`: Thay đổi rule validate cho `file` thành `mimes:pdf|max:5120` (PDF dưới 5MB).

#### Task 1.2: Sửa đổi logic upload file của học sinh
- **Mục tiêu:** Thay đổi cột lưu trữ từ `submit_file_path` sang `student_submit`.
- **Chi tiết:**
  - `SubmissionService.php`: Trong method `submitEssay`, lưu URL upload file vào cột `student_submit`. Thay đổi logic chặn ghi đè nếu bài đã được nộp (vì học sinh không được nộp lại, dù chưa được chấm).

### Module 2: Cập Nhật Giao Diện Chi Tiết Bài Tập (Tự Luận)
#### Task 2.1: Hiển thị file tài liệu của giáo viên
- **Mục tiêu:** Thêm nút tải file của giáo viên trong khung thông tin bài tập.
- **Chi tiết:**
  - `show.blade.php`: Trong phần hiển thị thông tin chung phía trên, kiểm tra biến `$assignment->attachment_path`. Nếu có, tạo nút "Tải tài liệu đính kèm" với liên kết trỏ tới URL chứa file để tải về.

#### Task 2.2: Thay đổi giao diện nộp bài (Khung nộp bài)
- **Mục tiêu:** Chặn nộp lại và chỉ cho nộp file PDF max 5MB. Hiển thị tên file đã nộp.
- **Chi tiết:**
  - **Nếu học sinh chưa nộp:** Hiển thị form nộp bài với hướng dẫn "Định dạng: PDF · Tối đa 5MB".
  - **Nếu học sinh đã nộp:** Ẩn toàn bộ form upload. Thay vào đó, hiển thị một khung ghi nhận bài đã nộp với biểu tượng file và tên file (lấy link từ `student_submit` để xem lại). Xóa chức năng `Nộp lại bài (ghi đè bài cũ)`.

#### Task 2.3: Xây dựng Khung Feedback bên dưới khu vực nộp bài
- **Mục tiêu:** Hiển thị thông báo trạng thái hoặc kết quả chấm của giáo viên.
- **Chi tiết:**
  - Đặt một vùng `<div class="feedback-box">` nằm ngay bên dưới phần nộp bài ở Task 2.2.
  - **Trạng thái đã nộp nhưng chưa chấm (`!$submission->isGraded()`):** Khung feedback hiện thông báo: "Vui lòng chờ kết quả".
  - **Trạng thái đã chấm:** Khung feedback hiện các thông tin sau:
    - Số điểm: `score`/`max_score`.
    - Thời gian chấm: format từ `graded_at`.
    - Giáo viên chấm: Truy xuất qua `$assignment->classRoom->teacher->full_name`.
    - Nút Xem bài chấm: Nếu có `submit_file_path` thì tạo nút liên kết mở file nhận xét.
    - Khung Feedback Detail (dưới cùng): Chứa nội dung văn bản nhận xét của giáo viên (`feedback`).

## Verification Plan
1. **Automated Tests:** Chạy `php artisan view:cache` để đảm bảo Blade syntax không bị lỗi sau khi chỉnh sửa.
2. **Manual Verification:**
   - **Đóng vai học sinh:** Mở bài tập tự luận. Kiểm tra xem có thấy nút Tải file tài liệu đính kèm của giáo viên hay không.
   - **Upload File:** Thử tải lên file không phải PDF hoặc dung lượng > 5MB xem validate có trả về lỗi chính xác không.
   - **Thành công:** Tải file hợp lệ lên. Form upload phải biến mất, hiển thị tên file vừa tải.
   - **Kiểm tra trạng thái:** Dưới phần nộp bài, khung feedback phải hiện dòng chữ "Vui lòng chờ kết quả".
   - **Mô phỏng giáo viên chấm:** Truy cập CSDL trực tiếp hoặc dùng Tinker để điền giá trị cho cột `graded_at`, `score`, `submit_file_path`, `feedback` của submission đó. Reload trang.
   - **Kết quả cuối cùng:** Kiểm tra khung feedback đã hiển thị đúng điểm, thời gian chấm, tên giáo viên, nút xem bài chấm và nội dung text feedback chi tiết.

## User Review Required
> [!IMPORTANT]
> - Yêu cầu ghi file kết quả của giáo viên vào cột `submit_file_path`. Trong chức năng cũ, cột `submit_file_path` được dùng làm file do học sinh upload. Bạn vui lòng đảm bảo rằng các chức năng/giao diện phía giáo viên cũng đã/sẽ được sửa để tương thích với thay đổi này nhé.
> - Bảng `submissions` có cột `other_file_path` và `submit_file_path`. Xin xác nhận rằng giáo viên nộp file bài chấm vào `submit_file_path` theo như mô tả.
