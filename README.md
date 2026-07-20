# 🎓 LMS — Hệ thống quản lý học tập (Laravel 11 + MySQL)

Website LMS hoàn chỉnh với 3 nhóm quyền **Admin / Giảng viên / Sinh viên**, xây dựng theo mô hình **MVC mở rộng** với luồng xử lý chuẩn:

```
Browser → Route → Middleware → Form Request → Controller → Service → Model → Database
Database → Model → Service → Controller → Blade View → Browser
```

Tech stack: **Laravel 11 · MySQL (XAMPP) · Blade · Tailwind CSS (CDN — không cần npm)**

---

## 1. Yêu cầu môi trường

| Thành phần | Phiên bản |
|---|---|
| XAMPP (Apache + MySQL) | PHP **8.2+** |
| Composer | 2.x |

> Kiểm tra: `php -v` và `composer -V`. Nếu XAMPP có PHP < 8.2, tải bản XAMPP mới nhất.

## 2. Cài đặt (5 bước)

```bash
# 0. Giải nén / copy thư mục lms vào nơi bạn muốn (VD: C:\Projects\lms)
cd lms

# 1. Cài thư viện PHP (bắt buộc — thư mục vendor chưa kèm sẵn)
composer install

# 2. Tạo database: mở phpMyAdmin (http://localhost/phpmyadmin)
#    → New → tên database: lms → Collation: utf8mb4_unicode_ci
#    (File .env đã cấu hình sẵn: DB lms, user root, không mật khẩu — sửa nếu khác)

# 3. Tạo bảng + dữ liệu demo
php artisan migrate --seed

# 4. Tạo symlink cho file upload (học liệu, bài nộp, avatar)
php artisan storage:link

# 5. Chạy web
php artisan serve
# → Mở http://localhost:8000
```

> File `.env` đã kèm sẵn APP_KEY nên **không cần** chạy `key:generate`. Nếu muốn tự sinh key mới: `php artisan key:generate`.

### Chạy qua Apache của XAMPP (tùy chọn)
Copy thư mục `lms` vào `C:\xampp\htdocs\` rồi truy cập `http://localhost/lms/public`. Khuyến nghị dùng `php artisan serve` cho đơn giản.

## 3. Tài khoản demo (mật khẩu chung: `password`)

| Vai trò | Email |
|---|---|
| Quản trị viên | `admin@lms.vn` |
| Giảng viên | `teacher1@lms.vn` → `teacher3@lms.vn` |
| Sinh viên | `student1@lms.vn` → `student12@lms.vn` |

Dữ liệu demo gồm: 3 chuyên ngành, 6 môn học, 4 lớp (kèm 12 buổi học/lớp), điểm danh các buổi đã qua, học liệu, quiz 5 câu + bài tự luận mỗi lớp, bài nộp đã chấm/chờ chấm, thảo luận và thông báo.

## 4. Chức năng theo 3 nhóm quyền

**Admin:** Dashboard thống kê · CRUD người dùng (phân quyền, reset mật khẩu) · CRUD chuyên ngành · CRUD môn học (gán nhiều ngành) · Tạo lớp + gán giảng viên + xếp sinh viên + tự sinh buổi học · Thông báo toàn hệ thống.

**Giảng viên:** Dashboard lịch dạy & tiến độ lớp · Danh sách lớp/sinh viên + thống kê chuyên cần · Upload học liệu (PDF/Word/Video) · Điểm danh từng buổi (Có mặt/Muộn/Vắng + ghi chú) · Tạo bài Tự luận & Quiz (builder câu hỏi động) · Chấm bài + nhận xét + file feedback (Quiz tự chấm) · Thông báo lớp · Trả lời thảo luận.

**Sinh viên:** Dashboard bài sắp đến hạn + kết quả + lịch tuần · Thời khóa biểu · Lịch sử điểm danh · Xem/tải học liệu · Làm Quiz online (tự chấm ngay, xem lại từng câu) · Nộp file tự luận (nộp lại trước hạn nếu chưa chấm) · Bảng điểm theo lớp · Thảo luận · Nhận thông báo.

## 5. Cấu trúc thư mục chính

```
app/
├── Http/
│   ├── Controllers/{Auth, Admin, Teacher, Student}/   # phân theo 3 nhóm quyền
│   ├── Middleware/CheckRole.php                       # phân quyền role
│   └── Requests/{Auth, Admin, Teacher, Student}/      # Form Request validation
├── Models/                                            # 14 model ↔ 15 bảng (3NF)
└── Services/{Admin, Teacher, Student}/                # business logic
database/
├── migrations/                                        # 5 file theo 5 phân hệ CSDL
└── seeders/DatabaseSeeder.php                         # dữ liệu demo
resources/views/{layouts, auth, profile, admin, teacher, student, shared}/
routes/web.php                                         # route nhóm theo prefix + middleware role
```

Chi tiết kế hoạch từng module: xem `KE_HOACH.md`.

## 6. Bảo mật

- **SQL Injection**: 100% Eloquent ORM, không raw query.
- **XSS**: Blade tự escape (`{{ }}`).
- **CSRF**: `@csrf` trên mọi form.
- **Phân quyền 2 lớp**: middleware `role:` trên route + kiểm tra quyền sở hữu trong Service (GV chỉ thao tác lớp mình dạy, SV chỉ truy cập lớp đã ghi danh).
- **Upload**: whitelist định dạng + giới hạn dung lượng qua Form Request.

## 7. Xử lý sự cố thường gặp

| Lỗi | Cách xử lý |
|---|---|
| `SQLSTATE Connection refused` | Chưa bật MySQL trong XAMPP Control Panel |
| `Unknown database 'lms'` | Chưa tạo database `lms` trong phpMyAdmin |
| Ảnh/file không tải được | Chưa chạy `php artisan storage:link` |
| `Class not found` sau khi sửa code | Chạy `composer dump-autoload` |
| Muốn reset dữ liệu demo | `php artisan migrate:fresh --seed` |
