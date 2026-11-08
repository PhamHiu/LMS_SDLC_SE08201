# BẢN THUYẾT MINH SẢN PHẨM DỰ THI
## HỆ THỐNG QUẢN LÝ HỌC TẬP THÔNG MINH SMART LMS TÍCH HỢP HỆ SINH THÁI ĐA TRÍ TUỆ NHÂN TẠO TOÀN DIỆN

---

## I. THÔNG TIN CHUNG

* **Tên sản phẩm dự thi:** Hệ sinh thái Quản lý Học tập Thông minh Smart LMS tích hợp Đa Trí tuệ Nhân tạo.
* **Lĩnh vực dự thi:** Ứng dụng Trí tuệ Nhân tạo (AI), Học máy (Machine Learning) và Dữ liệu lớn trong Giáo dục số & Quản trị Đại học - Cao đẳng.
* **Đối tượng thụ hưởng:** 
  1. **Sinh viên:** Nhận hỗ trợ học thuật, gỡ lỗi bài làm, giải đáp quy chế và cảnh báo tiến độ học tập.
  2. **Giảng viên:** Nhận hỗ trợ tự động hóa ra đề thi, theo dõi phân tích rủi ro học viên và can thiệp sư phạm.
  3. **Quản trị viên / Phòng Đào tạo / Phòng Công tác Sinh viên (CTSV):** Nhận bức tranh tổng thể về tỷ lệ rủi ro bỏ học, giảm tải xử lý thắc mắc hành chính và tiếp nhận hồ sơ hỗ trợ tự động.
* **Nền tảng công nghệ nền tảng:** Laravel Framework, PHP 8.2+, MySQL, TailwindCSS, Machine Learning Microservice (FastAPI / Scikit-Learn), Google Gemini Multi-modal API, Hệ thống RAG (Retrieval-Augmented Generation) & Semantic Hybrid Search.

---

## II. MỞ ĐẦU

### 1. Ý tưởng hình thành sản phẩm
Trong công tác đào tạo tại các trường Đại học và Cao đẳng chuẩn quốc tế (như Cao đẳng Anh Quốc BTEC FPT), một trường học số không thể chỉ dừng lại ở vai trò là nơi đăng tải file slide bài giảng hay nhập điểm thủ công. Thực tế vận hành cho thấy 4 nút thắt lớn:
1. **Ở cấp độ quản trị & giảng dạy:** Giảng viên và nhà trường thường chỉ phát hiện sinh viên có nguy cơ nghỉ học quá 20% (bị cấm thi) hoặc trượt môn khi kỳ học đã sắp kết thúc. Thiếu hoàn toàn một cơ chế AI tự động phân tích dữ liệu chuyên cần và điểm số để dự báo sớm.
2. **Ở công tác khảo thí:** Giảng viên mất rất nhiều thời gian để biên soạn các bộ câu hỏi trắc nghiệm bám sát đề cương tài liệu học tập chuẩn hóa.
3. **Ở khía cạnh học thuật sinh viên:** Khi tự học ở nhà, sinh viên gặp khó khăn với bài tập lập trình, lỗi cú pháp code, hoặc chưa hiểu bản chất kiến thức nhưng không thể có gia sư kèm cặp 24/7.
4. **Ở dịch vụ hỗ trợ sinh viên:** Phòng Đào tạo và Phòng CTSV luôn bị quá tải bởi hàng ngàn câu hỏi lặp đi lặp lại về quy chế (học phí, điều kiện bảo lưu, ngưỡng điểm danh, quy định đạo văn, phúc khảo điểm...).

Từ những vấn đề thực tiễn trên, dự án **Smart LMS** được thiết kế để giải quyết toàn diện bằng việc tích hợp **4 hệ thống Trí tuệ Nhân tạo chuyên biệt**, phục vụ đầy đủ cả 3 đối tượng người dùng trong nhà trường.

