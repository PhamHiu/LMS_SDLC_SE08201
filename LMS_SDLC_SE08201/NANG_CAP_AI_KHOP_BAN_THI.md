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

## 2. HYBRID RAG & VIETNAMESE EMBEDDING (`AITeamVN/Vietnamese_Embedding_v2`) CHO CẢ 2 AI (G3+)

**Yêu cầu kỹ thuật:** Tích hợp mô hình nhúng tiếng Việt chuyên biệt **`AITeamVN/Vietnamese_Embedding_v2`** (HuggingFace) và áp dụng **Hybrid RAG** cho cả **BTEC AI Student Support** lẫn **Mr. Biết Tuốt (AI Learning Tutor)**.

### 2.1 Động cơ Embedding 3 tầng (`app/Services/AI/GeminiEmbeddingService.php` & `ml_service/main.py`)
1. **Tầng 1 — Local Python FastAPI (`POST /embed` tại `ml_service/main.py`)**:
   - Dùng `sentence-transformers` nạp model `AITeamVN/Vietnamese_Embedding_v2` (vector 1024 chiều, chuẩn hóa L2 `normalize_embeddings=True`).
   - Lazy-loading: chỉ tải model vào RAM ở lần gọi `/embed` đầu tiên, không làm chậm khởi động FastAPI.
2. **Tầng 2 — HuggingFace Inference API**:
   - Gọi REST endpoint `https://router.huggingface.co/hf-inference/models/AITeamVN/Vietnamese_Embedding_v2/pipeline/feature-extraction` qua biến môi trường `HF_API_TOKEN`.
3. **Tầng 3 — Google Gemini Embedding (`gemini-embedding-001`)**:
   - Fallback tự động khi Local ML Service chưa bật hoặc chưa cấu hình `HF_API_TOKEN`.

### 2.2 Hybrid RAG trên AI 1 — BTEC AI Student Support (`app/Services/AI/BtecRagService.php`)
- Kết hợp **Dense Vector Cosine Similarity** (từ bảng `btec_kb_embeddings` trong MySQL) với **Lexical Keyword Matching** và **Intent Boosting**:
  $$\text{CombinedScore} = 100 \times \max(0, \text{CosineSim}) + 0.5 \times \text{LexicalScore}$$
- Tự động fallback về `LEXICAL` nếu chưa đánh chỉ mục.
- Lệnh đánh chỉ mục: `php artisan btec:index-kb --fresh`.

### 2.3 Hybrid RAG & Adaptive Learning trên AI 2 — Mr. Biết Tuốt / AI Learning Tutor (`app/Services/AI/AiTutorService.php`)
- **Hybrid RAG (`retrieveHybridContext`)**: Tính điểm Hybrid (`100 * CosineSim + 0.5 * LexicalScore`) giữa câu hỏi của sinh viên với toàn bộ học liệu (`Material`) của môn học bằng `AITeamVN/Vietnamese_Embedding_v2`, ghép thêm Top 2 đoạn tri thức học thuật từ `BtecRagService`.
- **Phân cấp học lực thích ứng theo đúng môn học (`resolveStudentAcademicProfile`)**:
  - Công thức điểm TB có trọng số (quy thang 10): **Tự luận (Essay) hệ số ×3**, **Trắc nghiệm (Quiz) hệ số ×1**:
    $$\text{WeightedAvg} = \frac{3 \times \sum s_{\text{essay}} + 1 \times \sum s_{\text{quiz}}}{3 \times N_{\text{essay}} + 1 \times N_{\text{quiz}}}$$
  - Xử lý 3 tầng (chống Cold-Start): Điểm môn đang chat → Điểm TB các môn trước → `STANDARD_NEW` (Tiêu chuẩn - Sinh viên mới).
  - Ngưỡng phân cấp: `< 5.0` (**Cơ bản** — Pass), `5.0–7.9` (**Khá** — Merit), `≥ 8.0` (**Nâng cao** — Distinction).
  - Tự động trích tối đa **3 câu Quiz làm sai gần nhất của môn đó** + nhận xét chấm bài mới nhất của giảng viên đưa vào Prompt.
- **4 Mode đồng hành khác biệt rõ rệt (`getSystemInstruction`)**:
  - `EXPLAIN`: *Ví dụ đời sống → Bản chất kỹ thuật → Hỏi ngược 1 câu kiểm tra*.
  - `GUIDANCE`: *Khóa đáp án/full code, chỉ gợi ý Bước 1, chờ học trò phản hồi mới mở Bước 2*.
  - `REVIEW`: *Khung chuẩn 4 mục: Vị trí lỗi → Nguyên nhân → Gợi ý sửa → Đánh giá mức BTEC (P/M/D)*.
  - `PRACTICE`: *Ra đúng 1 câu hỏi mỗi lượt, chờ trả lời rồi mới chấm Đúng/Sai & tăng độ khó*.
- **Thang gợi ý 3 nấc (Progressive Hint Ladder)** theo số lượt hỏi trong phiên + **Ngữ cảnh hội thoại 6 tin nhắn gần nhất** (đã fix loại trừ `message_id` vừa tạo để không lặp câu hỏi cuối) + **Gemini Fallback** (`callGeminiFallback`) nếu Vilao API lỗi/timeout.
- **Giao diện (`resources/views/student/ai_tutor/index.blade.php`)**: Hiển thị Badge học lực thích ứng (kèm tooltip chi tiết điểm Tự luận ×3 & Quiz ×1) trên đầu khung chat và cập nhật mô tả rõ ràng cho 4 thẻ Chế độ đồng hành.
- **Bằng chứng kiểm thử (chạy thật trên DB `lms`)**:
  - SV `Phạm Quốc Thắng` môn *Lập trình Web với Laravel*: 4 bài Tự luận (TB `8.43`) + 2 bài Quiz (TB `8.50`) → Điểm trọng số `8.44` (`ADVANCED`), trích đúng `2` câu Quiz làm sai.
  - SV mới (0 bài nộp) → `STANDARD_NEW` (không lỗi Cold-Start).
- Đã bổ sung đầy đủ chú thích kiến trúc (Comment Phần 1 → Phần 5 của Pipeline RAG) và chú thích chi tiết vai trò từng thư viện/model import trực tiếp trong mã nguồn `ml_service/requirements.txt`, `ml_service/main.py`, `GeminiEmbeddingService.php`, `BtecRagService.php`, `BtecSupportAiService.php`, và `AiTutorService.php`.

