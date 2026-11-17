# TÀI LIỆU CÔNG THỨC TOÁN HỌC & HƯỚNG DẪN HỆ THỐNG AI CẢNH BÁO NGUY CƠ NGHỈ HỌC / BỎ HỌC

**Dự án:** Smart LMS — BestCoder 2026  
**Phiên bản mô hình:** `v2.0-academic-math`  
**Ngày cập nhật:** 17/11/2026  
**Cơ sở khoa học:** Dựa trên tài liệu nghiên cứu học thuật tổng hợp từ UNESCO, OECD (2026), UNICEF, Đại học Sư phạm Kỹ thuật Vĩnh Long (VLUTE, 2024), và Khảo sát PROTEUS (2025).

---

## 1. NGUYÊN TẮC CỐT LÕI (BẮT BUỘC TUÂN THỦ)

> [!IMPORTANT]
> **4 KHÁI NIỆM KHÔNG ĐƯỢC ĐÁNH ĐỒNG:**
> 1. **Trọng số trong mô hình (Model Weights):** Tỷ lệ phân bổ điểm ưu tiên do con người thiết kế (ví dụ 40% – 35% – 25%).
> 2. **Tỷ lệ nguyên nhân (Cause Proportion):** Tỷ lệ sinh viên bỏ học tự báo cáo trong các cuộc khảo sát thực tế (ví dụ 24.35% do chọn sai ngành).
> 3. **Điểm nguy cơ (RiskScore 0–100):** Thang điểm xếp thứ tự ưu tiên can thiệp học vụ. **Điểm nguy cơ 70/100 KHÔNG ĐỒNG NGHĨA với xác suất bỏ học 70%**.
> 4. **Xác suất bỏ học (Dropout Probability):** Xác suất rời trường trong khoảng thời gian nhất định từ mô hình Machine Learning đã được hiệu chuẩn (Calibrated).
>
> **MỤC TIÊU SƯ PHẠM:** AI chỉ dùng để **nhận diện sớm và hỗ trợ kịp thời**, tuyệt đối không dùng để kỷ luật hay trừng phạt người học.

---

## 2. HỆ THỐNG CÔNG THỨC TOÁN HỌC ĐỘNG (KHÔNG FIX CỨNG)

Toàn bộ chỉ số được tính toán động từ dữ liệu thực tế trong CSDL (`attendances`, `submissions`, `grades`, `classes`, `enrollments`).

### 2.1. Công thức Tổng hợp Điểm Nguy cơ ($\text{RiskScore}$)

$$\boxed{\text{RiskScore} = 100 \times (w_a \cdot A + w_g \cdot G + w_r \cdot R)}$$

Trong đó:
* $w_a = 0{,}40$ (Trọng số Chuyên cần & Vắng học — 40%)
* $w_g = 0{,}35$ (Trọng số Kết quả học tập & Điểm số — 35%)
* $w_r = 0{,}25$ (Trọng số Trượt môn & Học lại — 25%)
* Tổng trọng số: $w_a + w_g + w_r = 1{,}0$ ($100\%$)
* $A, G, R \in [0{,}0; \, 1{,}0]$ là các chỉ số thành phần đã được chuẩn hóa.

---

### 2.2. Công thức Chuẩn hóa 3 Chỉ số Thành phần

#### 1) Chỉ số Rủi ro Chuyên cần ($A \in [0, 1]$)
Đo lường mức độ vắng học thực tế so với ngưỡng quy chế đào tạo:

$$a_{\text{overall}} = \frac{\sum \text{Số buổi vắng tất cả các môn}}{\sum \text{Tổng số buổi đã diễn ra}}, \quad a_{\max} = \max_{k} \left(\frac{\text{Vắng môn } k}{\text{Tổng buổi môn } k}\right)$$

$$a_{\text{effective}} = \max(a_{\text{overall}}, a_{\max})$$

$$A = \min\left(1{,}0, \frac{a_{\text{effective}}}{\text{threshold}}\right)$$