### 2. Mục tiêu và lợi ích của sản phẩm
* **Mục tiêu:** Xây dựng nền tảng LMS thông minh thế hệ mới, chuẩn hóa nghiệp vụ quản lý đào tạo và khai thác tối đa sức mạnh của AI trong giám sát, giảng dạy và hỗ trợ sinh viên.
* **Lợi ích mang lại:**
  * **Tự động hóa quản trị rủi ro học vụ:** Giúp nhà trường chủ động ngăn ngừa tình trạng sinh viên thôi học, bỏ tiết hoặc cấm thi.
  * **Nâng cao hiệu suất giảng dạy:** Giúp giảng viên tạo đề thi kiểm tra kiến thức chỉ trong vài giây từ giáo trình thực tế.
  * **Cá nhân hóa việc học:** Mỗi sinh viên có một gia sư AI kiên nhẫn giảng giải bản chất bài tập, sửa code và một trợ lý ảo thường trực giải đáp mọi thủ tục học đường 24/7.

---

## III. TIẾN TRÌNH THỰC HIỆN

### 1. Chuẩn bị công nghệ / Công cụ / Tài nguyên
* **Kiến trúc hệ thống đa tầng:**
  * **Tầng Ứng dụng Quản trị (Core Web App):** Laravel Framework (PHP 8.2), kiến trúc MVC, xác thực phân quyền 3 cấp (Admin, Teacher, Student), bảo mật CSRF, chuẩn hóa cơ sở dữ liệu quan hệ MySQL.
  * **Tầng Trí tuệ Nhân tạo & Học máy (AI/ML Services):**
    * Service Python FastAPI kết hợp thư viện Scikit-Learn để phân loại dự báo rủi ro sinh viên dựa trên tập dữ liệu học tập.
    * Engine Toán học Deterministic Rule Engine chạy trực tiếp trên nền tảng PHP để đảm bảo tính sẵn sàng cao, dự phòng và giải thích minh bạch.
    * Mô hình ngôn ngữ lớn đa phương thức Google Gemini 3.5 Flash xử lý văn bản, trích xuất cấu trúc đề thi và phân tích file ảnh/code.
    * Hệ thống RAG (Retrieval-Augmented Generation) kết hợp bộ tokenizer tiếng Việt và kho tri thức Sổ tay sinh viên BTEC FPT.
* **Tài nguyên tri thức chuyên ngành:** Sổ tay sinh viên BTEC FPT ver.BTEC.9.2024, Quy chế thi cử, Quy chế tài chính học phí, Quy định đạo văn và Quy trình dịch vụ một cửa.

### 2. Quy trình xây dựng sản phẩm
1. **Thiết kế cơ sở dữ liệu và nghiệp vụ lõi LMS:** Xây dựng quản lý Chuyên ngành, Môn học, Lớp học, Ca học, Lịch sử điểm danh (P/A/L), Bài tập/Quiz trắc nghiệm và Bảng điểm.
2. **Xây dựng hệ thống AI Cảnh báo sớm (Dropout Risk Engine):** Thu thập các trường dữ liệu điểm danh, điểm số, xây dựng công thức tính toán chỉ số rủi ro, kết nối ML Service và xây dựng giao diện can thiệp (Intervention) cho giảng viên.
3. **Xây dựng AI Khảo thí (Quiz Generator):** Phát triển bộ chuyển đổi tài liệu (PDF, Word, TXT) thành ngân hàng câu hỏi trắc nghiệm có 4 đáp án và lời giải đúng chuẩn JSON.
4. **Xây dựng Gia sư AI Học thuật ("HỌC CÙNG MR. BIẾT TUỐT"):** Tích hợp phương pháp sư phạm Socratic, cơ chế nhận diện file bài làm/ảnh code dưới 5MB và bộ lọc định dạng bảng biểu, loại bỏ ký tự rác Markdown.
5. **Xây dựng Trợ lý ảo Quy chế ("BTEC FPT AI Support Assistant"):** Xây dựng kho RAG tri thức quy chế, thiết kế giao diện **Bong bóng nổi (Floating Bubble Widget)** ghim toàn cục, bổ sung nút Deep Link dẫn link app và form gửi Ticket hỗ trợ tự động.
6. **Kiểm thử tích hợp tự động:** Chạy toàn bộ bộ test kiểm thử nghiệp vụ (BestCoder Test Suite) bảo đảm mọi chức năng hoạt động chính xác và an toàn.