### 2.4 Tóm tắt Tài liệu Học, Tóm tắt theo Môn & Nén Ngữ cảnh Hội thoại (`VietAI/vit5-base-vietnews-summarization` + `LLMLingua-2`)
- **Python FastAPI (`ml_service/main.py` & `ml_service/requirements.txt`)**:
  - Thêm bộ bóc tách tài liệu `PyMuPDF` (PDF), `python-docx` (Word), `python-pptx` (Slide PowerPoint).
  - Endpoint `POST /summarize`: Chạy model tiếng Việt **`VietAI/vit5-base-vietnews-summarization`** (HuggingFace `transformers` + `sentencepiece`).
  - Endpoint `POST /compress-context`: Chạy **`Microsoft LLMLingua-2`** (`microsoft/llmlingua-2-xlm-roberta-large-meetingbank`) để nén ngữ cảnh hội thoại dài (tự động fallback sang ViT5 nếu chưa cài `llmlingua`).
- **Backend Laravel (`app/Services/AI/VietnameseSummarizerService.php` — mới)**:
  - `compressConversationContext()`: Gắn trực tiếp vào `AiTutorService::buildVilaoMessages()`. Khi cuộc trò chuyện dài hơn 6 tin nhắn, toàn bộ tin cũ hơn 6 tin gần nhất được nén/tóm tắt và đưa vào System Prompt (`🧠 TÓM TẮT TOÀN BỘ NGỮ CẢNH HỘI THOẠI TRƯỚC ĐÓ`) giúp Mr. Biết Tuốt nhớ xuyên suốt cả phiên học dài.
  - `summarizeMaterial()` & `summarizeSubjectMaterials()`: Tóm tắt 1 tài liệu riêng lẻ hoặc tổng hợp toàn bộ tài liệu của 1 môn học (Waterfall 3 tầng: Local FastAPI ViT5 → HuggingFace ViT5 API → LLM Fallback + Cache 24h).
- **Routes & Giao diện Học liệu (`routes/web.php`, `Student\MaterialController`, `resources/views/student/materials/index.blade.php`)**:
  - `POST /student/materials/{material}/summarize` (nút **`Tóm tắt AI`** trên từng học liệu).
  - `POST /student/materials/subject/{subject}/summarize` (nút **`Tóm tắt toàn bộ môn này (ViT5)`** trên thanh lọc khi chọn môn).

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

## 4. CÁC NÂNG CẤP NGHIỆP VỤ & GIAO DIỆN ĐÀO TẠO (CẬP NHẬT 21/11/2026)

1. **Điểm danh thời gian thực & Phân quyền**:
   - Xác định thời gian thực toàn hệ thống (`RealtimeScheduleService.php`, `ClassSession.php`).
   - Mở cửa sổ điểm danh trước giờ vào ca 30 phút và đóng sau khi vào ca 15 phút.
   - Phân màu buổi đã học/đã điểm danh (xanh đậm) với buổi chưa học; bộ lọc Có mặt / Muộn / Vắng.
   - Khi cập nhật lại: Giáo viên chỉ được chuyển từ Có mặt sang Muộn hoặc Hủy điểm danh (Vắng), quyền khôi phục Có mặt dành riêng cho Admin.
2. **Thống kê Chấm bài, Nhắc nộp & Gia hạn hạn nộp**:
   - Thống kê tỷ lệ nộp bài (%), danh sách Đã nộp / Chưa nộp, nhắc nhở 1-click toàn lớp hoặc từng sinh viên (`GradingController.php`, `GradingService.php`).
   - Cho phép giáo viên sửa/gia hạn `due_date` trực tiếp tại màn hình chấm bài, danh sách bài tập, chi tiết bài tập và trang sửa bài (`AssignmentController@extendDeadline`), tự động gửi thông báo chuông cho sinh viên.
3. **Hệ thống Thông báo Học sinh & Điều hướng đích**:
   - Bảng `notification_reads` (`NotificationRead.php`, migration `2026_11_17_000003_upgrade_notifications_table.php`) lưu trạng thái đọc của từng sinh viên.
   - Phân biệt màu: **Chưa đọc = Nền trắng** (`bg-white`), **Đã đọc = Nền xanh blue nhạt** (`bg-sky-50/70`).
   - Bấm vào thông báo (`Student\NotificationController@read`) tự động đánh dấu đã đọc và chuyển thẳng tới bài tập hoặc lớp học tương ứng.
4. **Trình xem bài chấm & Ghi chú của giáo viên ở phía Học sinh**:
   - Đổi nhãn thành **"Xem chi tiết file bài chấm và ghi chú của giáo viên"** (`resources/views/student/assignments/show.blade.php`).
   - Tích hợp Modal Viewer độ rộng chuẩn trang A4 (`860px`) kèm phóng to/thu nhỏ và 3 tab: *Bài đã chấm & ghi chú*, *Bản gốc đã nộp*, *File sửa khác từ giáo viên* (`other_file_path`) kèm nút xem & tải về.
5. **Avatar Mascot BTEC AI Student Support**:
   - Cập nhật ảnh linh vật BTEC (`public/images/btec_support_avatar.jpg`) tại toàn bộ 6 vị trí hiển thị trong `resources/views/components/btec-ai-bubble.blade.php`.
6. **Trang Quản trị Kho tri thức RAG cho Admin (`/admin/rag-kb`)**:
   - Controller `app/Http/Controllers/Admin/RagKnowledgeController.php` + Giao diện `resources/views/admin/rag/index.blade.php` + Menu sidebar `Dữ liệu RAG (AI Support)`.
   - Cho phép Admin xem toàn bộ 35 chunks quy chế, lọc/tìm kiếm, **Thêm mới / Sửa / Xóa** từng đoạn tri thức (tự động cập nhật `kb_chunks.jsonl` + nhúng lại Vector vào `btec_kb_embeddings` + xóa cache FAQ), **Import hàng loạt từ file `.txt / .md / .jsonl`**, và bấm nút **Đồng bộ & Nhúng lại toàn bộ Vector** trực tiếp trên web.
7. **Thay thế mô-đun Cảnh báo nguy cơ AI + Biểu đồ → Thống kê số buổi nghỉ học của học sinh theo môn (Quy chế > 20% = Trượt môn)**:
   - Xóa bỏ toàn bộ biểu đồ (`resources/views/components/risk-analytics-charts.blade.php`, `resources/views/components/student-risk-charts.blade.php`) và phần hiển thị điểm rủi ro/ML.
   - Tạo mới `app/Services/Attendance/AbsenceStatisticsService.php` thống kê chính xác số buổi có mặt / đi muộn / nghỉ học, tỷ lệ nghỉ (%) và số buổi tối đa được phép nghỉ (`floor(total_sessions * 0.2)`) theo từng **Học sinh × Môn học**.
   - Viết lại gọn gàng 4 màn hình `admin/risk/index.blade.php`, `admin/risk/show.blade.php`, `teacher/risk/index.blade.php`, `teacher/risk/show.blade.php` và 2 controller `Admin/RiskManagementController.php`, `Teacher/RiskDashboardController.php` chỉ tập trung vào bảng thống kê số buổi nghỉ theo môn với 3 mức chuẩn: **Đạt chuyên cần (< 15%)**, **Sắp trượt môn (15%–20%)**, **Trượt môn (> 20%)**.
   - Cập nhật `AttendanceService.php` và `teacher/attendance/edit.blade.php` tính trạng thái dưới tên học sinh trực tiếp từ tỷ lệ nghỉ của môn đó (không còn gọi AI Risk khi điểm danh).