* $\text{threshold} = 0{,}20$ ($20\%$ — ngưỡng cấm thi môn học).
* **Quy tắc 20%:** Vắng $> 20\%$ mới tính là trượt chuyên cần (nghỉ đúng $20\%$ chưa trượt).
* Khi vắng $\ge 20\% \implies A = 1{,}0$ (rủi ro chuyên cần tối đa).
* Khi vắng $0\% \implies A = 0{,}0$.

#### 2) Chỉ số Rủi ro Điểm số GPA ($G \in [0, 1]$)
Đo lường mức độ tiếp thu kiến thức và theo kịp bài học trên thang điểm 10:

$$G = \max\left(0{,}0, \min\left(1{,}0, \frac{\text{GPA}_{\text{safe}} - \text{GPA}}{\text{GPA}_{\text{safe}} - \text{GPA}_{\text{critical}}}\right)\right)$$

Với ngưỡng chuẩn hóa: $\text{GPA}_{\text{safe}} = 7{,}0$ và $\text{GPA}_{\text{critical}} = 4{,}0$:

$$G = \max\left(0{,}0, \min\left(1{,}0, \frac{7{,}0 - \text{GPA}}{3{,}0}\right)\right)$$

* $\text{GPA} \ge 7{,}0 \implies G = 0{,}0$ (Học lực Khá/Giỏi, không có rủi ro điểm số).
* $\text{GPA} \le 4{,}0 \implies G = 1{,}0$ (Học lực Kém, rủi ro học tập tối đa).
* $4{,}0 < \text{GPA} < 7{,}0 \implies$ nội suy tuyến tính (Ví dụ: $\text{GPA} = 5{,}5 \implies G = \frac{7{,}0 - 5{,}5}{3{,}0} = 0{,}50$).
* Nếu sinh viên mới nhập học chưa có cột điểm nào: $G = 0{,}0$ (An toàn ban đầu).

#### 3) Chỉ số Rủi ro Trượt môn & Học lại ($R \in [0, 1]$)
Đo lường khó khăn học tập tích lũy qua các môn học:

$$R = \min\left(1{,}0, \frac{\text{Số môn trượt chuyên cần} + \text{Số môn trượt điểm (<5.0)} + \text{Số môn học lại}}{\max(1, \text{Tổng số môn đăng ký})}\right)$$

---

### 2.3. Phân rã Điểm số & Tỷ lệ Đóng góp (Explainable AI - Feature Attribution)

Hệ thống bóc tách chính xác phần điểm đóng góp của từng thành phần:

1. **Điểm đóng góp thành phần:**
   * Chuyên cần: $C_{\text{absent}} = 100 \times w_a \cdot A$ (Tối đa $40{,}0$ điểm)
   * Học lực GPA: $C_{\text{grade}} = 100 \times w_g \cdot G$ (Tối đa $35{,}0$ điểm)
   * Nợ môn / Học lại: $C_{\text{retake}} = 100 \times w_r \cdot R$ (Tối đa $25{,}0$ điểm)
   * $\text{RiskScore} = C_{\text{absent}} + C_{\text{grade}} + C_{\text{retake}}$

2. **Tỷ lệ phần trăm đóng góp trong tổng điểm:**
   * Nếu $\text{RiskScore} > 0$:
     $$P_{\text{absent}} = \frac{C_{\text{absent}}}{\text{RiskScore}} \times 100\%, \quad P_{\text{grade}} = \frac{C_{\text{grade}}}{\text{RiskScore}} \times 100\%, \quad P_{\text{retake}} = \frac{C_{\text{retake}}}{\text{RiskScore}} \times 100\%$$
     *(Đảm bảo $P_{\text{absent}} + P_{\text{grade}} + P_{\text{retake}} = 100{,}0\%$)*.
   * Nếu $\text{RiskScore} = 0$: $P_{\text{absent}} = P_{\text{grade}} = P_{\text{retake}} = 0\%$.

---

## 3. BỐN BẬC CẢNH BÁO VẬN HÀNH & KỊCH BẢN CAN THIỆP

