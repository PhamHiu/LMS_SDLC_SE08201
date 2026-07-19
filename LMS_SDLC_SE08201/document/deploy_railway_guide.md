# Hướng Dẫn Deploy Dự Án Laravel 11 Lên Railway Toàn Tập

Tài liệu này hướng dẫn chi tiết các bước để đưa dự án LMS lên nền tảng Railway, từ bước khởi tạo đến bước cấu hình Database và khắc phục lỗi kinh điển.

## Bước 1: Khởi tạo Project trên Railway
1. Đăng nhập vào [Railway.app](https://railway.app/).
2. Chọn **New Project** -> **Deploy from GitHub repo**.
3. Chọn repo `PhamHiu/LMS_SDLC_SE08201` (hoặc repo của bạn).
4. Mặc định Railway sẽ tạo ra một **Web Service** chứa mã nguồn.

## Bước 2: Khởi tạo Database (MySQL)
1. Trong giao diện Project của Railway, ấn nút **+ New** (hoặc **+ Create**) ở góc trên bên phải.
2. Chọn **Database** -> Chọn **Add MySQL**.
3. Đợi vài giây để Railway khởi tạo xong máy chủ Database.

## Bước 3: Cấu hình Biến môi trường (Environment Variables)
Đây là bước quan trọng nhất, nếu sai web sẽ bị lỗi 500 hoặc không kết nối được Database.

1. Bấm vào cục **Web Service** (cục chứa code GitHub của bạn).
2. Chuyển sang tab **Variables**.
3. Chọn chế độ **Raw Editor** (để dán nhiều biến cùng lúc).
4. **Copy và Dán** toàn bộ nội dung dưới đây (Lưu ý: Không dùng ngoặc kép `""` để bọc giá trị):

```env
APP_NAME=LMS_SE08201
APP_ENV=production
APP_KEY=base64:Rua8SuvuaZ4O0JSgCbEXsO7K0+34ZW7Kd9OYCZfvIpE=
APP_DEBUG=false
APP_URL=https://[domain-cua-ban].up.railway.app
NIXPACKS_PHP_ROOT_DIR=/public
LOG_CHANNEL=stderr
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=ma_access_key
AWS_SECRET_ACCESS_KEY=ma_secret_key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=ten_bucket
GEMINI_API_KEY=ma_api_key

# Database Config (Tự động trích xuất từ cục MySQL)
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

# Chú ý quan trọng ở bước tạo DB:
SESSION_DRIVER=file
```

*Lưu ý:* `APP_URL` bắt buộc phải có `https://` ở đầu, nếu không web sẽ bị lỗi giao diện CSS/JS.

## Bước 4: Khởi tạo dữ liệu Database (Migrate & Seed)
Vì dự án dùng `SESSION_DRIVER=database`, nhưng Database hiện tại lại chưa có bảng `sessions`. Nếu để `SESSION_DRIVER=database` ngay từ đầu, web sẽ bị sập (Lỗi con gà quả trứng).

**Cách thao tác chuẩn:**
1. Đảm bảo trong tab Variables đang để `SESSION_DRIVER=file` như ở Bước 3.
2. Đợi tiến trình Deploy mới nhất chạy xong (chuyển sang màu xanh lá).
3. Bấm vào bản Deploy màu xanh lá đó -> Chọn tab **Console** ở phía trên.
4. Gõ lệnh sau để tạo bảng và tạo dữ liệu mẫu:
   ```bash
   php artisan migrate --seed --force
   ```
5. Đợi lệnh chạy xong. (Sẽ mất khoảng 1-2 phút vì Seeder tạo hàng ngàn sinh viên, lớp học).

## Bước 5: Hoàn thiện
1. Sau khi dòng Console báo chạy Migrate thành công, quay lại tab **Variables**.
2. Sửa biến `SESSION_DRIVER=file` thành `SESSION_DRIVER=database`.
3. Đợi Railway tự động Deploy lại một lần nữa.
4. **Xong!** Dự án của bạn đã sẵn sàng hoạt động hoàn hảo trên Internet.

---
## 💡 Góc chia sẻ: Các lỗi kinh điển đã được tôi (AI) khắc phục sẵn trong Source Code
Nếu bạn tự làm dự án Laravel khác đưa lên Railway, hãy nhớ các lỗi sau để tránh lặp lại sự cố:

1. **Lỗi `ext-gd`, `ext-zip` is missing:** Railway dùng Nixpacks mặc định không cài sẵn thư viện xử lý ảnh (GD) và giải nén (ZIP) cho PHP. Bạn phải tự khai báo thêm vào file `composer.json`:
   `"ext-gd": "*", "ext-zip": "*"`
   
2. **Lỗi 500 Invalid cache path:** Github không bao giờ lưu các thư mục trống. Bạn cần đảm bảo tự chèn file `.gitignore` vào bên trong các thư mục sau, nếu không lên Railway Laravel sẽ chết tức tưởi vì không có chỗ lưu Cache/View:
   - `storage/framework/views`
   - `storage/framework/sessions`
   - `storage/framework/cache/data`
   - `bootstrap/cache`