8. **Lồng ghép Sơ đồ & Hình ảnh UML (Mermaid.js) trong câu trả lời AI**:
   - Tạo runtime dùng chung `resources/views/components/mermaid-diagram-runtime.blade.php` (nhúng trong `layouts/app.blade.php`) hỗ trợ dựng trực tiếp SVG cho **Flowchart, UML Sequence Diagram, UML Class Diagram, ER Diagram, UML State Diagram** kèm nút **Phóng to (Lightbox)**, **Tải SVG**, **Xem mã nguồn** và fallback an toàn khi lỗi cú pháp.
   - Cập nhật System Prompt và bảo vệ khối mã trong `cleanAiReply()` cho cả **Mr. Biết Tuốt** (`AiTutorService.php`, `AiTutorViewHelper.php`, `student/ai_tutor/index.blade.php`) và **BTEC AI Student Support** (`BtecSupportAiService.php`, `components/btec-ai-bubble.blade.php`) để AI chỉ lồng ghép sơ đồ UML/quy trình khi thật sự hợp lý.
   - Bổ sung thẻ hiển thị mã nguồn chuẩn **VS Code Dark+** (`#1e1e1e`, thanh tiêu đề `#252526` kèm tên ngôn ngữ và nút **Sao chép**, font `Cascadia Code` / `JetBrains Mono` / `Consolas`, tô màu cú pháp bằng **Highlight.js `vs2015`**) cho cả code block và inline `<code>` trong câu trả lời AI.
9. **Tích hợp hệ thống 4 API Key chuyên biệt theo vai trò (`AiTutorService.php` + `AiImageGeneratorService.php`)**:
   - `DEEPSEEK_KEY_MAIN` (`deepseek-v4.1-flash`): AI chính xử lý hỏi bài, giải thích, hướng dẫn, dịch thuật, đặt tên hội thoại.
   - `DEEPSEEK_KEY_CONTENT` (`deepseek-v4.1-flash`): AI chuyên trách xử lý câu hỏi kèm ảnh/PDF/file code, chế độ Luyện tập (`PRACTICE`) và Soi lỗi (`REVIEW`).
   - `OPENAI_API_KEY` (`vgpt/gpt-6.1-sol`): AI dự phòng tự động khi DeepSeek lỗi/timeout (tiếp tục fallback sang `GEMINI_API_KEY` nếu cần).
   - `IMAGE_API_KEY` (`gemini-3.1-flash-lite-image`): Tạo ảnh minh họa học tập qua `AiImageGeneratorService` (lưu tại `storage/app/public/ai_tutor_images/`) và hiển thị trực tiếp ảnh Markdown trong khung chat.
10. **Chuẩn hóa vai trò Trợ giảng AI đa ngành bậc Đại học / Cao đẳng**:
   - Cập nhật System Prompt (`AiTutorService.php`) và giao diện chào/gợi ý (`student/ai_tutor/index.blade.php`) để Mr. Biết Tuốt nhận mình là **Trợ giảng AI** hỗ trợ sinh viên đa ngành (xưng *mình / trợ giảng*, gọi *bạn*), bỏ hoàn toàn thiên hướng cố định theo ngành IT/lập trình.
11. **Bổ sung Tài liệu học thật (.docx Word & .pdf) cho toàn bộ 10 môn học & Nâng cấp RAG đọc trực tiếp nội dung file**:
   - Tạo seeder `database/seeders/SubjectMaterialsSeeder.php` sinh 20 file tài liệu thật (mỗi môn gồm 1 file giáo trình Word `.docx` chuẩn OpenXML đầy đủ tiếng Việt UTF-8 và 1 file bài giảng/thực hành `.pdf` chuẩn PDF 1.4) tại `storage/app/public/materials/` và đồng bộ vào bảng `materials` (thay thế các đường dẫn demo cũ không tồn tại).
   - Bổ sung `VietnameseSummarizerService::extractMaterialPlainText()` (trích xuất trực tiếp nội dung thật từ `.docx` qua `ZipArchive`, `.pdf` qua PDF stream parser, `.txt/.md`, có cache 24h) và tích hợp vào `AiTutorService::retrieveHybridContext()` để tự động đọc nội dung file của đúng môn đang chọn, tóm gọn tài liệu dài trước khi đưa vào System Prompt, đồng thời chỉ ghép thêm quy chế BTEC khi câu hỏi liên quan học vụ.
12. **Nâng cấp Rolling Summary Memory (Nén toàn bộ lịch sử trò chuyện, bỏ hoàn toàn mốc 6 tin thô)**:
   - Migration `2026_11_22_000001_add_context_summary_to_ai_conversations_table.php` thêm 2 cột `context_summary` và `summarized_up_to_message_id` vào bảng `ai_conversations` (`AiConversation.php`).
   - Triển khai `VietnameseSummarizerService::updateRollingConversationSummary()` cộng dồn bản trí nhớ cuốn chiếu 4 ý cố định (*Chủ đề đang học · Kiến thức đã chốt · Điểm còn vướng · Ý chính lượt gần nhất*) sau mỗi lượt hỏi–đáp.
   - `AiTutorService::buildVilaoMessages()` bỏ hoàn toàn vòng lặp gửi 6 tin nhắn thô, chỉ gửi `[System Prompt (kèm Rolling Summary), Tin nhắn hiện tại]` giúp tiết kiệm token tối đa và giữ mạch hội thoại không giới hạn độ dài.
13. **Thông báo mất kết nối mạng / Server toàn hệ thống**:
   - Tạo component `resources/views/components/network-status-banner.blade.php` (nhúng vào cả `layouts/app.blade.php` và `layouts/guest.blade.php`): tự động bắt sự kiện `offline`/`online` của trình duyệt và lỗi mạng trên mọi `fetch()`, hiển thị banner **"Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!"** kèm nút **Thử lại** và tự báo xanh khi có mạng trở lại.
   - Đồng bộ thông báo lỗi mất mạng trong khung chat `student/ai_tutor/index.blade.php` và `components/btec-ai-bubble.blade.php`.
