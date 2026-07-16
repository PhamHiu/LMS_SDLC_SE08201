# 📖 HƯỚNG DẪN CHẠY WEBSITE LMS CHI TIẾT (XAMPP + Laravel 11)

Tài liệu này hướng dẫn từng bước từ máy trắng chưa có gì → website chạy được, kèm xử lý mọi lỗi thường gặp.

---

## PHẦN A — CHUẨN BỊ MÔI TRƯỜNG (làm 1 lần duy nhất)

### Bước A1. Cài XAMPP (PHP 8.2 trở lên)

1. Tải XAMPP bản mới nhất tại: https://www.apachefriends.org/download.html
   - Chọn bản **PHP 8.2.x** trở lên (khuyến nghị 8.2 hoặc 8.3).
2. Cài đặt mặc định vào `C:\xampp`.
3. Mở **XAMPP Control Panel** → bấm **Start** cho 2 dịch vụ:
   - ✅ **Apache**
   - ✅ **MySQL**
4. Kiểm tra: mở trình duyệt vào `http://localhost` — thấy trang chào XAMPP là OK.

> ⚠️ Nếu Apache/MySQL không Start được: thường do trùng cổng (Skype, IIS chiếm cổng 80/3306). Bấm **Config → httpd.conf** đổi `Listen 80` thành `Listen 8080`, hoặc tắt ứng dụng chiếm cổng.

### Bước A2. Thêm PHP vào PATH (để gõ lệnh `php` ở mọi nơi)

1. Windows: mở **Edit the system environment variables** → **Environment Variables**.
2. Ở mục **Path** → **Edit** → **New** → thêm: `C:\xampp\php`
3. Mở **cửa sổ CMD mới** và kiểm tra:
   ```bash
   php -v
   ```
   Phải hiện `PHP 8.2.x` (hoặc cao hơn). Nếu hiện phiên bản < 8.2 → cài lại XAMPP bản mới hơn.

### Bước A3. Cài Composer

1. Tải Composer cho Windows: https://getcomposer.org/Composer-Setup.exe
2. Chạy file cài đặt — khi được hỏi chọn PHP, trỏ đến `C:\xampp\php\php.exe`.
3. Mở **cửa sổ CMD mới**, kiểm tra:
   ```bash
   composer -V
   ```
   Hiện `Composer version 2.x.x` là OK.

### Bước A4. Bật các extension PHP cần thiết

Mở file `C:\xampp\php\php.ini` (Notepad), tìm và **bỏ dấu `;` ở đầu dòng** (nếu có) của các dòng sau:

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=fileinfo
extension=curl
extension=zip
extension=gd
```

Lưu file → **Stop rồi Start lại Apache** trong XAMPP Control Panel.

---

## PHẦN B — CÀI ĐẶT DỰ ÁN LMS

### Bước B1. Giải nén source code

1. Giải nén `LMS_Laravel_Full.zip`.
2. Copy thư mục `lms` vào nơi bạn muốn, ví dụ: `C:\Projects\lms`
   (nếu muốn chạy qua Apache thì copy vào `C:\xampp\htdocs\lms`).

### Bước B2. Cài thư viện PHP (bắt buộc)

Mở CMD **tại thư mục dự án**:

```bash
cd C:\Projects\lms
composer install
```

- Quá trình tải mất 2–5 phút (cần mạng). Chạy xong sẽ xuất hiện thư mục `vendor/`.
- Nếu báo thiếu extension → quay lại **Bước A4**.

### Bước B3. Tạo database

1. Mở trình duyệt: `http://localhost/phpmyadmin`
2. Bấm **New** (cột trái).
3. Nhập thông tin:
   - **Database name:** `lms`
   - **Collation:** `utf8mb4_unicode_ci`
4. Bấm **Create**. (Chỉ tạo database rỗng — KHÔNG cần import gì cả, bảng sẽ được tạo tự động ở bước sau.)

### Bước B4. Kiểm tra file cấu hình `.env`