### 3. Mô tả giải pháp và cách thức hoạt động
* Toàn bộ hệ thống liên kết chặt chẽ qua luồng dữ liệu thời gian thực:
  * Điểm danh buổi học hàng ngày được tự động cập nhật vào **AI Cảnh báo sớm**.
  * Giảng viên sử dụng **AI Sinh đề** để kiểm tra mức độ hiểu bài của sinh viên sau mỗi chuyên đề.
  * Sinh viên tự học ở nhà gặp khó khăn có thể mở **MR. BIẾT TUỐT** để được giảng bài và kiểm tra lỗi sai trong code.
  * Khi có bất kỳ thắc mắc nào về nội quy, cấm thi, học phí hay cách thao tác ứng dụng, sinh viên mở **Bong bóng nổi BTEC AI** để tra cứu căn cứ pháp lý và nhận đường dẫn chuyển trang tức thời.
* **Cơ chế phát hiện sự cố & Chuyển tiếp người dùng:** Nếu sinh viên phát hiện lỗi điểm danh hoặc khiếu nại điểm số, AI tự động kích hoạt form tạo **Ticket hỗ trợ** phân luồng trực tiếp đến Phòng Đào tạo/CTSV để cán bộ trường xử lý thủ công, tránh gây sai sót quyền lợi cho sinh viên.

---

## IV. TỔNG HỢP TOÀN BỘ CÁC HỆ THỐNG TRÍ TUỆ NHÂN TẠO (AI) TRONG DỰ ÁN

Trong dự án Smart LMS, có tổng cộng **4 hệ thống Trí tuệ Nhân tạo riêng biệt**, mỗi AI nắm giữ một vai trò và phục vụ đối tượng cụ thể:

| STT | Tên hệ thống AI | Vị trí trên hệ thống | Đối tượng phục vụ | Nhiệm vụ & Tác dụng chính |
| :---: | :--- | :--- | :--- | :--- |
| **1** | **AI Cảnh báo sớm Nguy cơ Bỏ học & Học vụ** *(Early Warning & Dropout Risk Engine)* | Menu `Quản lý nguy cơ (AI)` | Quản trị viên & Giảng viên | Phân tích tự động tỷ lệ nghỉ học, điểm số, bài tập để dự báo 4 mức rủi ro (SAFE, MEDIUM, HIGH, VERY HIGH); chỉ rõ nguyên nhân và gợi ý kế hoạch can thiệp. |
| **2** | **AI Khảo thí & Sinh Đề Trắc Nghiệm Tự Động** *(AI Quiz & Assignment Generator)* | Menu `Bài tập & Chấm bài` $\rightarrow$ `Tạo bài trắc nghiệm với AI` | Giảng viên | Đọc hiểu trực tiếp tài liệu bài giảng (PDF, Word, TXT) và tự động tạo đề thi trắc nghiệm chuẩn cấu trúc 4 đáp án A/B/C/D. |
| **3** | **Gia sư AI Học thuật Thông thái** *(HỌC CÙNG MR. BIẾT TUỐT)* | Menu lớn `HỌC CÙNG MR. BIẾT TUỐT` (cuối menu SV) | Sinh viên | Giải thích bản chất kiến thức, hướng dẫn giải bài tập theo phương pháp Socratic, soi lỗi/debug code, đọc ảnh & file đính kèm dưới 5MB. |
| **4** | **Trợ lý ảo Quy chế & Điều hướng 24/7** *(BTEC FPT AI Student Support Assistant)* | **Bong bóng nổi (Floating Bubble Widget)** ghim ở góc màn hình | Sinh viên | Tra cứu quy chế đào tạo, điểm danh 20%, học phí từ kho dữ liệu Sổ tay sinh viên BTEC FPT có dẫn nguồn kiểm chứng; cung cấp nút Deep Link mở màn hình app và tạo Ticket hỗ trợ. |

---

### CHI TIẾT TỪNG HỆ THỐNG AI VÀ CÁCH THỨC HỖ TRỢ NGƯỜI DÙNG:

### 1. AI CẢNH BÁO SỚM NGUY CƠ BỎ HỌC & HỌC VỤ (Dropout Risk Engine)
* **Vị trí và màn hình:** 
  * Quản trị viên: Phân hệ `Quản lý nguy cơ (AI)` tổng quan toàn trường.
  * Giảng viên: Phân hệ `Cảnh báo học sinh (AI)` theo từng lớp phụ trách.
