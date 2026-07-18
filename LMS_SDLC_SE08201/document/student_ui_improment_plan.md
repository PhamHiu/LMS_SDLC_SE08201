# Kế Hoạch Cải Tiến Giao Diện & Chức Năng Student — Hệ Thống LMS

## Bối Cảnh

Dự án LMS đang cần cải tiến 3 module thuộc phân hệ **Student**: Thời Khóa Biểu, Lịch Sử Điểm Danh, và Danh Sách Bài Tập. Mục tiêu là nâng cấp trải nghiệm người dùng với bộ lọc thông minh hơn, hiển thị dạng bảng trực quan hơn, và tự động tìm kiếm real-time.

**Phạm vi tác động:** Chỉ thay đổi các file thuộc module Student, không đụng vào Auth, Admin, Teacher hay Middleware chung. Tuân thủ đúng quy tắc dự án (QUY_TAC_DU_AN.md).

---

## User Review Required

> [!IMPORTANT]
> **Về Module 3 (Bài tập):** Yêu cầu gốc không nêu tên module rõ ràng ("tiến hành"), tôi hiểu đây là trang **Danh sách Bài Tập** (`/student/assignments`). Xin xác nhận.

> [!IMPORTANT]
> **Về sắp xếp mặc định Bài tập:** Yêu cầu "mặc định hiển thị bài tập sắp đến hạn nộp". Hiện tại đang sắp theo `due_date DESC` (mới nhất trước). Tôi sẽ đổi thành **sắp theo `due_date ASC` và chỉ hiện bài chưa hết hạn trước**, bài đã hết hạn xếp sau. Xin xác nhận.

> [!WARNING]
> **Về Git workflow:** Theo quy tắc dự án, tôi sẽ tạo **1 branch duy nhất** `feature/student-ui-improvements` cho toàn bộ 3 module, nhưng code được chia thành các commit riêng biệt cho từng module. Nếu bạn muốn mỗi module 1 branch riêng, hãy cho tôi biết.

---

## Phân Tích Hiện Trạng

### Các file hiện tại liên quan

| Thành phần | File | Vai trò |
|---|---|---|
| Controller | [ScheduleController.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Http/Controllers/Student/ScheduleController.php) | Xử lý TKB + Điểm danh |
| Controller | [AssignmentController.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Http/Controllers/Student/AssignmentController.php) | Xử lý Bài tập |
| Service | [ScheduleService.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Services/Student/ScheduleService.php) | Logic TKB + Điểm danh |
| Service | [SubmissionService.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Services/Student/SubmissionService.php) | Logic Bài tập |
| Service | [StudentAccessService.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Services/Student/StudentAccessService.php) | Kiểm tra quyền truy cập |
| View | [schedule.blade.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/resources/views/student/schedule.blade.php) | Giao diện TKB |
| View | [attendance.blade.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/resources/views/student/attendance.blade.php) | Giao diện Điểm danh |
| View | [assignments/index.blade.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/resources/views/student/assignments/index.blade.php) | Giao diện DS Bài tập |
| Model | [Slot.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Models/Slot.php) | Ca học |
| Model | [ClassSession.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Models/ClassSession.php) | Buổi học |
| Model | [Attendance.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Models/Attendance.php) | Điểm danh |
| Route | [web.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/routes/web.php) L145-170 | Định tuyến Student |

---

## Proposed Changes

Kế hoạch được chia thành **3 Module độc lập**, mỗi module có các Task nhỏ rõ ràng. Các module có thể triển khai song song hoặc tuần tự mà không ảnh hưởng lẫn nhau.

---

### Module 1: Cải Tiến Trang Thời Khóa Biểu

**Mục tiêu:** Thay bộ 3 nút điều hướng tuần bằng dropdown chọn Năm + Tuần, hiển thị TKB dạng bảng lưới (hàng ngang = Thứ, hàng dọc = Ca học), tô màu theo trạng thái điểm danh.