File `.env` đã được cấu hình sẵn cho XAMPP mặc định, **thường không cần sửa gì**:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms
DB_USERNAME=root
DB_PASSWORD=
```

Chỉ sửa khi máy bạn khác mặc định:
- MySQL có mật khẩu → điền vào `DB_PASSWORD=`
- Đổi tên database → sửa `DB_DATABASE=`

> `APP_KEY` đã có sẵn, không cần chạy `php artisan key:generate`. (Chạy lại cũng không sao.)

### Bước B5. Tạo bảng + nạp dữ liệu demo

```bash
php artisan migrate --seed
```

Kết quả mong đợi: 5 migration chạy `DONE`, seeder chạy xong không lỗi.
Lệnh này tự tạo **15 bảng** và nạp toàn bộ dữ liệu demo (tài khoản, lớp, buổi học, điểm danh, bài tập, quiz, điểm, thảo luận, thông báo).

### Bước B6. Tạo symlink cho file upload

```bash
php artisan storage:link
```

Bước này giúp tải/xem được học liệu, bài nộp, avatar. Chỉ cần chạy 1 lần.

### Bước B7. Chạy website 🚀

```bash
php artisan serve
```

Mở trình duyệt: **http://localhost:8000**

> Giữ nguyên cửa sổ CMD đang chạy. Muốn dừng web: bấm `Ctrl + C`.

---

## PHẦN C — ĐĂNG NHẬP & KHÁM PHÁ

Mật khẩu chung cho mọi tài khoản demo: **`password`**

| Vai trò | Email | Vào được gì |
|---|---|---|
| 👑 Quản trị viên | `admin@lms.vn` | Quản lý người dùng, ngành, môn, lớp, thông báo toàn trường |
| 👨‍🏫 Giảng viên | `teacher1@lms.vn` (đến teacher3) | Lớp phụ trách, điểm danh, học liệu, giao bài, chấm bài |
| 🎓 Sinh viên | `student1@lms.vn` (đến student12) | TKB, điểm danh, học liệu, làm quiz, nộp bài, xem điểm |

### Kịch bản test nhanh (10 phút đi hết luồng chính)

1. **Admin** (`admin@lms.vn`):
   - Dashboard → xem thống kê.
   - Quản lý lớp học → bấm **Quản lý** một lớp → thử xếp thêm sinh viên vào lớp.
   - Thông báo hệ thống → gửi 1 thông báo toàn trường.
2. **Giảng viên** (`teacher1@lms.vn`):
   - Lớp của tôi → chọn lớp → bấm **Điểm danh** một buổi → lưu.
   - Bài tập → **Giao bài cho lớp** → chọn loại **Quiz** → thêm 2–3 câu hỏi → giao bài.
   - Bài tập & Chấm bài → chọn bài tự luận → **Chấm bài** → nhập điểm + nhận xét.
3. **Sinh viên** (`student1@lms.vn`):
   - Dashboard → thấy thông báo Admin vừa gửi + bài sắp đến hạn.
   - Bài tập & Quiz → mở bài Quiz giảng viên vừa tạo → **Bắt đầu làm bài** → nộp → xem điểm tự chấm ngay.
   - Bài tự luận → nộp file PDF → quay lại tài khoản giảng viên chấm → sinh viên xem điểm + nhận xét ở **Bảng điểm**.

---

## PHẦN D — CHẠY QUA APACHE CỦA XAMPP (tùy chọn, thay cho `artisan serve`)

1. Copy dự án vào `C:\xampp\htdocs\lms`
2. Truy cập: `http://localhost/lms/public`

Muốn URL đẹp (`http://lms.test`), tạo Virtual Host:

1. Mở `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, thêm cuối file:
   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/lms/public"
       ServerName lms.test
       <Directory "C:/xampp/htdocs/lms/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
2. Mở Notepad **bằng quyền Administrator**, mở file `C:\Windows\System32\drivers\etc\hosts`, thêm dòng:
   ```
   127.0.0.1    lms.test
   ```
3. Restart Apache → truy cập `http://lms.test`

---

## PHẦN E — BẢNG XỬ LÝ LỖI THƯỜNG GẶP

| Lỗi hiển thị | Nguyên nhân | Cách sửa |
|---|---|---|
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL chưa chạy | Mở XAMPP Control Panel → Start MySQL |
| `SQLSTATE[HY000] [1049] Unknown database 'lms'` | Chưa tạo database | Làm lại **Bước B3** |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | MySQL có mật khẩu | Điền mật khẩu vào `DB_PASSWORD=` trong `.env` |
| `Class ... not found` / trang trắng | Chưa `composer install` hoặc autoload cũ | `composer install` rồi `composer dump-autoload` |
| `No application encryption key` | Thiếu APP_KEY | `php artisan key:generate` |
| Ảnh đại diện / file tải về báo lỗi | Chưa tạo symlink | `php artisan storage:link` |
| `could not find driver` | Chưa bật `pdo_mysql` | Làm lại **Bước A4** rồi restart Apache |
| `1071 Specified key was too long` | MySQL/MariaDB bản cũ | Đã xử lý sẵn trong code (`defaultStringLength(191)`) — nếu vẫn lỗi, chạy `php artisan migrate:fresh --seed` |
| Trang lỗi 419 Page Expired khi submit form | Session/cache cũ | Xóa cookie trình duyệt hoặc `php artisan optimize:clear` |
| Sửa `.env` nhưng không có tác dụng | Config bị cache | `php artisan config:clear` |
| Muốn xóa sạch dữ liệu làm lại từ đầu | — | `php artisan migrate:fresh --seed` |
| Giao diện mất style (trắng trơn) | Máy không có mạng (Tailwind + font tải qua CDN) | Kết nối mạng, hoặc tải Tailwind về dùng offline |

---

## PHẦN F — LỆNH TÓM TẮT (copy nhanh)

```bash
cd C:\Projects\lms
composer install
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

→ Mở **http://localhost:8000** → đăng nhập `admin@lms.vn` / `password` ✅