14. **Nâng cấp AI Support chuẩn RBAC-before-read, Đa nghiệp vụ Học vụ & Quản trị Phiên bản Quy chế**:
   - **Phân quyền trước khi đọc dữ liệu**: Tạo `SupportAuthorizationService.php` kiểm soát quyền chặt chẽ theo danh tính xác thực Laravel (`STUDENT`: chỉ lớp ghi danh & dữ liệu cá nhân; `TEACHER`: chỉ lớp phân công `classes.user_id`, hẹp hơn bypass demo của `TeacherClassService`; `ADMIN`: phạm vi quản trị), kiểm tra lại quyền lịch sử khi mở lại hội thoại (`init`), và chặn trước byte đầu tiên trên `/stream`.
   - **Bộ điều phối học vụ nội bộ (`SupportAcademicOrchestrator.php`)**: Chạy trước mọi RAG/LLM bên ngoài cho các nghiệp vụ Môn học, Thời khóa biểu, Thay đổi lịch, Điểm số (`0` vs chưa chấm), Chuyên cần & Giả định nghỉ thêm (tỷ lệ chính xác trước khi làm tròn, không ghi điểm danh, hỗ trợ tiếng Việt không dấu, đa ý định, đổi môn & hỏi làm rõ).
   - **Cô lập dữ liệu riêng tư**: Đánh dấu `is_private` + `private_refs` trên `btec_support_messages`, lọc bỏ tin riêng tư khỏi ngữ cảnh gửi ra LLM ngoài, không lưu kết quả riêng tư vào cache FAQ dùng chung.
   - **Quản trị Phiên bản Quy chế (`PolicyRuleEngine.php`, `PolicyRuleController.php`, `admin/rag/policies.blade.php`)**: Tạo bảng `policy_rules` & `policy_rule_audits`, hỗ trợ quy trình `DRAFT → Mô phỏng tác động (4/20 với > vs >=) → APPROVED → PUBLISHED → ARCHIVED`, phát hiện xung đột 2 nguồn cùng ưu tiên và đánh dấu phép tính chưa hỗ trợ. Dùng chung logic tính ngưỡng chuyên cần với `AbsenceStatisticsService`.
   - **Kiểm thử nghiệm thu**: Tạo `tests/Feature/RunAiSupportAcceptanceTests.php` (và `tests/Feature/AiSupportFeatureTest.php`) — kết quả thực chạy **23/23 PASS**.
   - **Tối ưu hiển thị bong bóng AI Support (`btec-ai-bubble.blade.php`)**: Tăng cỡ chữ hội thoại từ `text-xs` (12px) lên `text-sm` (14px) dễ đọc hơn và tự động tô đậm nổi bật các cụm từ lưu ý (`Lưu ý:`, `Chú ý:`, `Quan trọng:`, `Cảnh báo:`).
15. **Công bố điểm bài Tự luận của lớp & Gửi thông báo cho học sinh**:
   - Migration `2026_11_22_000004_add_grades_published_at_to_assignments_table.php` thêm cột `grades_published_at` vào `assignments` (`Assignment::isEssay()`, `Assignment::isGradesPublished()`).
   - Thêm nút **"Công bố điểm của lớp"** (và trạng thái **"✓ Đã công bố điểm"**) tại trang chấm bài `teacher/grading/index.blade.php` + endpoint `POST teacher/assignments/{assignment}/publish-grades` (`GradingController@publishGrades`) tự động công bố điểm và tạo thông báo chuông (`Notification`) gắn link chi tiết bài tập cho cả lớp.
   - Đồng bộ ẩn điểm tự luận chưa công bố tại `student/grades.blade.php`, `student/assignments/show.blade.php`, `GradeService.php` và `SupportAcademicOrchestrator.php` cho tới khi giảng viên bấm công bố.

---

## 5. DANH SÁCH FILE ĐÃ THAY ĐỔI / THÊM MỚI

**ML service (Python):**
- `ml_service/requirements.txt` (thêm `shap`, `sentence-transformers>=2.7.0`)
- `ml_service/dataset.py`, `ml_service/main.py` (thêm feature `overdue_assignments`, SHAP, và endpoint `POST /embed` chạy `AITeamVN/Vietnamese_Embedding_v2`)

**Backend Laravel (PHP):**
- `app/Services/AI/GeminiEmbeddingService.php` (Động cơ Embedding 3 tầng: Local FastAPI `AITeamVN/Vietnamese_Embedding_v2` → HuggingFace API → Gemini Fallback)
- `app/Services/AI/BtecRagService.php` (Hybrid RAG cho BTEC AI Student Support)
- `app/Services/AI/AiTutorService.php` (Trợ giảng AI đa ngành, phân luồng 4 API key, Hybrid RAG đọc file `.docx`/`.pdf` thật, Rolling Summary Memory, UML Mermaid + tạo ảnh AI)
- `app/Services/AI/AiTutorViewHelper.php` (Render Markdown, bảng biểu, sơ đồ Mermaid, khối code chuẩn VS Code Dark+, ảnh minh họa AI)
- `app/Services/AI/AiImageGeneratorService.php` *(mới — sinh ảnh minh họa bằng `IMAGE_API_KEY` / `gemini-3.1-flash-lite-image`)*
- `app/Services/AI/VietnameseSummarizerService.php` *(mới — trích xuất `.docx`/`.pdf`/`.txt`, tóm tắt tài liệu theo file/theo môn, Rolling Summary Memory)*
- `app/Services/AI/BtecSupportAiService.php` (Thêm sơ đồ quy trình Mermaid & bảo vệ code block trong `cleanAiReply()`)
- `app/Services/Attendance/AbsenceStatisticsService.php` *(mới — thống kê số buổi nghỉ học theo Học sinh × Môn học, quy chế > 20% = Trượt môn)*
- `app/Services/Attendance/RealtimeScheduleService.php` *(mới)*
- `app/Services/Teacher/AttendanceService.php` (Tính trạng thái trực tiếp theo tỷ lệ nghỉ của môn học, bỏ gọi AI khi lưu điểm danh)
- `app/Services/AI/DropoutRiskService.php`, `app/Services/AI/DropoutRiskRuleEngine.php`, `app/Services/AI/RiskAnalyticsChartService.php`, `app/Services/AI/GeminiService.php` (Quiz 4 dạng)
- `app/Services/NotificationService.php`, `app/Services/Teacher/AssignmentService.php`, `app/Services/Teacher/GradingService.php`, `app/Services/Student/SubmissionService.php`
- `app/Models/BtecKbEmbedding.php` *(mới)*, `app/Models/NotificationRead.php` *(mới)*, `app/Models/AiConversation.php` (thêm `context_summary`, `summarized_up_to_message_id`), `app/Models/RiskPrediction.php`, `app/Models/Notification.php`, `app/Models/Question.php`, `app/Models/SubmissionDetail.php`, `app/Models/ClassSession.php`
- `app/Http/Controllers/Admin/RagKnowledgeController.php` *(mới — CRUD/Import/Re-index kho tri thức RAG)*
- `app/Http/Controllers/Admin/RiskManagementController.php`, `app/Http/Controllers/Teacher/RiskDashboardController.php` (Chuyển sang thống kê số buổi nghỉ theo môn)
- `app/Http/Controllers/Student/AiTutorController.php` (Trả `content` đầy đủ trong SSE `done` để đồng bộ ảnh AI), `app/Http/Controllers/Student/MaterialController.php` (Tóm tắt AI từng tài liệu & theo môn), `app/Http/Controllers/Student/BtecSupportController.php`
- `app/Http/Controllers/Teacher/AssignmentController.php`, `app/Http/Controllers/Teacher/GradingController.php`, `app/Http/Controllers/Teacher/AttendanceController.php`, `app/Http/Controllers/Student/NotificationController.php`
- `app/Console/Commands/IndexBtecKnowledgeBase.php` *(mới)*, `app/Console/Commands/SyncRealtimeSchedule.php` *(mới)*
- `routes/web.php` (Thêm nhóm route `/admin/rag-kb` và route tóm tắt học liệu)