* **AI hỗ trợ những gì?**
  * **Giám sát dữ liệu chuyên cần & điểm số:** Tự động theo dõi từng buổi học, tính toán tỷ lệ vắng theo từng môn độc lập (tuân thủ nghiêm ngặt quy định: nghỉ trên 20% tổng số buổi sẽ bị cấm thi).
  * **Phân loại 4 cấp độ rủi ro:** 
    * `SAFE (An toàn)`: Chuyên cần tốt, điểm số đạt chuẩn.
    * `MEDIUM (Trung bình)`: Bắt đầu có dấu hiệu nghỉ học hoặc nợ bài tập.
    * `HIGH (Nguy cơ cao)`: Số buổi nghỉ chạm sát ngưỡng 20% hoặc điểm trung bình dưới 5.0.
    * `VERY_HIGH (Rất nguy cấp)`: Đã vượt ngưỡng nghỉ 20% hoặc có nguy cơ thôi học.
  * **Trí tuệ nhân tạo có thể giải trình (Explainable AI - XAI):** Không chỉ đưa ra cảnh báo chung chung, AI bóc tách chi tiết danh sách các **Yếu tố nguy cơ (Risk Factors)** và gợi ý hành động can thiệp (Intervention) cho giảng viên như: Nhắc nhở qua email, mời phụ huynh hoặc phụ đạo bù kiến thức.
  * **Cơ chế hoạt động kép (Dual Engine):** Kết nối đồng thời với Microservice Machine Learning (Python FastAPI) để phân tích học máy và Rule Engine toán học nội bộ để đảm bảo hệ thống không bao giờ bị gián đoạn.

### 2. AI KHẢO THÍ & SINH ĐỀ TRẮC NGHIỆM TỰ ĐỘNG (Gemini Quiz Generator)
* **Vị trí và màn hình:** Phân hệ Giảng viên $\rightarrow$ `Bài tập & Chấm bài` $\rightarrow$ Nút bấm `Tạo bài trắc nghiệm với AI`.
* **AI hỗ trợ những gì?**
  * **Đọc hiểu tài liệu đa định dạng:** Giảng viên tải lên trực tiếp tài liệu bài giảng dạng file `.pdf`, `.docx` hoặc `.txt` (hỗ trợ đến 5MB). AI tự động bóc tách và phân tích ngữ nghĩa nội dung học phần.
  * **Biên soạn ngân hàng câu hỏi tự động:** Dựa trên chủ đề hoặc tài liệu tải lên, AI tạo ra danh sách từ 1 đến 50 câu hỏi trắc nghiệm chất lượng cao. Mỗi câu hỏi gồm: Nội dung câu hỏi, 4 phương án lựa chọn (A, B, C, D) và đáp án chính xác.
  * **Đồng bộ hóa 1 chạm vào hệ thống LMS:** Giảng viên chỉ cần kiểm tra nhanh và bấm nút áp dụng, toàn bộ câu hỏi và đáp án sẽ được tự động điền vào form tạo Quiz của LMS, tiết kiệm tới 80% thời gian soạn bài kiểm tra định kỳ.

### 3. GIA SƯ AI HỌC THUẬT THÔNG THÁI ("HỌC CÙNG MR. BIẾT TUỐT")
* **Vị trí và màn hình:** Mục nổi bật kích thước lớn `🧙‍♂️ HỌC CÙNG MR. BIẾT TUỐT` đặt ở cuối danh sách menu sinh viên.
* **AI hỗ trợ những gì?**
  * **Vai trò sư phạm chuẩn mực:** Xưng hô tôn ti *"Thầy Biết Tuốt - Em/Trò"*, đóng vai trò một giáo sư uyên bác, kiên nhẫn giảng dạy và truyền cảm hứng.
  * **4 Chế độ học tập chuyên biệt:**
    1. *💡 Giải thích:* Giảng giải cặn kẽ bản chất kiến thức, nguyên lý thuật toán, cấu trúc dữ liệu theo cách dễ hiểu nhất.
    2. *🧭 Hướng dẫn bài tập (Socratic Method):* Tuyệt đối không làm hộ bài tập lớn (Assignment) mà đặt các câu hỏi gợi ý từng bước, giúp sinh viên tự tìm ra đáp án.
    3. *🔍 Soi lỗi bài làm & Debug code:* Sinh viên dán đoạn code lỗi, Thầy sẽ chỉ ra lỗi cú pháp, lỗi logic, giải thích nguyên nhân và cách tối ưu.
    4. *✍️ Luyện tập:* Thầy đưa ra các câu hỏi thử thách phản xạ để sinh viên tự kiểm tra lại kiến thức môn học.
  * **Xử lý Đa phương tiện (Multi-modal):** Cho phép sinh viên tải lên ảnh chụp lỗi màn hình IDE, ảnh đề bài viết tay hoặc file mã nguồn/tài liệu (tối đa 5MB) để Thầy trực tiếp phân tích hình ảnh và trả lời.
  * **Hiển thị trực quan:** Tự động loại bỏ hoàn toàn các ký tự Markdown thô (`###`, `*`), format câu trả lời thành các bảng biểu so sánh và in đậm các từ khóa kiến thức cốt lõi.

