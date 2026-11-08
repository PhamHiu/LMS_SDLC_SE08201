# HƯỚNG DẪN CÀI ĐẶT VÀ VẬN HÀNH HỆ THỐNG SMART LMS AI
## CUỘC THI BESTCODER 2026 – TRƯỜNG CAO ĐẲNG ANH QUỐC BTEC FPT

Tài liệu này cung cấp hướng dẫn đầy đủ từ A-Z để cài đặt, cấu hình, nạp dữ liệu demo và vận hành toàn bộ hệ sinh thái **Smart LMS** trên môi trường cục bộ (Localhost).

---

## I. YÊU CẦU MÔI TRƯỜNG (SYSTEM PREREQUISITES)

| Thành phần | Phiên bản tối thiểu | Ghi chú |
| :--- | :--- | :--- |
| **Hệ điều hành** | Windows 10/11, macOS, Linux | Hoạt động tối ưu trên Windows (XAMPP) |
| **PHP** | PHP 8.2 trở lên | Cần kích hoạt các extension: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo` |
| **Web Server / DB** | Apache & MySQL 8.0 / MariaDB 10.4+ | Có sẵn trong bộ cài **XAMPP** |
| **Dependency Manager**| Composer 2.5+ | Quản lý gói PHP |
| **Python (Tùy chọn)** | Python 3.10+ | Dành cho Machine Learning Service (`ml_service/`) |
| **Google Gemini API**| Gemini 1.5 Pro / Flash API Key | Phục vụ AI Learning Tutor và AI Quiz Generator |

---

## II. CÁC BƯỚC CÀI ĐẶT BƯỚC TỪNG BƯỚC

### Bước 1: Khởi động XAMPP
1. Mở **XAMPP Control Panel**.
2. Nhấn nút **Start** ở cả hai dịch vụ: **Apache** và **MySQL**.
3. Đảm bảo cổng mặc định: Apache (Port 80/443), MySQL (Port 3306).

---

### Bước 2: Cấu hình biến môi trường (`.env`)
1. Di chuyển vào thư mục dự án:
   ```bash
   cd c:\xampp\htdocs\CLONE_SDLC\LMS_SDLC_SE08201\LMS_SDLC_SE08201
   ```
2. Nếu chưa có file `.env`, tạo bản sao từ `.env.example`:
   ```bash
   copy .env.example .env
   ```
3. Mở file `.env` và kiểm tra các tham số quan trọng:
   ```env
   APP_NAME="Smart LMS"
   APP_ENV=local
   APP_KEY=base64:...
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   # Cấu hình Cơ sở dữ liệu MySQL
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lms_sdlc_se08201
   DB_USERNAME=root
   DB_PASSWORD=

   # Khóa kết nối Google Gemini AI API (AI Tutor & AI Quiz Generator)
   GEMINI_API_KEY=AIzaSy...

   # Cấu hình Machine Learning Dropout Prediction Service (FastAPI)
   ML_SERVICE_ENABLED=true
   ML_SERVICE_URL=http://127.0.0.1:8000
   ```
4. Tạo Application Key (nếu chưa có):
   ```bash
   php artisan key:generate
   ```

---

### Bước 3: Cập nhật CSDL và Nạp Dữ liệu Mẫu (Seeding)
Hệ thống sử dụng các Migration chuẩn và Seeder tự động nạp toàn bộ cấu trúc đào tạo cùng 4 sinh viên mẫu tương ứng với 4 mức độ nguy cơ:

```bash
# 1. Chạy toàn bộ Migration mới nhất
php artisan migrate

# 2. Nạp dữ liệu mẫu BestCoder 2026 (Tạo môn học, lớp học, buổi học, điểm danh, bài tập và 4 sinh viên mẫu)
php artisan db:seed --class=BestCoderDemoSeeder
```

---

### Bước 4: Khởi chạy Laravel Web Server
Khởi chạy máy chủ phát triển Laravel:

```bash
php artisan serve --port=8000
```

Hệ thống sẵn sàng truy cập tại địa chỉ: **`http://localhost:8000`**

---

### Bước 5: (Tùy chọn) Khởi chạy Python Machine Learning Service
Nếu máy tính đã cài Python 3.10+, bạn có thể khởi chạy dịch vụ FastAPI để kiểm tra mô hình Scikit-Learn:

```bash
# 1. Di chuyển vào thư mục ml_service
cd ml_service

# 2. Tạo môi trường ảo và cài đặt thư viện
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt

# 3. Huấn luyện mô hình và xuất file nhị phân joblib
python train.py

# 4. Khởi chạy FastAPI Server trên cổng 8000
uvicorn main:app --reload --host 127.0.0.1 --port 8000
```
> **Lưu ý quan trọng về tính linh hoạt (Graceful Fallback):**
> Nếu Python service chưa được bật, Laravel sẽ tự động phát hiện và chuyển đổi trong vòng 0.4s sang **Rule Engine** độc lập mà không phát sinh bất kỳ lỗi nào trên giao diện người dùng!

---

## III. TÀI KHOẢN ĐĂNG NHẬP MẶC ĐỊNH

Tất cả tài khoản demo đều sử dụng mật khẩu chung: **`password`**

| STT | Vai trò | Email đăng nhập | Hồ sơ dữ liệu học tập |
| :---: | :--- | :--- | :--- |
| 1 | **Admin (Quản trị viên)** | `admin@lms.vn` | Toàn quyền quản trị hệ thống, Dashboard rủi ro toàn trường |
| 2 | **Teacher (Giảng viên)** | `teacher@lms.vn` | Giảng viên bộ môn Lập trình Java, quản lý lớp & điểm danh |
| 3 | **Sinh viên (An toàn)** | `student_safe@lms.vn` | Chuyên cần 100%, Điểm TB 8.0 $\rightarrow$ Xếp mức **SAFE** |
| 4 | **Sinh viên (Cảnh báo)** | `student_warning@lms.vn` | Vắng 2/12 buổi (16.7% tiệm cận 20%) $\rightarrow$ Xếp mức **MEDIUM** |
| 5 | **Sinh viên (Trượt vắng)** | `student_att_fail@lms.vn` | Vắng 3/12 buổi (25.0% > 20%) $\rightarrow$ Xếp mức **HIGH** |
| 6 | **Sinh viên (Nguy cơ cao)** | `student_dropout@lms.vn` | Vắng 5/12 buổi (41.7%) + nợ bài tập $\rightarrow$ Xếp mức **VERY_HIGH** |

---

## IV. HƯỚNG DẪN CHẠY BỘ KIỂM THỬ TỰ ĐỘNG (AUTOMATED TEST SUITES)

Dự án cung cấp 2 bộ kiểm thử độc lập bao quát toàn bộ logic cốt lõi:

### 1. Bộ kiểm thử nghiệp vụ BestCoder (Quy tắc 20%, AI Tutor, Grounding)
```bash
php tests/Unit/BestCoderTestSuite.php
```
*Kết quả:* **10/10 Test Cases Passed (0 Failed)**
- TC-01 đến TC-03: Kiểm tra toán tử điểm danh `> 20.0%`.
- TC-04 đến TC-06: Phân loại 4 mức nguy cơ rủi ro.
- TC-07 đến TC-09: Kiểm tra System Instruction AI Tutor, cấm giải hộ bài thi, kiểm soát quyền sở hữu phiên chat.
- TC-10: Kiểm tra tính độc lập theo từng môn học.

### 2. Bộ kiểm thử tích hợp API & Machine Learning
```bash
php tests/Unit/ApiAndMlIntegrationTest.php
```
*Kết quả:* **8/8 Test Cases Passed (0 Failed)**
- TC-API-01: Kiểm tra cấu hình và metadata Risk Engine.
- TC-API-02: Kiểm tra trích xuất vector đặc trưng 9 chiều ML.
- TC-API-03 đến TC-API-06: Kiểm tra các RESTful endpoint phục vụ Dashboard và hồ sơ sinh viên.
- TC-API-07: Kiểm tra cơ chế tự động Fallback sang Rule Engine.
- TC-API-08: Kiểm tra quy trình ghi nhận can thiệp sư phạm (Intervention Logging).

---

## V. CÁC ĐƯỜNG DẪN TÍNH NĂNG CHÍNH

- **Trang chủ / Đăng nhập:** `http://localhost:8000/login`
- **Dashboard Quản trị viên:** `http://localhost:8000/admin/dashboard`
- **Dashboard Cảnh báo Toàn trường (Admin):** `http://localhost:8000/admin/risk-analysis`
- **Dashboard Cảnh báo Lớp học (Teacher):** `http://localhost:8000/teacher/risk-dashboard`
- **Gia sư AI (Student AI Tutor):** `http://localhost:8000/student/ai-tutor`
- **Tài liệu API RESTful (Section XI):**
  - `GET http://localhost:8000/api/risk/configuration`
  - `GET http://localhost:8000/api/admin/risk/dashboard`
  - `GET http://localhost:8000/api/teacher/students/{id}/risk`