**CSDL & Seeder:**
- `database/migrations/2026_11_17_000001_create_btec_kb_embeddings_table.php` *(mới)*
- `database/migrations/2026_11_17_000002_add_question_types_to_questions_table.php` *(mới)*
- `database/migrations/2026_11_17_000003_upgrade_notifications_table.php` *(mới)*
- `database/migrations/2026_11_22_000001_add_context_summary_to_ai_conversations_table.php` *(mới)*
- `database/seeders/SubjectMaterialsSeeder.php` *(mới — sinh 20 file học liệu thật `.docx` & `.pdf` cho toàn bộ 10 môn học tại `storage/app/public/materials/`)*

**Giao diện (Blade) & Tài nguyên:**
- `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php` (Nhúng Mermaid + Highlight.js runtime, `network-status-banner`, thêm menu `Dữ liệu RAG (AI Support)`, đổi menu `Tỷ lệ nghỉ học của học sinh`)
- `resources/views/components/network-status-banner.blade.php` *(mới — banner báo mất mạng / không thể kết nối với Server toàn hệ thống)*
- `resources/views/components/mermaid-diagram-runtime.blade.php` *(mới — dựng sơ đồ UML Mermaid + Lightbox + Tải SVG + khối code VS Code Dark+)*
- `resources/views/admin/rag/index.blade.php` *(mới — giao diện quản trị kho tri thức RAG)*
- `resources/views/admin/risk/index.blade.php`, `resources/views/admin/risk/show.blade.php`, `resources/views/teacher/risk/index.blade.php`, `resources/views/teacher/risk/show.blade.php` (Viết lại thuần bảng thống kê số buổi nghỉ theo môn, bỏ toàn bộ biểu đồ)
- *Đã xóa*: `resources/views/components/risk-analytics-charts.blade.php`, `resources/views/components/student-risk-charts.blade.php`
- `resources/views/student/ai_tutor/index.blade.php`, `resources/views/student/materials/index.blade.php`, `resources/views/student/attendance.blade.php`
- `resources/views/components/btec-ai-bubble.blade.php` + `public/images/btec_support_avatar.jpg` *(mới)*
- `resources/views/student/assignments/quiz.blade.php`, `resources/views/student/assignments/show.blade.php`
- `resources/views/student/notifications.blade.php`, `resources/views/student/dashboard.blade.php`
- `resources/views/teacher/grading/index.blade.php`, `resources/views/teacher/assignments/index.blade.php`, `.../show.blade.php`, `.../create.blade.php`, `.../edit.blade.php`
- `resources/views/teacher/attendance/index.blade.php`, `.../edit.blade.php`

**Cấu hình:** `.env`, `.env.example` (thêm `DEEPSEEK_KEY_MAIN`, `DEEPSEEK_KEY_CONTENT`, `DEEPSEEK_MODEL`, `OPENAI_API_KEY`, `OPENAI_MODEL`, `IMAGE_API_KEY`, `IMAGE_MODEL=gemini-3.1-flash-lite-image`, `VI_EMBED_MODEL`, `HF_API_TOKEN`, `GEMINI_EMBED_MODEL`).

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


---

## 7. CẬP NHẬT NGHIỆP VỤ TICKET HỖ TRỢ & KHÔI PHỤC ĐIỂM DANH (22/11/2026)

1. **Migration & CSDL** ([2026_11_22_000005_upgrade_tickets_notifications_and_attendance_restoration.php](database/migrations/2026_11_22_000005_upgrade_tickets_notifications_and_attendance_restoration.php)):
   - `notifications`: thêm `recipient_user_id` để hỗ trợ gửi thông báo riêng tư đích danh từng tài khoản (Admin / Giảng viên / Sinh viên).
   - `btec_support_tickets`: thêm `class_id`, `assigned_teacher_id`, `forwarded_by`, `forwarded_at`, `admin_note`, `teacher_reply`, `teacher_replied_at`, `resolved_by`, `resolved_at`.
   - `attendances`: thêm `is_restored`, `restored_at`, `restored_by`, `restore_reason`.
2. **Luồng Ticket khép kín (Sinh viên → Admin → Giảng viên phụ trách → Thông báo kết quả)**:
   - Modal gửi Ticket trong [btec-ai-bubble.blade.php](resources/views/components/btec-ai-bubble.blade.php): bổ sung loại *"Xin khôi phục điểm danh (đến muộn - tối đa 1 lần/môn)"* và chọn lớp học liên quan; gửi xong tự động tạo thông báo tới Admin ([BtecSupportAiService.php](app/Services/AI/BtecSupportAiService.php)).
   - Admin (`/admin/tickets`, [Admin/TicketController.php](app/Http/Controllers/Admin/TicketController.php)): tiếp nhận, lọc trạng thái, trực tiếp phản hồi hoặc chuyển tiếp tới Giảng viên phụ trách kèm gửi thông báo cho cả Giảng viên và Sinh viên.
   - Giảng viên (`/teacher/tickets`, [Teacher/TicketController.php](app/Http/Controllers/Teacher/TicketController.php)): chỉ thấy phiếu được chuyển cho mình, có nút mở thẳng điểm danh lớp, nhập phản hồi và gửi thông báo kết quả về Sinh viên + Admin.
   - Sinh viên (`/student/tickets`, [Student/TicketController.php](app/Http/Controllers/Student/TicketController.php)): theo dõi trạng thái và phản hồi của tất cả phiếu đã gửi.