| Mức điểm ($\text{RiskScore}$) | Cấp độ cảnh báo | Màu sắc / Badge | Hành động sư phạm đề xuất |
| :---: | :--- | :--- | :--- |
| **$0{,}0$ – $24{,}9$** | **SAFE (An toàn)** | Xanh lá (`#10b981`) | Duy trì theo dõi học vụ định kỳ; biểu dương tinh thần học tập tốt. |
| **$25{,}0$ – $49{,}9$** | **MEDIUM (Cần theo dõi)** | Vàng hổ phách (`#f59e0b`) | Gửi thông báo tự động qua LMS nhắc nhở chuyên cần; cảnh báo các môn tiệm cận mốc 20%. |
| **$50{,}0$ – $74{,}9$** | **HIGH (Nguy cơ cao)** | Cam (`#f97316`) | Giảng viên/Cố vấn liên hệ trực tiếp; kết nối AI Tutor phụ đạo kiến thức hổng; hỗ trợ nộp bù bài tập. |
| **$75{,}0$ – $100{,}0$** | **VERY_HIGH (Rất cao)** | Đỏ hồng (`#ef4444`) | **Can thiệp khẩn cấp (Tầng 2):** Họp trực tiếp Cố vấn – Giảng viên – Sinh viên để tìm hiểu lý do gốc (tài chính, gia đình, tâm lý); lập kế hoạch cứu vãn hoặc hướng dẫn bảo lưu hợp lệ. |

---

## 4. DANH MỤC 9 NHÓM NGUYÊN NHÂN HỌC THUẬT (THEO NGHIÊN CỨU)

Hệ thống phân loại nguyên nhân vắng học kết hợp **Ghi chú điểm danh của giảng viên** và **Chẩn đoán hành vi AI**:

| STT | Mã nguyên nhân | Tên danh mục học thuật | Tỷ lệ / Bằng chứng khoa học | Giải pháp can thiệp |
| :---: | :--- | :--- | :--- | :--- |
| 1 | `academic_struggle` | **1. Kết quả học tập** | Điểm kém, hổng kiến thức nền, nợ bài tập. | Phụ đạo 1-1, AI Tutor Mr. Biết Tuốt, bù bài tập. |
| 2 | `post_ban_drop` | **2. Chuyên cần & Ý thức** | Hiệu ứng buông xuôi sau khi vắng quá 20% cấm thi. | Hướng dẫn thủ tục học cải thiện sớm, vực dậy tinh thần. |
| 3 | `financial_work` | **3. Tài chính & Việc làm** | Phải đi làm thêm nhiều giờ, thiếu học phí. (Nghiên cứu: 31.6% đề cập). | Học bổng vượt khó, giãn hạn đóng học phí, việc làm linh hoạt. |
| 4 | `motivation_major` | **4. Động lực & Hướng nghiệp** | Chọn sai ngành, chán nản. (PROTEUS 2025: **24.35%** sinh viên nêu lý do). | Cố vấn hướng nghiệp, trắc nghiệm năng lực, đổi ngành. |
| 5 | `excused_personal` | **5. Gia đình & Biến cố** | Việc gia đình, hiếu hỉ, chăm sóc người thân. | Tạo điều kiện học bù trực tuyến, bảo lưu tạm thời. |
| 6 | `mental_health` | **6. Sức khỏe thể chất & Tâm lý** | Bệnh tật, stress thi cử, kiệt sức, trầm cảm. | Kết nối phòng y tế và tham vấn tâm lý học đường. |
| 7 | `multi_subject_overload` | **7. Nhà trường & Chương trình** | Lịch học quá dày, trùng lịch, quá tải tín chỉ. | Điều chỉnh lộ trình cá nhân, giãn tiến độ học tập. |
| 8 | `unexcused_habit` | **8. Quan hệ & Kỷ luật tự giác** | Vắng tự phát không phép, thói quen đi trễ. | Nhắc nhở quy chế, cam kết chuyên cần, gắn điểm rèn luyện. |
| 9 | `morning_weekend` | **9. Điều kiện tiếp cận** | Ca học sớm 07:15, cuối tuần, nhà xa, thời tiết. | Hỗ trợ chuyển ca học/lớp phù hợp, học liệu video lưu lại. |