### 4. TRỢ LÝ ẢO QUY CHẾ & ĐIỀU HƯỚNG 24/7 ("BTEC FPT AI STUDENT SUPPORT ASSISTANT")
* **Vị trí và màn hình:** Dạng **Bong bóng nổi (Floating Bubble Widget)** ghim cố định ở góc dưới bên phải màn hình (`bottom-6 right-6`), thường trực 24/7 trên mọi trang của sinh viên.
* **AI hỗ trợ những gì?**
  * **Giải đáp quy chế đào tạo chuẩn xác (RAG Evidence):** AI kết nối với kho dữ liệu Sổ tay sinh viên BTEC FPT ver.BTEC.9.2024 (S01–S16). Trả lời chính xác mọi câu hỏi về: Ngưỡng nghỉ 20% cấm thi, quy chế nộp bài Assignment, nộp lại (Resubmission/Retake), quy định đạo văn Turnitin, thủ tục bảo lưu, biểu phí tài chính và thời hạn nộp học phí.
  * **Chống ảo giác & Trích dẫn bằng chứng:** Mọi câu trả lời về quy chế đều bắt buộc trích dẫn văn bản căn cứ cụ thể (ví dụ: `[Căn cứ: Sổ tay sinh viên BTEC FPT ver.BTEC.9.2024]`). AI không bao giờ tự bịa đặt mức học phí các năm chưa ban hành hay số điện thoại riêng của cán bộ trường.
  * **Điều hướng ứng dụng thông minh (Deep Link Navigation):** AI hiểu rõ cấu trúc của Smart LMS. Khi sinh viên hỏi: *"Xem lịch học và điểm danh ở đâu?"*, AI không chỉ giải thích bằng lời mà cung cấp ngay **Nút bấm hành động (Action Button)** để sinh viên bấm 1 chạm là nhảy thẳng đến đúng màn hình `/student/attendance` hay `/student/schedule`.
  * **Cầu nối tiếp nhận Ticket hỗ trợ:** Khi gặp các vấn đề cần sự can thiệp của người thật (khiếu nại điểm danh sai, phúc khảo bài tập, xin gia hạn học phí), AI mở form tạo **Ticket hỗ trợ** ngay trên giao diện để gửi thẳng hồ sơ về Phòng Đào tạo/CTSV xử lý.

---

## V. TÍNH MỚI VÀ TÍNH SÁNG TẠO CỦA SẢN PHẨM

### 1. Tính mới
* **Mô hình kiến trúc Đa Trí tuệ Nhân tạo toàn diện:** Không sử dụng một chatbot chung chung mà phân tách thành 4 hệ thống AI độc lập: AI Cảnh báo rủi ro cho Quản trị viên, AI Soạn đề cho Giảng viên, AI Gia sư học thuật và AI Trợ lý quy chế cho Sinh viên.
* **Tích hợp sâu vào quy trình nghiệp vụ đào tạo:** AI không nằm tách rời bên ngoài mà được nhúng trực tiếp vào các luồng nghiệp vụ thực tế của trường: Điểm danh $\rightarrow$ Cảnh báo nguy cơ $\rightarrow$ Hỗ trợ học tập $\rightarrow$ Xử lý Ticket học vụ.