3. **Khôi phục điểm danh (tối đa 1 lần / 1 môn cho mỗi sinh viên)**:
   - [AttendanceService::restoreAttendance()](app/Services/Teacher/AttendanceService.php) & [Teacher/AttendanceController::restore()](app/Http/Controllers/Teacher/AttendanceController.php): kiểm tra giới hạn `countRestorationsForSubject($studentId, $subjectId) < 1` trong transaction; nếu hợp lệ thì chuyển sang `Có mặt (P)`, đánh dấu `is_restored = true` và gửi thông báo riêng cho sinh viên; nếu đã dùng 1/1 lượt trong môn đó thì chặn tuyệt đối.
   - UI [teacher/attendance/edit.blade.php](resources/views/teacher/attendance/edit.blade.php): hiển thị trạng thái `Lượt khôi phục ĐD: 0/1` / `Hết lượt (1/1)` và modal xác nhận khôi phục điểm danh ngay trên dòng sinh viên.
4. **Kiểm thử tự động**: bổ sung `T11.1`–`T11.4` và `T12.1`–`T12.2` vào [RunAiSupportAcceptanceTests.php](tests/Feature/RunAiSupportAcceptanceTests.php) — kết quả **30 PASS / 0 FAIL**.


---

## 8. KHẮC PHỤC LỖI SAI NGỮ CẢNH & CHỐNG TRẢ LỜI LINH TINH CHO AI SUPPORT (22/11/2026)

1. **LLM Structured Intent & Tool Router (Zero-PII)** ([SupportIntentRouterService.php](app/Services/AI/SupportIntentRouterService.php)):
   - Thay vì phải hardcode regex cho từng cách diễn đạt mới (*"mai em có buổi học không ạ"*, *"thầy nào dạy em môn dữ liệu giải thuật"*...), khi bộ nhận diện nội bộ chưa bắt được ý định, hệ thống gọi **Vilao AI (`deepseek-v4.1-flash` / `vgpt/gpt-6.1-sol`, `temperature = 0`)** với **chỉ duy nhất câu hỏi gốc** (0% dữ liệu cá nhân/PII) để phân loại thành JSON chuẩn (`intents`, `subject_hint`, `date_hint`, `asks_other_person`, `confidence`).
   - Backend ([SupportAcademicOrchestrator.php](app/Services/AI/SupportAcademicOrchestrator.php)) luôn tự xác thực lại quyền RBAC rồi mới thực thi truy vấn DB (`SCHEDULE`, `INSTRUCTOR`, `SUBJECTS`, `GRADES`, `ATTENDANCE`, `SCHEDULE_CHANGE`). Nếu ý định là `OUT_OF_SCOPE` → nói thẳng không tìm thấy thay vì đẩy bừa sang RAG.
2. **Fuzzy Token Overlap Matching tổng quát cho môn học** ([SupportAcademicOrchestrator::resolveTargetClass()](app/Services/AI/SupportAcademicOrchestrator.php)):
   - Tự động so khớp tỷ lệ từ chồng lấn giữa câu hỏi + `subject_hint` với tên môn/mã lớp trong phạm vi tài khoản (không hardcode tên từng môn).
3. **Chuyển AI Support sang Vilao AI + RAG Relevance Gate** ([BtecRagService.php](app/Services/AI/BtecRagService.php), [BtecSupportAiService.php](app/Services/AI/BtecSupportAiService.php)):
   - Loại bỏ tình trạng phụ thuộc duy nhất vào Gemini (hay gặp 429) bằng chuỗi gọi Vilao AI (`DEEPSEEK_KEY_MAIN` → `OPENAI_API_KEY`) trước, Gemini fallback sau.
   - Thêm ngưỡng tin cậy `is_confident` (lọc stopwords tiếng Việt + kiểm tra ngưỡng cosine/lexical) và chốt chặn ở `generateInstantRagAnswer()`: nếu không đủ tin cậy hoặc đoạn trích rỗng → **tuyệt đối không chép bừa `top_chunks[0]`**, không cache kết quả fallback, mà thừa nhận thẳng thắn và gợi ý hỏi rõ hơn / tạo Ticket.
4. **Kiểm thử tự động**: bổ sung `T13.1`–`T13.3` vào [RunAiSupportAcceptanceTests.php](tests/Feature/RunAiSupportAcceptanceTests.php) — kết quả **33 PASS / 0 FAIL**, đồng thời đã chạy `php artisan cache:clear` để xóa cache fallback cũ.

5. **Chuẩn hóa trình bày Thời khóa biểu, Bảng điểm, Giảng viên & Môn học** ([SupportAcademicOrchestrator.php](app/Services/AI/SupportAcademicOrchestrator.php), [btec-ai-bubble.blade.php](resources/views/components/btec-ai-bubble.blade.php)):
   - `resolveDateRange()`: ưu tiên nhận diện thứ cụ thể (*t2..t7*, *thứ 2..thứ 7*, *chủ nhật / cn*) kết hợp *tuần này / tuần sau* (VD: *"lịch học ngày t4 tuần sau"* → lọc đúng ngày **Thứ 4 tuần sau** thay vì cả tuần).
   - **Thời khóa biểu**: nhóm theo từng ngày kèm **Thứ**, giờ cắt gọn `HH:MM`, tách 2 dòng rõ ràng (Ca + Môn học / Lớp · Phòng · Giảng viên).
   - **Bảng điểm**: nhóm theo môn, ghi rõ loại bài (*Tự luận / Assignment* hoặc *Trắc nghiệm / Quiz*) và tách dòng kết quả điểm, bỏ lặp tên môn ở từng dòng.
   - **Giảng viên & Môn học**: trình bày theo từng mục tách dòng; đổi kiểu inline code trên khung AI Support sang badge sáng nhẹ thay vì nền đen chói.

6. **Đồng bộ DB toàn hệ thống & sửa tra cứu tổng số buổi / ngưỡng 20% theo môn** ([RealisticLmsDataSyncSeeder.php](database/seeders/RealisticLmsDataSyncSeeder.php), [SupportIntentRouterService.php](app/Services/AI/SupportIntentRouterService.php), [SupportAcademicOrchestrator.php](app/Services/AI/SupportAcademicOrchestrator.php)):
   - Sửa định tuyến câu hỏi kiểu *"môn giải thuật nghỉ quá 20% là bao nhiêu buổi"* / *"tổng số buổi môn X"* đi đúng vào `ATTENDANCE` (tra `classes.total_sessions`, fallback `sessions()->count()`) thay vì lọt sang RAG không có số liệu; bổ sung nhận diện `ngày kia` (+2 ngày) và sửa lỗi lồng `**` ở tiêu đề bảng điểm.
   - Chạy [RealisticLmsDataSyncSeeder.php](database/seeders/RealisticLmsDataSyncSeeder.php): đồng bộ `total_sessions = actual_sessions` cho cả 7 lớp hiện có (12/12 buổi) và tạo mới 3 lớp đầy đủ 15/15 buổi + điểm danh + bài tập (đã công bố & chờ công bố) + thông báo cho 3 môn còn trống (`OOP19305`, `MKT19306`, `SWE19307`) → đạt **10/10 môn học có lớp & lịch đầy đủ**.

