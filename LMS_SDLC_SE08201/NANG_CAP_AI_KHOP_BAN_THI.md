# NÂNG CẤP CODE KHỚP BẢN THUYẾT MINH DỰ THI — SMART LMS

> Tài liệu ghi lại các thay đổi mã nguồn để **code chạy đúng như bản thuyết minh đã khai**.
> Mục đích: làm tư liệu dựng slide + trả lời giám khảo khi bảo vệ.
> Ngày cập nhật: 17/11/2026.

---

## 0. Bối cảnh — vì sao cần nâng cấp

Rà soát cho thấy bản thuyết minh (bản đi thi) khai **4 điểm mà code chưa làm đúng**. Đây là các "khe hở" dễ bị giám khảo kỹ thuật hỏi sâu. Đã sửa code để mọi lời khai thành **sự thật kiểm chứng được**:

| Mã | Bản thuyết minh khai | Hiện trạng code TRƯỚC | Trạng thái SAU |
| :-- | :-- | :-- | :-- |
| G1 | Giải thích dự đoán bằng **SHAP** (Lundberg & Lee, 2017) | Không có `shap`; giải thích bằng luật viết tay | ✅ SHAP thật |
| G2 | Đặc trưng ML gồm **số bài quá hạn** | 8 đặc trưng, không có bài quá hạn | ✅ Đã thêm |
| G5 | Mức cảnh báo cuối = **max của 2 tầng** (luật ∨ ML) | ML **đè** luật | ✅ Lấy max |
| G3 | RAG dùng **Gemini embedding, vector trong MySQL** | Chỉ khớp **từ khóa** | ✅ Embedding + cosine |
| G4 | Quiz nhiều dạng: **trắc nghiệm, đúng/sai, điền khuyết, đọc code** | Chỉ sinh A/B/C/D | ✅ Đủ 4 dạng |

---

## 1. AI CẢNH BÁO NGUY CƠ BỎ HỌC (G1 + G2 + G5)

### 1.1 G1 — SHAP thật (giải thích từng dự đoán)
**Slide nói:** *"Mỗi dự đoán được giải thích bằng SHAP — cho biết yếu tố nào làm tăng/giảm nguy cơ của từng sinh viên."*

**Đã làm:**
- Thêm thư viện `shap` vào `ml_service/requirements.txt`.
- `ml_service/main.py`: dựng `shap.TreeExplainer` từ Random Forest đã nạp; tính đóng góp SHAP **theo từng sinh viên** trong API `/predict` và `/explain`, kèm nhãn tiếng Việt và chiều **INCREASE/DECREASE**.

**Bằng chứng kiểm thử (chạy thật):**
- Tính **cộng được**: `base (0.5026) + Σ SHAP (0.183) = 0.6856 = P(nguy cơ)` → đúng tuyệt đối.
- Với một hồ sơ mẫu, yếu tố SHAP mạnh nhất là **"Số bài quá hạn"** (đẩy tăng nguy cơ).

### 1.2 G2 — Đặc trưng "số bài quá hạn" (overdue_assignments)
**Slide nói:** *"...xu hướng điểm và số bài quá hạn."*

**Đã làm:**
- `ml_service/dataset.py`: thêm `overdue_assignments` vào `FEATURE_COLUMNS`, sinh dữ liệu tổng hợp có tương quan và đưa vào công thức log-odds.
- `ml_service/main.py`: thêm feature vào schema, vector đầu vào, yếu tố giải thích và công thức dự phòng.
- `app/Services/AI/DropoutRiskRuleEngine.php`: hàm `countOverdueAssignments()` — đếm bài đã quá hạn mà sinh viên **chưa nộp hoặc nộp trễ** (phòng vệ: lỗi dữ liệu trả 0), nối vào `extractFeatures()`.

**Bằng chứng:** huấn luyện lại 9 đặc trưng OK — Random Forest, **F1 = 0.83, Recall = 0.92**; đặc trưng mới tách nhóm rõ (dropout: TB **2.79** bài quá hạn so với **1.08** ở nhóm an toàn).

### 1.3 G5 — Tổ hợp 2 tầng lấy mức cao hơn
**Slide nói:** *"Mức cảnh báo cuối cùng lấy mức cao hơn của hai tầng."*

