# Checklist triển khai — Module Quản lý Lịch học

> **Nhánh Git:** `feature/admin-schedule-management`  
> **Quy tắc:** Mỗi task nhỏ được ✅ ngay khi hoàn thành. Cuối mỗi module → commit.

---

## Module 1: Database & Models

- [x] 1.1 — Tạo nhánh Git `feature/admin-schedule-management` từ `main`
- [x] 1.2 — Tạo migration: bảng `slots` (slot_id, slot_number, start_time, end_time, label)
- [x] 1.3 — Tạo migration: bảng `rooms` (room_id, room_name, capacity, location)
- [x] 1.4 — Migration: thêm cột `slot_id` (FK nullable) vào `sessions`
- [x] 1.5 — Migration: thêm cột `room_id` (FK nullable) vào `sessions`
- [x] 1.6 — Migration: xóa cột `room` (varchar) khỏi `sessions`
- [x] 1.7 — Migration: xóa cột `shift` (varchar) khỏi `sessions`
- [x] 1.8 — Tạo Model `Slot` với quan hệ `sessions()`
- [x] 1.9 — Tạo Model `Room` với quan hệ `sessions()`
- [x] 1.10 — Cập nhật Model `ClassSession`: đổi fillable, thêm `slot()`, `room()`
- [x] 1.11 — Tạo `SlotSeeder` — seed 6 ca cố định
- [x] 1.12 — Tạo `RoomSeeder` — seed phòng mẫu
- [x] 1.13 — Chạy `php artisan migrate:fresh --seed` thành công
- **Commit:** `[DB] Tạo bảng slots, rooms và cập nhật sessions`

---

## Module 2: Backend API

- [x] 2.1 — Thêm route `GET classes/api/subjects-by-major` vào `web.php`
- [x] 2.2 — Thêm route `GET classes/api/teachers-by-major` vào `web.php`
- [x] 2.3 — Thêm route `GET classes/api/timetable` vào `web.php`
- [x] 2.4 — Thêm route `POST classes/api/check-conflict` vào `web.php`
- [x] 2.5 — Viết `ClassController::subjectsByMajor()` — trả JSON môn theo ngành
- [x] 2.6 — Viết `ClassController::teachersByMajor()` — trả JSON GV (kèm nhóm "Chưa phân ngành")
- [x] 2.7 — Viết `ClassController::timetable()` — trả JSON lịch hiện có từ ngày X
- [x] 2.8 — Viết `ClassController::checkConflict()` — trả JSON trạng thái xung đột
- [x] 2.9 — Viết lại `ClassService::generateSessions()` — nhận `selected_slots[]` + `room_id`, sinh buổi xoay vòng, tự tính `end_date`
- [x] 2.10 — Viết `ClassService::checkConflicts()` — kiểm tra xung đột GV/phòng/lớp
- [x] 2.11 — Viết `ClassService::getExistingTimetable()` — lấy sessions hiện có
- [x] 2.12 — Cập nhật `ClassRequest` — validation `selected_slots`, `room_id`; bỏ `room`, `shift`, `end_date`
- [x] 2.13 — Cập nhật `ClassController::create()` — truyền `$rooms`, `$slots` vào view
- **Commit:** `[Backend] API cascade dropdown, timetable và check-conflict`

---

## Module 3: Frontend Form

