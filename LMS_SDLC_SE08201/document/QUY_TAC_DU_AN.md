# Cấu Trúc Yêu Cầu Và Quy Tắc Làm Việc Dự Án LMS

## I. Tổng Quan Dự Án

### 1. Về Tech Stack & Kiến trúc

- **Tech Stack:** Laravel 11, MySQL, Tailwind CSS, Blade.
- **Kiến trúc:** Tuân thủ nghiêm ngặt mô hình **MVC mở rộng (Service Pattern)**.
- **Luồng xử lý (Data Flow):** Request → Route → Middleware (CheckRole) → Form Request (Validate) → Controller → Service (Logic nghiệp vụ) → Model (Eloquent) → Database.

### 2. Về Cơ sở dữ liệu

*Tự động cập nhật từ dự án hiện tại.*

### 3. Về Phân quyền & Cấu trúc thư mục

*Tự động cập nhật từ dự án hiện tại.*

### 4. tổng quan Về Luồng tính năng (Modules/Sprints)

Hệ thống được chia thành các phân hệ độc lập:

- **Module Admin (Quản trị nền tảng):** Quản lý User, Ngành học, Môn học, Lớp học, sinh buổi học, và thông báo hệ thống.
- **Module Teacher (Vận hành lớp):** Quản lý dashboard lịch dạy, học liệu, điểm danh, bài tập/Quiz, chấm điểm, thảo luận.
- **Module Student (Tham gia học):** Theo dõi lịch học, học liệu, làm bài tập/Quiz, xem điểm, và nhận thông báo.
- **Các Module chung:** Xác thực (Auth), Thảo luận, Thông báo.

### 5. Về Tiêu chuẩn bảo mật

- **Chống SQL Injection:** 100% sử dụng Eloquent ORM, không dùng raw query.
- **Validation:** Ràng buộc dữ liệu server-side chặt chẽ thông qua các Form Request.
- **Phân quyền (Authorization):** Sử dụng Middleware để kiểm tra phân quyền (Role) và quyền sở hữu dữ liệu (ví dụ: Giảng viên chỉ sửa dữ liệu lớp mình, Sinh viên chỉ xem dữ liệu lớp mình).
- **Chống XSS/CSRF:** Blade template auto-escape và bắt buộc sử dụng `@csrf` cho mọi form.

---

## II. Quy Tắc Khi Làm Việc

Để đảm bảo chất lượng source code, tránh conflict khi làm việc nhóm và giữ cho hệ thống dễ bảo trì, tôi và đội ngũ dự án cam kết tuân thủ nghiêm ngặt các nguyên tắc sau:

### 1. Quản lý tác động (Scope Management)

- Trước khi chỉnh sửa một tính năng, luôn tiến hành kiểm tra để xác định chính xác các file liên quan đang đảm nhiệm tính năng đó (Controller, Service, Request, View).
- **Tác động tối thiểu:** Chỉ thực hiện chỉnh sửa trên các file thuộc về module được yêu cầu. Hạn chế tối đa việc chạm vào các file không liên quan (đặc biệt là Auth, Middleware chung) nếu không thực sự cần thiết, nhằm ngăn ngừa lỗi side-effect.
- **Mở rộng rõ ràng (Clean Extension):** Khi thêm chức năng mới, ưu tiên tạo mới các file (như Controller mới, Service mới) theo đúng cấu trúc chuẩn thay vì nhồi nhét code vào các file có sẵn.
- **Code Độc lập:** Tuân thủ nguyên tắc Single Responsibility (Đơn trách nhiệm), đặc biệt ở tầng Service, giúp code sạch, dễ đọc và an toàn khi merge.

### 2. Quy trình làm việc với Git

Bắt buộc áp dụng Workflow tạo branch riêng cho từng chức năng/cải tiến:

- **Bước 1 - Lấy code mới:**
  Kiểm tra và cập nhật nhánh chính trước khi bắt đầu.

  ```bash
  git checkout main
  git pull origin main
  ```

- **Bước 2 - Khởi tạo nhánh:**
  Tạo một nhánh mới dựa trên nhánh main.

  ```bash
  git checkout -b <tên-nhánh-mới>
  ```

 **Bước 3 - Phát triển:**
 khi xây code luôn ưu tiên làm theo các quy tắc ở mục 1, luôn kiểm tra file có liên quan đến module mình đang xây, không đụng vào module khác nếu không cần thiết, ưu tiên tạo một file hoặc class mới theo đúng quy tắc nếu không có file hiện tại để xây dựng, không sửa đè lên logic hay cấu trúc của file có sẵn nếu không cần thiết, đảm bảo code sạch sẽ và dễ bảo trì, tránh xung đột khi merge.
 nếu cần các thao tác add hoặc update db thì phải tạo file mới trong migrations

**Bước 4 - Đồng bộ code (Merge):**
  Merge `main` vào nhánh vừa tạo để đảm bảo code trên nhánh này là bản cập nhật mới nhất từ toàn bộ dự án.

  ```bash
  git merge main
  ```

- **Bước 5 - test và sửa lỗi nếu cần thiết:**
 tiến hành kiểm thử và sửa lỗi nếu cần thiết trên nhánh vừa tạo, đảm bảo code hoạt động ổn định và đáp ứng đúng yêu cầu.

- **Bước 6 - Chờ duyệt & Đẩy code:**
  - Không được phép tự động push code khi chưa có thông báo.
  - Chờ đợi quá trình test và xác nhận "đã OK".
  - Chỉ khi có lệnh đồng ý, mới thực hiện `git add`, `git commit` và `git push`.

### 3. Quy chuẩn Commit Message

Mỗi khi commit, nội dung phải được viết rõ ràng, rành mạch theo các gạch đầu dòng:

```text
[Tên Task/Module] Tóm tắt ngắn gọn nội dung đã làm

- Chi tiết thay đổi 1 (Ví dụ: Cập nhật giao diện phần Dashboard)
- Chi tiết thay đổi 2 (Ví dụ: Thêm hàm xử lý lấy dữ liệu vào Service X)
- Chi tiết thay đổi 3 (Ví dụ: Thêm validate tại Form Request Y)
```