---

#### Task 1.1 — Cập nhật Service: trả thêm dữ liệu slots, danh sách năm/tuần

##### [MODIFY] [ScheduleService.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Services/Student/ScheduleService.php)

**Thay đổi:**
- Thêm method `availableYears()`: trả danh sách các năm có buổi học (lấy từ `sessions.session_date` của lớp đã ghi danh).
- Thêm method `weeksOfYear(int $year)`: trả danh sách các tuần trong năm dạng `['week_start' => '2026-01-05', 'label' => '05/01 - 11/01']`.
- Cập nhật method `weeklyTimetable()`: ngoài dữ liệu hiện tại, eager-load thêm `attendances` (lọc theo `user_id` của sinh viên) để biết trạng thái điểm danh từng buổi.
- Thêm method `allSlots()`: trả toàn bộ danh sách ca học (bảng `slots`) để làm hàng dọc cho bảng TKB.

**Dữ liệu trả về cho bảng TKB:**
```
sessionGrid[slot_id][dayOfWeekIso] = {
    session, className, subjectName, room, attendanceStatus (P/A/L/null)
}
```

---

#### Task 1.2 — Cập nhật Controller: nhận tham số năm/tuần, truyền thêm dữ liệu

##### [MODIFY] [ScheduleController.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Http/Controllers/Student/ScheduleController.php)

**Thay đổi method `index()`:**
- Nhận thêm query params: `year` (năm), `week` thay đổi thành nhận `week_start` (ngày đầu tuần).
- Truyền thêm vào view: `$years`, `$weeksOfYear`, `$selectedYear`, `$selectedWeekStart`, `$slots`, `$sessionGrid`.
- Giữ nguyên `$weekStart`, `$weekEnd`, `$isCurrentWeek`.

---

#### Task 1.3 — Viết lại giao diện TKB dạng bảng lưới

##### [MODIFY] [schedule.blade.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/resources/views/student/schedule.blade.php)

**Thay đổi:**

1. **Thanh điều hướng (giữ nguyên khung thông tin tuần):**
   - Giữ nguyên hiển thị "Tuần học: dd/mm/YYYY – dd/mm/YYYY" và badge "Tuần này".
   - **Xóa** 3 nút "Tuần trước / Tuần này / Tuần sau".
   - **Thêm** dropdown "Chọn năm" + dropdown "Chọn tuần" (format: `dd/mm - dd/mm`) + nút "Làm mới" (reset về tuần hiện tại).
   - Khi thay đổi dropdown → tự động submit form (JS `onchange`).

2. **Bảng thời khóa biểu:**
   - **Header hàng ngang:** 7 cột = Thứ 2 → Chủ nhật, mỗi cột hiển thị "Thứ X" + ngày/tháng bên dưới (vd: `Thứ 2` / `19/07`).
   - **Header hàng dọc:** Mỗi hàng = 1 slot (ca học), hiển thị label + khung thời gian (vd: `Ca 1` / `07:30 – 09:00`).
   - **Nội dung ô:** Nếu có buổi học → hiện tên môn + tên lớp + phòng. Nếu không → để trống.
   - **Tô màu:**
     - 🟢 Xanh lá (`bg-emerald-100 border-emerald-400`) → Đã điểm danh (status = `P` hoặc `L`).
     - 🔴 Đỏ (`bg-rose-100 border-rose-400`) → Vắng mặt (status = `A`).
     - 🟣 Tím (`bg-purple-100 border-purple-400 ring-2 ring-purple-300`) → Hôm nay có lịch học (chưa điểm danh hoặc chưa đến giờ).
     - Ô trống không tô màu.

---

### Module 2: Cải Tiến Trang Lịch Sử Điểm Danh

**Mục tiêu:** Thêm bộ lọc theo lớp, thanh tìm kiếm real-time, cột "Tuần" trong bảng kết quả, mặc định hiển thị lớp đầu tiên.

---