- [x] 3.1 — Viết lại form: input Tên lớp (text, required)
- [x] 3.2 — Viết lại form: select Chuyên ngành (từ `$majors`, `id="major_select"`)
- [x] 3.3 — Viết lại form: select Môn học (rỗng ban đầu, disabled)
- [x] 3.4 — Viết lại form: select Giảng viên (rỗng ban đầu, disabled)
- [x] 3.5 — Viết lại form: input Ngày bắt đầu (date, `id="start_date"`)
- [x] 3.6 — Viết lại form: input Số buổi học (number, min=1 max=60)
- [x] 3.7 — Viết lại form: select Phòng học (từ `$rooms`)
- [x] 3.8 — Viết lại form: readonly Ngày kết thúc (tự tính)
- [x] 3.9 — Viết lại form: select Trạng thái
- [x] 3.10 — Xây dựng bảng TKB: header Thứ 2 → Chủ nhật (7 cột)
- [x] 3.11 — Xây dựng bảng TKB: body 6 hàng (Ca 1 → Ca 6, label từ `$slots`)
- [x] 3.12 — Mỗi ô TKB là `<button>` với `data-day`, `data-slot-id` + hidden inputs
- [x] 3.13 — Responsive: `overflow-x-auto` cho bảng TKB trên mobile
- **Commit:** `[UI] Giao diện form tạo lớp + lưới thời khóa biểu`

---

## Module 4: AJAX & Kiểm tra xung đột

- [x] 4.1 — JS: Chọn Chuyên ngành → fetch load Môn học → populate select
- [x] 4.2 — JS: Chọn Chuyên ngành → fetch load GV → populate select (kèm "Chưa phân ngành")
- [x] 4.3 — JS: Đổi Chuyên ngành → reset Môn + GV về rỗng
- [x] 4.4 — JS: Chọn Ngày bắt đầu → fetch load lịch hiện có → tô xám ô đã có lớp
- [x] 4.5 — JS: Click ô TKB → toggle class `selected` (xanh dương) + cập nhật hidden input
- [x] 4.6 — JS: Click ô + đã chọn GV/phòng → fetch `check-conflict` → tô màu xanh lá / đỏ
- [x] 4.7 — JS: Tooltip hiển thị chi tiết xung đột khi hover ô đỏ
- [x] 4.8 — JS: Tự tính `end_date` preview khi thay đổi ô / số buổi
- [x] 4.9 — JS: Hiển thị counter "Đã chọn X/Y buổi mỗi tuần"
- [x] 4.10 — JS: Validation client — không submit nếu 0 ô, cảnh báo ô xung đột
- [x] 4.11 — JS: Submit form → POST dữ liệu + `selected_slots` JSON → redirect / lỗi
- **Commit:** `[AJAX] Cascade dropdown, conflict check, tự tính end_date`

---

## Module 5: Edit Flow & Polish

- [ ] 5.1 — Form Edit: ẩn khu vực Bảng TKB khi `$class !== null`
- [ ] 5.2 — Form Edit: ẩn "Số buổi học" + "Ngày bắt đầu" (readonly hoặc ẩn)
- [ ] 5.3 — Form Edit: hiển thị "Ngày kết thúc" readonly từ DB
- [ ] 5.4 — Form Edit: cascade dropdown vẫn hoạt động khi đổi chuyên ngành
- [ ] 5.5 — Cập nhật `ClassController::edit()` — truyền `$rooms`, `$slots`
- [ ] 5.6 — Validation errors hiển thị đúng dưới mỗi trường
- [ ] 5.7 — AJAX failure → hiển thị thông báo lỗi
- [ ] 5.8 — Old input khôi phục đúng khi validation fail
- [ ] 5.9 — Animation transition khi toggle ô TKB
- [ ] 5.10 — Loading spinner khi AJAX đang chạy
- [ ] 5.11 — Test: tạo lớp mới end-to-end OK
- [ ] 5.12 — Test: sửa lớp (info cơ bản), xóa lớp cascade OK
- [ ] 5.13 — Test: 2 lớp cùng GV cùng ca → xung đột hiển thị đúng
- **Commit & Push:** `[Polish] Edit flow, responsive, error handling, final test`

---

## Tiến độ tổng quan

| Module | Hoàn thành | Tổng tasks |
|--------|-----------|-----------|
| 1. Database & Models | 13 / 13 | 13 |
| 2. Backend API | 13 / 13 | 13 |
| 3. Frontend Form | 13 / 13 | 13 |
| 4. AJAX & Conflict | 11 / 11 | 11 |
| 5. Edit & Polish | 0 / 13 | 13 |
| **Tổng cộng** | **0 / 63** | **63** |
