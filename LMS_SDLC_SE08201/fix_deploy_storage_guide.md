# Hướng Dẫn Cấu Hình Lưu Trữ File Khi Triển Khai (Deploy) Dự Án

Bản hướng dẫn này cung cấp 2 giải pháp quản lý file tải lên (ví dụ: bài nộp của sinh viên, tài liệu giáo viên) khi bạn đưa dự án lên server chạy thực tế. 

Tài liệu được chia thành 2 phần riêng biệt theo đúng nhu cầu sử dụng thực tế của dự án.

---

## Phần 1: Khắc Phục Lỗi Không Hiện File (Lỗi 404) Trên VPS/Server Cố Định

### 1. Nguyên nhân gây ra lỗi
Trong Laravel, vì lý do bảo mật, người dùng từ bên ngoài Internet chỉ có thể truy cập các nội dung được đặt trực tiếp trong thư mục `public/`.
Tuy nhiên, file upload an toàn thường được ứng dụng lưu vào thư mục `storage/app/public/`. 

Do đó, nếu bạn không tạo một "cây cầu" (symlink) để nối từ thư mục `public/storage` sang thư mục `storage/app/public/`, trình duyệt sẽ không thể tìm thấy file và báo lỗi **404 Not Found** khi sinh viên nhấn nút tải file.

### 2. Cách khắc phục khi Deploy
Sau khi đưa source code lên máy chủ (VPS, Shared Hosting hỗ trợ SSH, Ubuntu Server, v.v...), bạn cần mở Terminal (hoặc SSH vào server), di chuyển tới thư mục gốc của dự án và chạy duy nhất 1 lệnh sau:

```bash
php artisan storage:link
```

**⚠️ Lưu ý quan trọng về Quyền hạn (Permissions) trên Server:**
Ngay cả khi đã chạy lệnh liên kết ở trên, trên môi trường Linux (Ubuntu/CentOS), bạn có thể vẫn gặp lỗi hoặc không upload được file nếu không cấp quyền cho thư mục. Bạn bắt buộc phải cấp quyền đọc ghi cho user của web server (thường là `www-data` đối với Nginx hoặc Apache):

```bash
# Cấp quyền đọc/ghi/thực thi cho thư mục storage
chmod -R 775 storage bootstrap/cache

# Trao quyền chủ sở hữu cho tiến trình web server
chown -R www-data:www-data storage bootstrap/cache
```

---

## Phần 2: Hướng Dẫn Chuyển Đổi Sang Cấu Hình Lưu Trữ Đám Mây (Amazon S3)

### 1. Tại sao và khi nào cần dùng Cloud Storage (Amazon S3)?
Nếu bạn triển khai web trên các nền tảng "không trạng thái" (như Heroku, Vercel, Docker Container, AWS Elastic Beanstalk), hệ thống sẽ reset lại mã nguồn và ổ cứng mỗi khi khởi động lại máy chủ hoặc khi bạn deploy bản mới.
Điều này khiến toàn bộ file nộp của sinh viên **bị xóa sạch hoàn toàn**. Giải pháp bắt buộc là tải file thẳng lên một dịch vụ đám mây chuyên dụng như Amazon S3 thay vì lưu ở ổ cứng cục bộ.

### 2. Các bước cấu hình Amazon S3 trong Laravel

#### Bước 2.1: Cài đặt thư viện AWS SDK cho dự án
Để Laravel có thể giao tiếp và gửi file lên máy chủ của Amazon S3, bạn cần mở Terminal tại thư mục dự án và chạy lệnh cài đặt thư viện:

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

#### Bước 2.2: Lấy thông tin từ Amazon AWS
Đăng nhập vào AWS Console, tạo một Bucket trong dịch vụ S3 và tạo một tài khoản IAM User. Cấp quyền đọc ghi cho S3, bạn sẽ nhận được 4 thông tin quan trọng sau:
1. `Access Key ID` (Mật khẩu truy cập)
2. `Secret Access Key` (Khoá bí mật)
3. `Bucket Name` (Tên kho lưu trữ, ví dụ: `se08201-lms-storage`)
4. `Region` (Khu vực máy chủ, ví dụ: `ap-southeast-1` cho Singapore)

#### Bước 2.3: Chỉnh sửa file cấu hình `.env` trên Server
Mở file `.env` trên máy chủ và cập nhật thông số biến môi trường. Laravel sẽ tự động nhận diện các cấu hình này:

```env
# 1. Đổi ổ đĩa mặc định sang S3
FILESYSTEM_DISK=s3

# 2. Cấu hình tài khoản S3 của bạn
AWS_ACCESS_KEY_ID=nhập_access_key_của_bạn_vào_đây
AWS_SECRET_ACCESS_KEY=nhập_secret_key_của_bạn_vào_đây
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=ten-bucket-cua-ban
AWS_USE_PATH_STYLE_ENDPOINT=false
```

#### Bước 2.4: Điều chỉnh linh hoạt code lưu file
Trong code hiện tại (file `SubmissionService.php`), chúng ta đang cấu hình cứng việc lưu file vào ổ public:
```php
$path = $file->store('submissions', 'public');
```
Để hệ thống tự động lưu vào S3 khi deploy và lưu ở localhost khi đang test ở máy cá nhân, hãy xoá tham số `'public'`. Hệ thống sẽ tự dùng giá trị `FILESYSTEM_DISK` trong `.env`:
```php
// Tự động nhận diện lưu vào S3 hay Local
$path = $file->store('submissions');
```

#### Bước 2.5: Cập nhật đường dẫn hiển thị trên trình duyệt
Thay vì dùng hàm `asset('storage/...` như hiện tại (vì file không còn nằm ở máy chủ cục bộ nữa), bạn hãy sửa lại trên các file giao diện Blade (`show.blade.php`) bằng hàm chuyên biệt của Storage:

```html
<!-- Dành cho file công khai -->
<a href="{{ \Illuminate\Support\Facades\Storage::url($submission->student_submit) }}">
    Tải file bài nộp
</a>
```

*(Tùy chọn Nâng cao)*: Nếu bạn cài đặt Bucket S3 ở chế độ Private (Chỉ ai có tài khoản mới được tải, người lạ lấy link web cũng không tải được), Laravel hỗ trợ tạo link tải bảo mật tự động hủy sau 5 phút:
```html
<a href="{{ \Illuminate\Support\Facades\Storage::temporaryUrl($submission->student_submit, now()->addMinutes(5)) }}">
    Tải file bảo mật (Hết hạn sau 5 phút)
</a>
```