**Đã làm:** `app/Services/AI/DropoutRiskService.php` — luôn chạy Rule Engine trước, nếu ML online thì lấy **mức nghiêm trọng cao hơn** (`moreSevereLevel`) giữa luật và ML; giữ nguyên phân rã theo từng môn (minh bạch), gắn thêm giải thích ML (có kết luận SHAP) vào tóm tắt. Phương thức ghi nhận: `HYBRID_ML_RULE`.

**Talking point:** *Quy chế chuyên cần là "cổng cứng" bắt buộc; ML bổ sung tín hiệu; lấy max đảm bảo không bỏ sót (ưu tiên Recall).* 

---

## 2. AI STUDENT SUPPORT — RAG EMBEDDING (G3)

**Slide nói:** *"Gemini embedding, chỉ mục vector lưu trong MySQL."*

**Hiện trạng trước:** `BtecRagService` chỉ chấm điểm **khớp từ khóa** trên file JSON.

**Đã làm:**
- Migration `btec_kb_embeddings` — bảng lưu **vector (JSON) trong MySQL** + model `app/Models/BtecKbEmbedding.php`.
- `app/Services/AI/GeminiEmbeddingService.php` — gọi **Gemini Embedding API** + hàm cosine similarity.
- Lệnh artisan `php artisan btec:index-kb --fresh` — nhúng toàn bộ KB và lưu vector.
- `app/Services/AI/BtecRagService.php` — truy xuất **hybrid**: xếp hạng theo **cosine vector** (chính) + từ khóa/intent (phụ); **tự fallback** sang từ khóa nếu chưa đánh chỉ mục / không gọi được API. Trả thêm cờ `retrieval_method`.
- Cấu hình `GEMINI_EMBED_MODEL=gemini-embedding-001` trong `.env` / `.env.example`.

**Bằng chứng (chạy thật trên DB `lms`):**
- Nhúng **35/35 đoạn**, mỗi vector **3072 chiều**, lưu MySQL, 0 lỗi.
- Truy xuất đúng ngữ nghĩa (`VECTOR_HYBRID`):
  - *"vắng bao nhiêu % bị cấm thi"* → T08 (Chuyên cần)
  - *"học bổng/học phí"* → T06/T05
  - *"bảo lưu"* → T11 (Học lại, bảo lưu, thôi học)

**Lưu ý vận hành:** khi sửa KB → chạy lại `btec:index-kb --fresh`. (API Key không có `text-embedding-004`; đã chuyển sang `gemini-embedding-001`.)

---

## 3. AI KHẢO THÍ — QUIZ NHIỀU DẠNG (G4)

**Slide nói:** *"...dạng bài — trắc nghiệm A/B/C/D, đúng/sai, điền khuyết hoặc câu hỏi đọc code... mỗi câu có giải thích vì sao đúng/sai."*

**Hiện trạng trước:** chỉ sinh & chấm được **A/B/C/D**.