#### Task 2.1 — Cập nhật Service: hỗ trợ lọc theo lớp, tìm kiếm

##### [MODIFY] [ScheduleService.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Services/Student/ScheduleService.php)

**Thay đổi method `attendanceHistory()`:**
- Thêm tham số `?int $classId = null`, `?string $search = null`.
- Nếu `$classId` != null → lọc theo `session.class_id`.
- Nếu `$search` != null → tìm theo ngày (format `dd/mm`), tên lớp (`class_name`), trạng thái (`Có mặt`, `Vắng`, `Đi muộn` → map sang `P/A/L`).
- Thêm method `enrolledClassesForFilter(User $student)`: trả danh sách lớp đã ghi danh (gồm `class_id`, `class_name`, `subject_name`) để render dropdown.
- Thêm logic chọn `defaultClassId`: lấy lớp đầu tiên có buổi học sắp tới (hoặc lớp đầu tiên nếu không có).

---

#### Task 2.2 — Cập nhật Controller: nhận params lọc, truyền dữ liệu mới

##### [MODIFY] [ScheduleController.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Http/Controllers/Student/ScheduleController.php)

**Thay đổi method `attendance()`:**
- Nhận thêm query params: `class_id`, `search`.
- Truyền thêm vào view: `$enrolledClasses`, `$selectedClassId`, `$searchQuery`.

---

#### Task 2.3 — Cập nhật giao diện Điểm danh

##### [MODIFY] [attendance.blade.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/resources/views/student/attendance.blade.php)

**Thay đổi:**

1. **Thêm thanh bộ lọc ở đầu trang:**
   - Dropdown "Chọn lớp" → liệt kê các lớp đã ghi danh (mặc định chọn lớp đầu tiên có lịch học).
   - Thanh tìm kiếm text input → auto-search khi nhập (debounce ~400ms, JS `fetch` hoặc submit form với query string).
   - Placeholder: "Tìm theo ngày, lớp, trạng thái..."

2. **Thêm cột "Tuần" vào bảng:**
   - Thêm cột đầu tiên hiển thị "Tuần dd/mm - dd/mm" (lấy từ `week_start` và `week_end`).
   - Sử dụng `rowspan` để gom các dòng cùng tuần lại.
   - Giữ nguyên các cột hiện tại: Ngày, Lớp/Buổi học, Trạng thái, Ghi chú.

3. **Giữ nguyên:** Thẻ thống kê (✅ Có mặt, 🕐 Muộn, ❌ Vắng) cho mỗi tuần.

---

### Module 3: Cải Tiến Trang Danh Sách Bài Tập

**Mục tiêu:** Thêm bộ lọc theo lớp, thanh tìm kiếm real-time, giữ nguyên tabs loại bài tập và card hiển thị, mặc định hiện bài sắp đến hạn.

---

#### Task 3.1 — Cập nhật Service: hỗ trợ lọc theo lớp, tìm kiếm, sắp xếp mới

##### [MODIFY] [SubmissionService.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Services/Student/SubmissionService.php)

**Thay đổi method `assignmentsFor()`:**
- Thêm tham số `?int $classId = null`, `?string $search = null`.
- Nếu `$classId` != null → lọc theo `class_id`.
- Nếu `$search` != null → tìm theo `title` (tên bài tập), `class_name`.
- **Thay đổi sắp xếp mặc định:** Ưu tiên bài chưa hết hạn lên trước (sắp `due_date ASC` cho bài chưa hết hạn, rồi `due_date DESC` cho bài đã hết hạn):
  ```php
  ->orderByRaw("CASE WHEN due_date >= NOW() THEN 0 ELSE 1 END")
  ->orderBy('due_date', 'asc')
  ```

---

#### Task 3.2 — Cập nhật Controller: nhận params lọc

##### [MODIFY] [AssignmentController.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/app/Http/Controllers/Student/AssignmentController.php)