---

## 5. CẤU TRÚC FILE MÃ NGUỒN VÀ HƯỚNG DẪN ĐIỀU CHỈNH

### 5.1. File cấu hình: `config/risk.php`
Nơi tập trung toàn bộ trọng số, ngưỡng và định nghĩa nguyên nhân. Khi muốn thay đổi, chỉ cần sửa cấu hình trong file này hoặc khai báo trong `.env`:

```env
# File .env (Nếu muốn thay đổi mà không sửa code)
RISK_WEIGHT_ATTENDANCE=0.40
RISK_WEIGHT_ACADEMIC=0.35
RISK_WEIGHT_RETAKE=0.25
ATTENDANCE_FAIL_RATE=0.20
RISK_GPA_SAFE=7.0
RISK_GPA_CRITICAL=4.0
RISK_PASSING_SCORE=5.0
```

### 5.2. File Core Engine: `app/Services/AI/DropoutRiskRuleEngine.php`
* Chứa hàm `evaluate(User $student): array`: Tính toán động toàn bộ các chỉ số toán học ($a, A, \text{GPA}, G, R, \text{RiskScore}, C_i, P_i$).
* Chứa hàm `analyzeSubject(User $student, ClassRoom $class): array`: Phân tích chuyên sâu từng môn học của sinh viên theo quy chế 20%.

### 5.3. File Quản lý Dịch vụ: `app/Services/AI/DropoutRiskService.php`
* Quản lý lưu trữ lịch sử đánh giá vào bảng `risk_predictions` và `risk_factors`.
* Hỗ trợ tự động fallback linh hoạt giữa Rule Engine và Machine Learning Microservice.

### 5.4. File Xử lý Biểu đồ: `app/Services/AI/RiskAnalyticsChartService.php`
* Xây dựng dữ liệu cho hệ thống Pie Chart, Line Chart và Column Chart cho Admin và Giảng viên.
* Phân loại nguyên nhân vắng học tự động theo 9 nhóm nguyên nhân học thuật.

### 5.5. File Giao diện Blade:
* `resources/views/components/risk-analytics-charts.blade.php`: Thẻ hiển thị cơ sở khoa học & biểu đồ toàn trường/lớp.
* `resources/views/components/student-risk-charts.blade.php`: Thẻ phân rã trọng số đóng góp điểm cá nhân cho từng sinh viên.

---

## 6. CÁC CÔNG CỤ & THƯ VIỆN HỖ TRỢ CHUYÊN SÂU

1. **`Rubix ML` (`rubix/ml`) & `MathPHP` (`markrogoyski/math-php`)**:
   * Thư viện Machine Learning & Thống kê cấp cao thuần PHP trong Laravel.
   * Cung cấp các mô hình Logistic Regression, Random Forest, chuẩn hóa MinMax, tính phương sai và ma trận tương quan.
2. **`scikit-learn` & `FastAPI` (Thư mục `ml_service/`)**:
   * Microservice Python có sẵn trong dự án tại `http://127.0.0.1:8001/predict`.
   * Sử dụng thuật toán hồi quy Logistic Regression để ước lượng xác suất bỏ học thực tế từ dữ liệu huấn luyện lịch sử.
3. **`SHAP` (SHapley Additive exPlanations)**:
   * Thư viện giải thích AI hàng đầu thế giới để giải thích mức độ tác động của từng đặc trưng vào quyết định của mô hình Machine Learning.
4. **`Chart.js`**:
   * Thư viện JavaScript trực quan hóa toàn bộ biểu đồ Pie, Line, Column động trên giao diện web.

---

*Tài liệu được bảo lưu trong kho mã nguồn Smart LMS để phục vụ việc bảo trì, phát triển và thuyết minh trước Hội đồng Giám khảo.*