**Đã làm (end-to-end):**
- **CSDL** (migration `add_question_types_to_questions_table`): thêm `question_type`, `explanation`, `correct_text` vào `questions`; `answer_text` vào `submission_details`; cho phép option rỗng.
- **Model** `Question.php`: hằng 4 loại, `options()` lọc đáp án rỗng (đúng/sai còn 2), `isAnswerCorrect()` chấm theo loại (điền khuyết so khớp chuẩn hóa, nhiều đáp án cách nhau bằng `|`).
- **Sinh đề AI** `GeminiService.php`: nhận tham số **dạng bài + độ khó (Bloom)**; prompt + bộ chuẩn hóa sinh đủ 4 dạng, kèm **giải thích** từng câu. (Đã sửa lỗi parser cắt cụt JSON khi câu "đọc code" chứa dấu ```.)
- **Chấm điểm** `SubmissionService.php` + `SubmissionDetail.php`: chấm cả đáp án chọn (chữ cái) lẫn đáp án điền text.
- **Giao diện sinh viên**: trang làm bài hiển thị ô điền khuyết / khối code; trang kết quả hiển thị đáp án đúng + **giải thích** từng câu, có nhãn loại.
- **Giao diện giáo viên** (create + edit): bộ chọn **Loại câu hỏi**, trường đáp án điền khuyết, ô giải thích; modal AI thêm **chọn dạng bài + độ khó**.
- Validation theo từng dạng (`AssignmentRequest`, `QuizSubmissionRequest`).

**Bằng chứng kiểm thử (chạy thật):**
- Unit test chấm điểm 4 dạng + lọc đáp án + normalizer: **PASS toàn bộ**.
- **Sinh đề thật qua Gemini**: yêu cầu 4 câu đủ dạng → nhận đúng **1 mcq + 1 đúng/sai + 1 điền khuyết + 1 đọc code**, parse chuẩn.

**Cần click-test trên trình duyệt trước khi demo:** tương tác JS của form giáo viên (đổi loại, AI điền) và render trang làm bài của sinh viên (backend + compile Blade đã OK, nhưng chưa bấm thử UI thật).

---

## 4. DANH SÁCH FILE ĐÃ THAY ĐỔI / THÊM MỚI

**ML service (Python):**
- `ml_service/requirements.txt` (thêm `shap`)
- `ml_service/dataset.py`, `ml_service/main.py`, `ml_service/train.py` (feature + SHAP)

**Backend Laravel (PHP):**
- `app/Services/AI/DropoutRiskService.php` (max 2 tầng)
- `app/Services/AI/DropoutRiskRuleEngine.php` (overdue feature)
- `app/Services/AI/GeminiEmbeddingService.php` *(mới)*
- `app/Services/AI/BtecRagService.php` (RAG vector hybrid)
- `app/Services/AI/GeminiService.php` (quiz 4 dạng)
- `app/Services/Teacher/AssignmentService.php`, `app/Services/Student/SubmissionService.php`
- `app/Models/BtecKbEmbedding.php` *(mới)*, `app/Models/Question.php`, `app/Models/SubmissionDetail.php`
- `app/Http/Controllers/Teacher/AiController.php`, `app/Http/Controllers/Student/AssignmentController.php`
- `app/Http/Requests/Teacher/AssignmentRequest.php`, `app/Http/Requests/Student/QuizSubmissionRequest.php`
- `app/Console/Commands/IndexBtecKnowledgeBase.php` *(mới)*

**CSDL:**
- `database/migrations/2026_11_17_000001_create_btec_kb_embeddings_table.php` *(mới)*
- `database/migrations/2026_11_17_000002_add_question_types_to_questions_table.php` *(mới)*

**Giao diện (Blade):**
- `resources/views/student/assignments/quiz.blade.php`, `.../show.blade.php`
- `resources/views/teacher/assignments/create.blade.php`, `.../edit.blade.php`

**Cấu hình:** `.env`, `.env.example` (thêm `GEMINI_EMBED_MODEL`, khối AI/ML).

---

## 5. VIỆC CẦN LÀM TRƯỚC KHI DEMO (CHECKLIST)

- [ ] Trong venv của `ml_service`: `pip install -r requirements.txt` (cài `shap`) rồi `python train.py` lại để khớp phiên bản scikit-learn của máy demo.
- [ ] Chạy `php artisan migrate` (2 migration mới) trên máy demo.
- [ ] Chạy `php artisan btec:index-kb --fresh` để nạp vector KB (cần `GEMINI_API_KEY`).
- [ ] Bật ML service nếu muốn demo tầng ML + SHAP: đặt `ML_SERVICE_ENABLED=true`, `ML_SERVICE_URL` và chạy `uvicorn main:app`.
- [ ] Click-test: giáo viên tạo quiz 4 dạng bằng AI → sinh viên làm bài → xem kết quả + giải thích.

---

## 6. GHI CHÚ TRUNG THỰC KHI BẢO VỆ

- Mô hình ML huấn luyện trên **dữ liệu mô phỏng**; bản thuyết minh đã nêu rõ "sẽ huấn luyện lại trên dữ liệu lịch sử thật trước khi dùng" → giữ đúng cách nói này.
- SHAP giải thích **mô hình dựa vào yếu tố nào**, không phải nguyên nhân thực tế (đúng như mục 1.1 bản thuyết minh).
- RAG: triết lý "truy xuất đoạn → trả lời dựa trên đoạn + trích nguồn" (Lewis et al., 2020) nay được hiện thực bằng **embedding thật**, không chỉ từ khóa.