### 2. Tính sáng tạo
* **Sự kết hợp giữa AI Sư phạm (Pedagogy) và RAG Căn cứ (Grounding):** Sinh viên vừa có người thầy dạy tư duy học thuật (MR. BIẾT TUỐT), vừa có người trợ lý hành chính giải thích luật học đường (BTEC AI Support).
* **Trải nghiệm người dùng liền mạch (Floating Popover & Deep Links):** Sinh viên không cần rời khỏi trang đang học tập vẫn có thể tra cứu thông tin qua bong bóng nổi và di chuyển nhanh qua các nút điều hướng thông minh.
* **Khả năng dự phòng linh hoạt:** Hệ thống AI Cảnh báo sớm luôn bảo đảm hoạt động thông suốt nhờ cơ chế tự động chuyển đổi giữa Học máy (FastAPI) và Bộ quy tắc nghiệp vụ (PHP Engine).

---

## VI. KHẢ NĂNG ÁP DỤNG THỰC TIỄN

### 1. Phạm vi và đối tượng áp dụng
* Triển khai đồng bộ tại toàn bộ các cơ sở của Cao đẳng Anh Quốc BTEC FPT (Hà Nội, Đà Nẵng, TP.HCM, Cần Thơ).
* Mã nguồn module hóa rõ ràng, dễ dàng mở rộng để áp dụng cho các trường Đại học, Cao đẳng thuộc Tổ chức Giáo dục FPT cũng như các cơ sở giáo dục khác trên toàn quốc.

### 2. Hiệu quả dự kiến
* **Giảm 30% tỷ lệ sinh viên trượt môn / cấm thi:** Nhờ hệ thống AI Cảnh báo sớm hỗ trợ giảng viên phát hiện kịp thời các sinh viên vắng nhiều trước khi vượt ngưỡng 20%.
* **Tiết kiệm 80% thời gian ra đề kiểm tra:** Giảng viên chỉ cần nạp tài liệu bài giảng, AI tự động tạo ngân hàng trắc nghiệm.
* **Giảm 70% tải công việc học vụ:** Trợ lý ảo BTEC AI Support giải đáp tự động phần lớn các thắc mắc thường gặp về nội quy, lịch thi, học phí 24/7.
* **Nâng cao chất lượng tự học:** Sinh viên luôn có gia sư MR. BIẾT TUỐT bên cạnh để gỡ lỗi code và củng cố kiến thức mọi lúc mọi nơi.

---

## VII. HƯỚNG PHÁT TRIỂN TRONG TƯƠNG LAI

1. **AI Nhận diện Giọng nói Hai chiều (Voice AI):** Tích hợp giao tiếp bằng giọng nói tự nhiên với Thầy Biết Tuốt để phục vụ việc luyện phản xạ tiếng Anh chuyên ngành.
2. **AI Phân tích Cảm xúc Học tập:** Phát hiện sớm các dấu hiệu căng thẳng hoặc áp lực học tập của sinh viên qua ngôn từ giao tiếp để chủ động kết nối với chuyên viên tư vấn tâm lý học đường.
3. **Mở rộng RAG tài liệu theo từng lớp học:** Tự động lập chỉ mục giáo trình, slide bài giảng riêng của từng giảng viên trong mỗi học kỳ.
4. **Phát triển ứng dụng di động Native (Android & iOS):** Mang toàn bộ nền tảng Smart LMS và Hệ sinh thái Đa AI lên thiết bị di động của sinh viên và giảng viên.

---

## VIII. KẾT LUẬN

Sản phẩm **Hệ thống Quản lý Học tập Thông minh Smart LMS tích hợp Hệ sinh thái Đa Trí tuệ Nhân tạo** là một công trình hoàn chỉnh, bắt nguồn từ những nhu cầu thực tế cấp thiết trong trường học và được hiện thực hóa bằng giải pháp công nghệ toàn diện.

Bằng việc tích hợp đầy đủ **4 hệ thống Trí tuệ Nhân tạo (AI Cảnh báo rủi ro học tập, AI Khảo thí sinh đề, Gia sư AI học thuật MR. BIẾT TUỐT và Trợ lý ảo quy chế BTEC AI Support)**, sản phẩm không chỉ hỗ trợ tối đa cho sinh viên trong học tập mà còn là cánh tay đắc lực cho giảng viên trong giảng dạy và ban giám hiệu trong quản trị đào tạo.

Đây là giải pháp chuyển đổi số giáo dục tiêu biểu, khẳng định tinh thần đổi mới sáng tạo, năng lực làm chủ công nghệ và khát vọng mang lại một môi trường giáo dục thông minh, hiện đại và nhân văn cho sinh viên Việt Nam.