**Thay đổi method `index()`:**
- Nhận thêm query params: `class_id`, `search`.
- Truyền thêm vào view: `$enrolledClasses`, `$selectedClassId`, `$searchQuery`.

---

#### Task 3.3 — Cập nhật giao diện Bài tập

##### [MODIFY] [assignments/index.blade.php](file:///c:/xampp/htdocs/SDLC_LMS_SE08201/LMS_SDLC_SE08201/resources/views/student/assignments/index.blade.php)

**Thay đổi:**

1. **Thêm thanh bộ lọc ở đầu trang** (trước các tab Tất cả / Tự luận / Trắc nghiệm):
   - Dropdown "Chọn lớp" → liệt kê các lớp đã ghi danh.
   - Thanh tìm kiếm text input → auto-search khi nhập (debounce ~400ms).
   - Placeholder: "Tìm theo tên bài, lớp..."

2. **Giữ nguyên:** 3 tab loại bài tập (Tất cả / Tự luận / Trắc nghiệm), card hiển thị bài tập, pagination.

---

## Tóm Tắt Files Thay Đổi

| # | File | Loại thay đổi | Module |
|---|---|---|---|
| 1 | `app/Services/Student/ScheduleService.php` | MODIFY — thêm 3 method mới, cập nhật 2 method | M1, M2 |
| 2 | `app/Http/Controllers/Student/ScheduleController.php` | MODIFY — cập nhật 2 method | M1, M2 |
| 3 | `resources/views/student/schedule.blade.php` | MODIFY — viết lại giao diện bảng TKB | M1 |
| 4 | `resources/views/student/attendance.blade.php` | MODIFY — thêm bộ lọc, cột tuần | M2 |
| 5 | `app/Services/Student/SubmissionService.php` | MODIFY — cập nhật method `assignmentsFor()` | M3 |
| 6 | `app/Http/Controllers/Student/AssignmentController.php` | MODIFY — cập nhật method `index()` | M3 |
| 7 | `resources/views/student/assignments/index.blade.php` | MODIFY — thêm bộ lọc + tìm kiếm | M3 |

> [!NOTE]
> **Không tạo file mới, không thêm migration, không thay đổi route.** Toàn bộ thay đổi nằm trong 7 file hiện có, giữ nguyên cấu trúc dự án, dễ merge và không gây conflict.

---

## Thứ Tự Triển Khai Khuyến Nghị

```
Module 1 (TKB)     ──── Task 1.1 → Task 1.2 → Task 1.3
                                                         ↘
Module 2 (Điểm danh) ── Task 2.1 → Task 2.2 → Task 2.3   → Test toàn bộ → Commit & Push
                                                         ↗
Module 3 (Bài tập)  ── Task 3.1 → Task 3.2 → Task 3.3
```

Mỗi module hoàn toàn độc lập, có thể thực hiện theo bất kỳ thứ tự nào. Trong mỗi module, các task phải thực hiện tuần tự (Service → Controller → View).

---

## Verification Plan

### Automated Tests
```bash
php artisan route:list --path=student    # Kiểm tra route không bị lỗi
php artisan view:cache                   # Kiểm tra Blade syntax
```

### Manual Verification
- **Module 1:** Truy cập `/student/schedule`, thay đổi dropdown Năm/Tuần → bảng TKB cập nhật đúng. Kiểm tra tô màu đúng trạng thái điểm danh. Nút "Làm mới" quay về tuần hiện tại.
- **Module 2:** Truy cập `/student/attendance`, thay đổi dropdown lớp → chỉ hiện điểm danh của lớp đó. Nhập text vào ô tìm kiếm → kết quả lọc real-time. Kiểm tra cột "Tuần" hiển thị đúng.
- **Module 3:** Truy cập `/student/assignments`, thay đổi dropdown lớp → chỉ hiện bài tập lớp đó. Nhập tìm kiếm → lọc theo tên bài. Kiểm tra mặc định bài sắp hạn hiện trước.