7. **Giọng văn gần gũi, thân thiết & câu nhắc nhở tự nhiên cho cả 2 Chatbot** ([SupportAcademicOrchestrator.php](app/Services/AI/SupportAcademicOrchestrator.php), [BtecSupportAiService.php](app/Services/AI/BtecSupportAiService.php), [AiTutorService.php](app/Services/AI/AiTutorService.php)):
   - Thêm lời chào mở đầu tự nhiên và câu nhắc nhở / động viên sát ngữ cảnh ở cuối mỗi phản hồi (nhắc đến sớm điểm danh đầu ca khi xem lịch, động viên / nhắc ôn tập tùy theo mức điểm thực tế khi xem điểm, nhắc giữ chuyên cần tùy theo số buổi còn được nghỉ, gợi ý trao đổi giảng viên khi tra thông tin GV).
   - Cập nhật System Prompt cho cả BTEC AI Support và Mr. Biết Tuốt dùng giọng văn gần gũi, ấm áp, thân thiết với sinh viên.

8. **Chuẩn hóa giao diện Phiếu hỗ trợ phía Sinh viên chỉ hiển thị phản hồi của Admin** ([student/tickets/index.blade.php](resources/views/student/tickets/index.blade.php), [Teacher/TicketController.php](app/Http/Controllers/Teacher/TicketController.php), [teacher/tickets/index.blade.php](resources/views/teacher/tickets/index.blade.php)):
   - Phía Sinh viên chỉ hiện duy nhất khung **Phản hồi từ Quản trị viên** (`resolution_note`), ẩn thông tin chuyển tiếp nội bộ và phản hồi riêng của Giảng viên.
   - Giảng viên phản hồi báo cáo kết quả nội bộ về cho Admin; Admin là đầu mối duy nhất chốt và gửi phản hồi chính thức tới Sinh viên.

9. **Kiểm duyệt ngôn từ chửi tục Tiếng Việt & Tiếng Anh cho toàn bộ Chat AI và Bình luận** ([ContentModerationService.php](app/Services/ContentModerationService.php)):
   - Phát hiện & tự động che (`***`) từ ngữ thô tục / xúc phạm bằng tiếng Việt (có dấu, không dấu, viết tắt, lách ký tự `.`, `*`, `_`, `-`) và tiếng Anh, có ranh giới từ chặt để tránh bắt nhầm các từ học tập (`class`, `assignment`, `document`, `các`, `buổi`, `lớn`, `luôn`...).
   - Áp dụng đồng bộ tại: **BTEC AI Support** (`/chat`, `/stream`, `/ticket` trong [BtecSupportAiService.php](app/Services/AI/BtecSupportAiService.php)), **Mr. Biết Tuốt** (`/message`, `/stream` trong [AiTutorService.php](app/Services/AI/AiTutorService.php)), và **Bình luận bài tập** ([DiscussionService.php](app/Services/DiscussionService.php), [Student/DiscussionController.php](app/Http/Controllers/Student/DiscussionController.php), [Teacher/DiscussionController.php](app/Http/Controllers/Teacher/DiscussionController.php)).
   - Khi vi phạm: tự động che nội dung trước khi lưu DB, không đẩy nội dung chửi tục ra API AI ngoài, và hiển thị cảnh báo hành vi không phù hợp với môi trường học tập.
   - Bổ sung `T14.1`–`T14.3` vào [RunAiSupportAcceptanceTests.php](tests/Feature/RunAiSupportAcceptanceTests.php) — kết quả **36 PASS / 0 FAIL**.
   - Nâng cấp cơ chế che từ thay thế `***` sang **làm mờ trực tiếp bằng CSS (`filter: blur(5px); user-select: none`)** qua thẻ `[[BLUR]]...[[/BLUR]]` và cập nhật tức thì bong bóng tin nhắn user ngay sau khi gửi (không cần F5) trên cả [btec-ai-bubble.blade.php](resources/views/components/btec-ai-bubble.blade.php), [student/ai_tutor/index.blade.php](resources/views/student/ai_tutor/index.blade.php) và [shared/discussions.blade.php](resources/views/shared/discussions.blade.php).

10. **Nâng cấp toàn bộ hệ thống giao diện theo phong cách High-Tech Enterprise (Obsidian + Crisp Precision Surfaces)** ([layouts/app.blade.php](resources/views/layouts/app.blade.php), [layouts/guest.blade.php](resources/views/layouts/guest.blade.php), [components/btec-ai-bubble.blade.php](resources/views/components/btec-ai-bubble.blade.php), [student/ai_tutor/index.blade.php](resources/views/student/ai_tutor/index.blade.php)):
    - Áp dụng Design System toàn cục (`Inter` + `Be Vietnam Pro` + `JetBrains Mono`, `tabular-nums` cho bảng số liệu, sidebar Obsidian `#09090b` hairline, nền `#fafafa`, bề mặt trắng phẳng viền 1px sắc nét, khử hoàn toàn bóng nhựa, glassmorphism và gradient tím neon).
    - Loại bỏ toàn bộ avatar hoạt hình trẻ con (SVG "Mr. Biết Tuốt Cute" trên sidebar, ảnh mascot `btec_support_avatar.jpg` và `mr_biet_tuot.jpg`), thay bằng monogram kỹ thuật tối giản `SL` / `AI` và tông màu Obsidian sắc nét.
    - Nâng cấp trang đăng nhập (`guest.blade.php`) với nền chấm lưới kỹ thuật tối giản và focus ring sắc nét.

11. **Chặn triệt để xưng hô vô lễ (`mày`/`tao`), chửi xúc phạm (`con chó`, `con lợn`, `lol`/`loz`) + AI Semantic Moderation + sửa lỗi regex `^con ` + chốt xưng hô `thầy` – `em` cho Trợ giảng AI** ([ContentModerationService.php](app/Services/ContentModerationService.php), [SupportAcademicOrchestrator.php](app/Services/AI/SupportAcademicOrchestrator.php), [AiTutorService.php](app/Services/AI/AiTutorService.php)):
    - Nâng cấp `ContentModerationService` lên 2 tầng: Tầng 1 chặn đầy đủ `mày`, `tao`, `con chó`, `con lợn`, `cái lol`/`loz`, xúc phạm tiếng Việt & tiếng Anh (0 false positive với `máy`, `tạo`, `các`, `buổi`, `class`, `assignment`); Tầng 2 gọi AI Semantic Moderation (Vilao) bắt các câu xúc phạm gián tiếp không có trong từ điển cứng.
    - Sửa lỗi regex `^con ` trong `SupportAcademicOrchestrator` (từng hiểu nhầm `"con chó này"` thành `"còn ..."` rồi kế thừa intent Giảng viên).
    - Chốt bắt buộc Trợ giảng AI (`AiTutorService`) xưng **"thầy"** và gọi sinh viên là **"em"**.
    - Bổ sung test `T14.4` kiểm chứng trực tiếp 3 câu trong ảnh — kết quả **37 PASS / 0 FAIL**.

12. **Vẽ lại toàn bộ 9 biểu đồ Dashboard (Admin, Giảng viên, Sinh viên) theo chuẩn High-Tech Analytics** ([admin/dashboard.blade.php](resources/views/admin/dashboard.blade.php), [teacher/dashboard.blade.php](resources/views/teacher/dashboard.blade.php), [student/dashboard.blade.php](resources/views/student/dashboard.blade.php)):
    - Loại bỏ viền đen thô (`#0f172a`) quanh lát Pie/Doughnut, màu cầu vồng chói, chữ legend mờ (`#cbd5e1`) và nhãn số chèn đè lên biểu đồ.
    - Áp dụng đồng bộ: Doughnut vòng mảnh `cutout: 76–78%` viền trắng tách lát, Line Chart nét `2px` kèm gradient mờ tinh tế, Bar Chart cột gọn đơn sắc Obsidian (`#18181b`) hover Cobalt (`#2563eb`), trục ẩn viền + lưới ngang siêu mảnh `#f4f4f5`, font số `JetBrains Mono` và Tooltip tối Obsidian `#09090b` kèm % tự động.

13. **Sửa lỗi AI Support chỉ tải 30 tin cũ nhất khi mở lại** ([BtecSupportController.php](app/Http/Controllers/Student/BtecSupportController.php), [btec-ai-bubble.blade.php](resources/views/components/btec-ai-bubble.blade.php)):
    - Đổi truy vấn `init()` từ `orderBy('created_at')->take(30)` (lấy 30 tin cũ nhất, bỏ sót tin mới khi vượt 30 tin) sang `orderByDesc('message_id')->take(40)->get()->reverse()` để luôn hiển thị 40 tin nhắn mới nhất theo đúng thứ tự thời gian.
    - Bỏ gọi lại `initSession()` trong nút làm mới phiên chat để tạo đúng phiên hội thoại mới thay vì tải lại lịch sử cũ.

14. **Sửa triệt để lỗi điều hướng sai màn hình khi hỏi Học phí / Thủ tục & Xuất bộ Slide PPTX + Kịch bản PDF mới ra `Downloads`** ([BtecRagService.php](app/Services/AI/BtecRagService.php), [BtecSupportAiService.php](app/Services/AI/BtecSupportAiService.php)):
    - Gỡ từ khóa đơn lẻ `'muộn'` khỏi `APP_ATTENDANCE` và chặn gắn nút điều hướng màn hình Điểm danh/Bảng điểm cho các câu hỏi Học phí (`FINANCIAL_SUPPORT`), Bảo lưu, Tuyển sinh, Khiếu nại.
    - Giới hạn RAG chỉ lấy tối đa 2 đoạn sát nhất (đoạn 2 phải đạt `>= 65%` điểm đoạn 1) và chỉ lấy tối đa 2 nguồn trích dẫn trực tiếp từ đoạn số 1 (không gom 8 nguồn rác).
    - Tự động bật nút **Tạo Ticket** (`needs_escalation = true`) và hướng dẫn ngắn gọn trong 2–3 câu để sinh viên lập phiếu liên hệ trực tiếp với **Phòng Đào tạo** khi tài liệu chưa quy định mức phạt cố định hoặc chức năng đang cập nhật cải tiến.

15. **Cập nhật đồng bộ Logo cú thông thái Mr. Biết Tuốt & Xuất hoàn chỉnh bộ Slide PPTX + 3 Kịch bản PDF (Script 1, 2, 3) ra `C:\Users\admin\Downloads`** ([public/images/mr_biet_tuot.png](public/images/mr_biet_tuot.png), [layouts/app.blade.php](resources/views/layouts/app.blade.php), [student/ai_tutor/index.blade.php](resources/views/student/ai_tutor/index.blade.php), [AiTutorService.php](app/Services/AI/AiTutorService.php)):
    - Sửa lỗi cú pháp dấu nháy kép dòng 265 tại `AiTutorService.php` (`**\"thầy\"**` – `**\"em\"**`).
    - Thay toàn bộ logo/avatar **Mr. Biết Tuốt** (trên thanh menu trái, tiêu đề trang, avatar bong bóng chat, màn hình chờ và modal khởi tạo phiên) bằng logo cú thông thái mới (`public/images/mr_biet_tuot.png`).
    - Giữ nguyên khung thiết kế & cấu trúc chữ đậm/thường (`<a:r>`) của slide gốc, thay ảnh chụp giao diện thực tế mới nhất có logo Mr. Biết Tuốt, **bỏ hoàn toàn Vilao** (chỉ ghi **DeepSeek & Google Gemini**), **bỏ toàn bộ test case**, **bỏ AI cảnh báo sinh viên nghỉ học** (Slide 7 & Slide 8), và làm nổi bật sự khác biệt giữa **Gia sư AI Mr. Biết Tuốt** với **Trợ lý Học vụ & Quy chế AI** cùng các công nghệ/skill cốt lõi.
    - Xuất đầy đủ vào `C:\Users\admin\Downloads`:
      - Slide PowerPoint: `Smart_LMS_BestCoder_2026_Final.pptx` (và bản sao `Smart_LMS_BestCoder_2026_Updated.pptx`, `Smart_LMS_BestCoder_2026_Clean.pptx`).
      - Kịch bản 1 (Trình tạo Quiz AI): `Script_phu_1_Trinh_tao_Quiz_AI_Updated.pdf` (2 trang).
      - Kịch bản 2 (Gia sư AI Mr. Biết Tuốt): `Script_phu_2_Gia_su_AI_Mr_Biet_Tuot_Updated.pdf` (2 trang).
      - Kịch bản 3 (Trợ lý Học vụ & Quy chế AI + So sánh 2 AI): `Script_phu_3_Tro_ly_Hoc_vu_AI_Updated.pdf` (2 trang).
