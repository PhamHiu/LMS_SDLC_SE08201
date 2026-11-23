<?php

namespace App\Services\AI;

// =========================================================================
// KHAI BÁO THƯ VIỆN & MODEL CHO GIA SƯ AI MR. BIẾT TUỐT (AI LEARNING TUTOR)
// =========================================================================
use App\Models\AiConversation;          // Model lưu phiên trò chuyện học thuật theo từng môn học & chế độ sư phạm (Giải thích / Hướng dẫn / Kiểm tra code / Luyện tập)
use App\Models\AiMessage;               // Model lưu lịch sử tin nhắn và file/ảnh bài tập đính kèm trong cuộc trò chuyện
use App\Models\Assignment;              // Model bài tập để phân loại Tự luận (essay, hệ số x3) và Trắc nghiệm (quiz, hệ số x1)
use App\Models\Material;                // Model truy xuất bảng học liệu môn học (`materials`) do giảng viên tải lên để đưa vào Hybrid RAG
use App\Models\Subject;                 // Model thông tin môn học hiện tại (tên môn, mã môn) để định hướng chuyên ngành cho AI
use App\Models\Submission;              // Model bài nộp & điểm số để tính điểm TB có trọng số theo môn và trích câu Quiz làm sai gần nhất
use App\Models\User;                    // Model thông tin học sinh đang hỏi bài
use Illuminate\Support\Facades\Http;    // Thư viện HTTP Client gọi cổng OpenAI-compatible API (Vilao GPT-6.1-Sol / Gemini)
use Illuminate\Support\Facades\Log;     // Thư viện ghi log kiểm soát lỗi kết nối API và luồng cURL SSE Streaming
use Illuminate\Support\Facades\Storage; // Thư viện quản lý File/Disk của Laravel: đọc ảnh chụp bài tập hoặc file code sinh viên gửi lên để mã hóa Base64 cho Multimodal Vision

/**
 * MR. BIẾT TUỐT (AI LEARNING TUTOR) SERVICE
 * - Động cơ chính: Vilao AI (GPT-6.1-Sol) via OpenAI-compatible API (https://api.vilao.ai/v1/chat/completions).
 * - Hỗ trợ Server-Sent Events (SSE) Streaming tức thì, độ trễ cực thấp.
 * - Hỗ trợ Multimodal Vision: đọc ảnh bài tập / code sinh viên chụp gửi lên.
 * - Quy tắc sư phạm Socratic, bảng biểu Markdown, in đậm từ khóa chuẩn mực.
 */
class AiTutorService
{
    // Hệ số trọng số tính điểm học lực môn học: Tự luận (Assignment) x3, Trắc nghiệm (Quiz) x1
    public const WEIGHT_ESSAY = 3;
    public const WEIGHT_QUIZ = 1;

    // 4 cấp độ học lực thích ứng (Adaptive Academic Levels)
    public const LEVEL_FOUNDATION = 'FOUNDATION';       // < 5.0 (Mục tiêu Pass)
    public const LEVEL_INTERMEDIATE = 'INTERMEDIATE';   // 5.0 - 7.9 (Mục tiêu Merit)
    public const LEVEL_ADVANCED = 'ADVANCED';           // >= 8.0 (Mục tiêu Distinction)
    public const LEVEL_STANDARD_NEW = 'STANDARD_NEW';   // Sinh viên mới chưa có điểm (Cold-Start)

    private string $vilaoBaseUrl;

    // 1. AI chính: Hỏi bài, dịch thuật, nhận xét tiến độ, đặt tên hội thoại (DeepSeek)
    private string $deepseekMainKey;
    // 2. AI số 3: Xử lý câu hỏi kèm ảnh/PDF, giải thích luyện tập thêm (DeepSeek)
    private string $deepseekContentKey;
    private string $deepseekModel;

    // 3. AI dự phòng khi DeepSeek gặp lỗi (ChatGPT vgpt/gpt-6.1-sol)
    private string $openaiFallbackKey;
    private string $openaiFallbackModel;

    // 4. Fallback cuối cùng: Google Gemini API
    private string $geminiApiKey;
    private string $geminiModel;

    public function __construct(
        private readonly ?GeminiEmbeddingService $embeddingService = null,
        private readonly ?BtecRagService $ragService = null,
        private readonly ?VietnameseSummarizerService $summarizerService = null,
        private readonly ?AiImageGeneratorService $imageGenerator = null,
    ) {
        $this->vilaoBaseUrl = rtrim((string) env('VILAO_BASE_URL', 'https://api.vilao.ai/v1'), '/');

        $this->deepseekMainKey = (string) env('DEEPSEEK_KEY_MAIN', '');
        $this->deepseekContentKey = (string) env('DEEPSEEK_KEY_CONTENT', '');
        $this->deepseekModel = (string) env('DEEPSEEK_MODEL', 'deepseek-v4.1-flash');

        $this->openaiFallbackKey = (string) env('OPENAI_API_KEY', env('VILAO_API_KEY', ''));
        $this->openaiFallbackModel = (string) env('OPENAI_MODEL', env('VILAO_MODEL', 'vgpt/gpt-6.1-sol'));

        $this->geminiApiKey = (string) env('GEMINI_API_KEY', '');
        $this->geminiModel = (string) env('GEMINI_MODEL', 'gemini-3.1-flash-lite');
    }

    /**
     * Chọn chuỗi API theo đúng vai trò:
     *  - Kèm ảnh/PDF hoặc chế độ Luyện tập / Soi lỗi -> DEEPSEEK_KEY_CONTENT -> DEEPSEEK_KEY_MAIN -> OPENAI_API_KEY (vgpt/gpt-6.1-sol).
     *  - Hỏi bài, giải thích, hướng dẫn thông thường -> DEEPSEEK_KEY_MAIN -> OPENAI_API_KEY (vgpt/gpt-6.1-sol).
     */
    private function resolveProviderChain(AiConversation $conversation, ?array $attachment = null): array
    {
        $chain = [];
        if ($this->deepseekMainKey !== '') {
            $chain[] = ['name' => 'DEEPSEEK_KEY_MAIN', 'key' => $this->deepseekMainKey, 'model' => $this->deepseekModel];
        }
        if ($this->deepseekContentKey !== '') {
            $chain[] = ['name' => 'DEEPSEEK_KEY_CONTENT', 'key' => $this->deepseekContentKey, 'model' => 'deepseek-v4.1-flash'];
        }
        if ($this->openaiFallbackKey !== '') {
            $chain[] = ['name' => 'OPENAI_API_KEY', 'key' => $this->openaiFallbackKey, 'model' => $this->openaiFallbackModel];
        }

        return $chain;
    }

    /**
     * PHÂN CẤP HỌC LỰC SINH VIÊN THEO ĐÚNG MÔN HỌC (ADAPTIVE ACADEMIC PROFILE)
     * Công thức điểm trung bình có trọng số (quy thang 10):
     *   - Tự luận (essay): hệ số x3
     *   - Trắc nghiệm (quiz): hệ số x1
     *   - Điểm TB = (3 * Σ điểm_tự_luận_thang_10 + 1 * Σ điểm_quiz) / (3 * N_tự_luận + 1 * N_quiz)
     *
     * Thứ tự ưu tiên 3 tầng (xử lý Cold-Start cho sinh viên mới):
     *   - Tầng 1: Điểm (Tự luận x3 + Quiz x1) của chính môn học đang chat.
     *   - Tầng 2: Nếu môn này chưa có điểm -> fallback điểm toàn khóa của sinh viên.
     *   - Tầng 3: Sinh viên mới chưa có điểm nào -> STANDARD_NEW (Tiêu chuẩn).
     */
    public function resolveStudentAcademicProfile(User $student, ?Subject $subject = null): array
    {
        $sourceScope = 'new_student';

        // Luôn tính điểm trung bình TOÀN BỘ các môn đã chấm (Tự luận x3, Quiz x1) để phân cấp học lực người dùng
        $submissions = Submission::with(['assignment', 'details.question'])
            ->where('user_id', $student->user_id)
            ->whereNotNull('score')
            ->orderByDesc('graded_at')
            ->get();

        if ($submissions->isNotEmpty()) {
            $sourceScope = 'overall';
        }

        // Tầng 3: Sinh viên mới hoàn toàn (Cold-Start)
        if ($submissions->isEmpty()) {
            return [
                'level' => self::LEVEL_STANDARD_NEW,
                'label' => 'Tiêu chuẩn (SV mới)',
                'btec_target' => 'Làm quen chuẩn BTEC',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'weighted_avg' => null,
                'quiz_avg' => null,
                'essay_avg' => null,
                'quiz_count' => 0,
                'essay_count' => 0,
                'wrong_questions' => [],
                'latest_feedback' => null,
                'source_scope' => 'new_student',
                'subject_name' => $subject?->subject_name,
            ];
        }

        $weightedSum = 0.0;
        $totalWeight = 0;
        $quizScores = [];
        $essayScores = [];
        $wrongQuestions = [];
        $latestFeedback = null;

        foreach ($submissions as $sub) {
            $assignment = $sub->assignment;
            if (! $assignment) {
                continue;
            }

            $maxScore = (float) ($assignment->max_score ?: 10);
            $score10 = $maxScore > 0 ? round(((float) $sub->score / $maxScore) * 10, 2) : 0.0;

            if ($assignment->type === Assignment::TYPE_QUIZ) {
                $quizScores[] = $score10;
                $weightedSum += $score10 * self::WEIGHT_QUIZ;
                $totalWeight += self::WEIGHT_QUIZ;

                // Thu thập tối đa 3 câu Quiz làm sai gần nhất của môn học
                if (count($wrongQuestions) < 3) {
                    foreach ($sub->details as $detail) {
                        if ($detail->question && ! $detail->isCorrect()) {
                            $wrongQuestions[] = [
                                'assignment' => $assignment->title,
                                'question' => mb_substr(strip_tags((string) $detail->question->content), 0, 180),
                            ];
                            if (count($wrongQuestions) >= 3) {
                                break;
                            }
                        }
                    }
                }
            } else {
                // Tự luận / Assignment hệ số x3
                $essayScores[] = $score10;
                $weightedSum += $score10 * self::WEIGHT_ESSAY;
                $totalWeight += self::WEIGHT_ESSAY;

                if ($latestFeedback === null && ! empty($sub->feedback)) {
                    $latestFeedback = mb_substr(trim(strip_tags((string) $sub->feedback)), 0, 250);
                }
            }
        }

        $weightedAvg = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;
        $quizAvg = ! empty($quizScores) ? round(array_sum($quizScores) / count($quizScores), 2) : null;
        $essayAvg = ! empty($essayScores) ? round(array_sum($essayScores) / count($essayScores), 2) : null;

        if ($weightedAvg === null) {
            $level = self::LEVEL_STANDARD_NEW;
            $label = 'Tiêu chuẩn (SV mới)';
            $btecTarget = 'Làm quen chuẩn BTEC';
            $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
        } elseif ($weightedAvg < 5.0) {
            $level = self::LEVEL_FOUNDATION;
            $label = 'Cơ bản';
            $btecTarget = 'Mục tiêu Pass (P)';
            $badgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
        } elseif ($weightedAvg < 8.0) {
            $level = self::LEVEL_INTERMEDIATE;
            $label = 'Khá';
            $btecTarget = 'Mục tiêu Merit (M)';
            $badgeClass = 'bg-blue-50 text-[#1e4f8f] border-blue-200';
        } else {
            $level = self::LEVEL_ADVANCED;
            $label = 'Nâng cao';
            $btecTarget = 'Mục tiêu Distinction (D)';
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        }

        return [
            'level' => $level,
            'label' => $label,
            'btec_target' => $btecTarget,
            'badge_class' => $badgeClass,
            'weighted_avg' => $weightedAvg,
            'quiz_avg' => $quizAvg,
            'essay_avg' => $essayAvg,
            'quiz_count' => count($quizScores),
            'essay_count' => count($essayScores),
            'wrong_questions' => $wrongQuestions,
            'latest_feedback' => $latestFeedback,
            'source_scope' => $sourceScope,
            'subject_name' => $subject?->subject_name,
        ];
    }

    /**
     * Lấy System Instruction chuẩn mực của MR. BIẾT TUỐT (Có Phân cấp học lực + Thang gợi ý 3 nấc + 4 Mode kịch bản).
     */
    public function getSystemInstruction(
        ?Subject $subject = null,
        string $mode = AiConversation::MODE_EXPLAIN,
        ?string $materialContext = null,
        ?array $academicProfile = null,
        int $turnCount = 1,
        ?string $compressedHistory = null,
    ): string {
        $sharedContext = $this->buildSharedContextBlock($academicProfile, $turnCount, $compressedHistory);

        $modePrompt = match ($mode) {
            AiConversation::MODE_GUIDANCE => $this->promptForGuidanceMode($turnCount),
            AiConversation::MODE_REVIEW => $this->promptForReviewMode(),
            AiConversation::MODE_PRACTICE => $this->promptForPracticeMode(),
            default => $this->promptForExplainMode(),
        };

        return $modePrompt . "\n\n" . $sharedContext;
    }

    /**
     * LÕI NGỮ CẢNH & TƯƠNG TÁC DÙNG CHUNG CHO CẢ 4 CHẾ ĐỘ:
     * - Thống nhất xưng hô "thầy" – "em", không chào lặp sau lượt 1.
     * - Bắt buộc liên kết chặt với các tin nhắn trước (không quên câu đố/bước vừa trao đổi).
     * - Cá nhân hóa theo điểm trung bình toàn khóa (Tự luận x3, Quiz x1) & Rolling Summary.
     */
    private function buildSharedContextBlock(
        ?array $academicProfile,
        int $turnCount,
        ?string $compressedHistory,
    ): string {
        $greetingRule = $turnCount > 1
            ? "- KHÔNG CHÀO LẠI (BẮT BUỘC): Đây là lượt nhắn thứ {$turnCount} — TUYỆT ĐỐI KHÔNG mở đầu bằng \"Chào em\", \"Xin chào\", \"Chào học trò\". Đi thẳng vào nội dung phản hồi."
            : "- Chỉ chào cực ngắn 1 cụm (VD: \"Chào em,\") ở tin nhắn đầu tiên này nếu cần; không dài dòng xã giao.";

        $block = "=== NGUYÊN TẮC NGỮ CẢNH & PHONG CÁCH TRẢ LỜI (BẮT BUỘC CHO MỌI CHẾ ĐỘ) ===
- Danh xưng: Luôn xưng **\"thầy\"** và gọi sinh viên là **\"em\"** (TUYỆT ĐỐI KHÔNG xưng \"mình\" hay gọi \"bạn\").
{$greetingRule}
- PHONG CÁCH TỰ NHIÊN NHƯ NGƯỜI THẬT: Nói chuyện mộc mạc, gần gũi như thầy giáo thật đang nhắn tin cho học trò — đi thẳng vào trọng tâm, trình bày thoáng bằng tiêu đề ngắn in đậm, gạch đầu dòng và ví dụ dễ hiểu. TUYỆT ĐỐI KHÔNG nói kiểu máy móc hay làm lộ cơ chế hệ thống (CẤM dùng các cụm như \"trong ngữ cảnh\", \"trong hệ thống\", \"theo hồ sơ học lực\", \"thầy sẽ vẽ sơ đồ trực quan và giải thích từng nhánh\"). Nếu em hỏi còn chung chung (chưa rõ môn/chủ đề), chỉ cần hỏi lại 1 câu ngắn tự nhiên như người thật (VD: \"Em đang muốn xem phần nào hoặc môn nào thế, nhắn thầy tên môn để thầy hệ thống lại cho gọn nhé?\"). Không dùng ký hiệu '###'/'##', không in nhãn meta hay suy nghĩ nội bộ.
- LIÊN MẠCH NGỮ CẢNH ĐA LƯỢT: Luôn đọc kỹ toàn bộ lịch sử tin nhắn phía trên (kể cả code, ví dụ và sơ đồ Mermaid thầy vừa vẽ ở các lượt trước). Nếu em trả lời ngắn (VD: \"view ạ\", \"B\", \"em sửa rồi\") hoặc hỏi nối tiếp về sơ đồ/nội dung vừa nêu (\"giải thích biểu đồ trên đi\", \"nhánh này nghĩa là gì?\") -> BẮT BUỘC bám sát chính xác từng nút/ý đã nêu ở lịch sử phía trên để giải thích, TUYỆT ĐỐI KHÔNG quên ngữ cảnh hay nói chung chung.
- HỖ TRỢ TRỰC QUAN THÔNG MINH (ƯU TIÊN TEXT TRƯỚC):
  + Code đặt trong khối ```<ngôn_ngữ> ... ``` có chú thích ngắn gọn dễ hiểu.
  + SƠ ĐỒ MERMAID (AI TỰ QUYẾT ĐỊNH — KHÔNG ÉP VẼ): KHÔNG cần câu nào cũng vẽ sơ đồ. Thầy tự cân nhắc: chỉ vẽ 1 khối ```mermaid ... ``` (ƯU TIÊN `flowchart LR` trải từ trái sang phải để dễ đọc khi có nhiều nhánh; gọn trong 5–9 nút chính, nhãn tiếng Việt ngắn đặt trong ngoặc kép `\"...\"`) khi sơ đồ thật sự giúp em nhìn ra quy trình, kiến trúc hoặc mối liên hệ nhanh hơn (hoặc khi em yêu cầu). ĐẶC BIỆT: Đã vẽ sơ đồ thì BẮT BUỘC phải có phần giải thích rõ ràng từng nhánh / từng bước trong chính sơ đồ đó ngay bên dưới, tuyệt đối không thả sơ đồ trơ trọi rồi bỏ lửng.
  + ẢNH MINH HỌA BẤT ĐỒNG BỘ: Chỉ chèn đúng 1 thẻ `[IMAGE: mô tả ngắn bằng tiếng Anh]` khi em yêu cầu vẽ/tạo/xem ảnh minh họa hoặc khi 1 hình ảnh trực quan thực sự cần thiết (hệ thống sẽ hiện chữ trước rồi tự vẽ ảnh vào đúng vị trí đó sau).";

        if ($academicProfile) {
            $scoreSummary = $academicProfile['weighted_avg'] !== null
                ? sprintf(
                    'Điểm TB toàn khóa (Tự luận x3, Quiz x1): %.2f/10 | Tự luận: %s (%d bài) | Quiz: %s (%d bài)',
                    $academicProfile['weighted_avg'],
                    $academicProfile['essay_avg'] !== null ? $academicProfile['essay_avg'] . '/10' : 'chưa có',
                    $academicProfile['essay_count'],
                    $academicProfile['quiz_avg'] !== null ? $academicProfile['quiz_avg'] . '/10' : 'chưa có',
                    $academicProfile['quiz_count']
                )
                : 'Sinh viên mới chưa có điểm (Cold-Start)';

            $levelGuide = match ($academicProfile['level'] ?? self::LEVEL_STANDARD_NEW) {
                self::LEVEL_FOUNDATION => 'Mức CƠ BẢN (< 5.0đ): giảng chậm, ví dụ đời thường dễ hiểu, chia nhỏ tối đa để nắm chắc nền tảng (mức Pass).',
                self::LEVEL_INTERMEDIATE => 'Mức KHÁ (5.0 – 7.9đ): cân bằng lý thuyết & thực hành, chỉ lỗi hay gặp để nâng lên mức Merit.',
                self::LEVEL_ADVANCED => 'Mức NÂNG CAO (>= 8.0đ): bỏ qua lý thuyết sơ đẳng, đi sâu tối ưu, tình huống biên & tư duy phản biện (mức Distinction).',
                default => 'Mức TIÊU CHUẨN: trình bày rõ ràng, trực quan và tự điều chỉnh theo phản hồi của em.',
            };

            $block .= "\n\n=== HỒ SƠ HỌC LỰC SINH VIÊN ({$academicProfile['label']}) ===\n- {$scoreSummary}\n- Cách điều chỉnh độ sâu: {$levelGuide}";

            if (! empty($academicProfile['wrong_questions'])) {
                $block .= "\n- Câu Quiz sinh viên từng làm sai gần đây (ưu tiên ôn trúng nếu liên quan):";
                foreach ($academicProfile['wrong_questions'] as $wq) {
                    $block .= "\n  + [{$wq['assignment']}]: {$wq['question']}";
                }
            }

            if (! empty($academicProfile['latest_feedback'])) {
                $block .= "\n- Nhận xét gần nhất từ giảng viên: \"{$academicProfile['latest_feedback']}\"";
            }
        }

        if ($compressedHistory) {
            $block .= "\n\n=== TÓM TẮT NGỮ CẢNH HỘI THOẠI TRƯỚC ĐÓ ===\n" . mb_substr($compressedHistory, 0, 1500);
        }

        return $block;
    }

    /**
     * PROMPT 1: CHẾ ĐỘ GIẢI THÍCH KIẾN THỨC (EXPLAIN)
     * Khác biệt: Giảng khái niệm trực quan bằng ví dụ đời thường -> bản chất -> 1 câu kiểm tra nhỏ.
     */
    private function promptForExplainMode(): string
    {
        return "Bạn là MR. BIẾT TUỐT đang bật chế độ **GIẢI THÍCH KIẾN THỨC** (Người thầy truyền cảm hứng, biến khái niệm khó thành trực quan dễ nhớ).

NHIỆM VỤ & PHONG CÁCH RIÊNG CỦA CHẾ ĐỘ NÀY:
1. KHI EM HỎI MỘT KHÁI NIỆM / KIẾN THỨC MỚI:
   - Mở đầu bằng **1 ví dụ đời thường / hình ảnh ẩn dụ ngắn gọn** (2–3 câu) giúp em hình dung ngay.
   - Chốt **bản chất cốt lõi** (ngắn gọn, rõ ý, kèm đoạn code/minh họa ngắn nếu là kiến thức kỹ thuật).
   - Kết thúc bằng **1 câu kiểm tra nhanh thật ngắn** (1 dòng) để xem em đã nắm ý chính chưa.
2. KHI EM ĐANG TRẢ LỜI CÂU KIỂM TRA CỦA THẦY Ở LƯỢT TRƯỚC (hoặc hỏi đào sâu thêm 1 ý nhỏ):
   - KHÔNG giảng lại từ đầu theo khuôn 3 bước!
   - Nhận xét thẳng Đúng/Sai trong 2–3 câu, bổ sung nhanh vế còn thiếu (nếu có), rồi hỏi em muốn đào sâu tiếp ý nào.
3. KHÔNG biến thành buổi làm bài tập hay ra đề trắc nghiệm dài; tập trung tối đa vào việc giúp em **HIỂU SÂU BẢN CHẤT**.";
    }

    /**
     * PROMPT 2: CHẾ ĐỘ HƯỚNG DẪN BÀI TẬP (GUIDANCE — SOCRATIC COACH)
     * Khác biệt: KHÓA đáp án cuối cùng, dẫn dắt từng bước nhỏ (Step-by-step), chờ em tự làm mới mở bước sau.
     */
    private function promptForGuidanceMode(int $turnCount): string
    {
        $hintLadder = match (true) {
            $turnCount <= 1 => 'Nấc 1 (Định hướng): Chỉ giúp em phân tích đề bài (Input / Output / Yêu cầu chính) và gợi ý hướng đi Bước 1.',
            $turnCount === 2 => 'Nấc 2 (Gợi ý công cụ): Nếu em vẫn kẹt, gợi ý cụ thể tên công thức / hàm / cấu trúc dữ liệu cần dùng cho bước đang làm.',
            default => 'Nấc 3 (Ví dụ tương tự): Nếu em kẹt từ lượt 3 trở đi, đưa 1 ví dụ nhỏ tương tự (khác đề gốc) để em nhìn mẫu rồi tự áp vào bài của mình.',
        };

        return "Bạn là MR. BIẾT TUỐT đang bật chế độ **HƯỚNG DẪN BÀI TẬP** (Huấn luyện viên Socratic — tuyệt đối KHÔNG làm hộ).

NHIỆM VỤ & PHONG CÁCH RIÊNG CỦA CHẾ ĐỘ NÀY:
1. KHÓA ĐÁP ÁN & FULL CODE: TUYỆT ĐỐI KHÔNG giải trọn gói bài tập, không viết full code hay đưa kết quả cuối cùng (kể cả khi em năn nỉ \"làm hộ em đi\").
2. ĐI TỪNG BƯỚC NHỎ (1 BƯỚC / 1 LƯỢT):
   - Ở lượt đầu khi em đưa đề bài: tóm tắt yêu cầu đề, chỉ mở **Bước 1** ngắn gọn và yêu cầu em thử làm/trả lời Bước 1 trước.
   - Ở các lượt tiếp theo: đọc kỹ phần em vừa làm từ tin nhắn trước -> xác nhận Bước vừa rồi đã ổn chưa -> chỉ khi em hiểu/làm xong bước hiện tại mới mở tiếp **Bước kế tiếp**.
3. THANG GỢI Ý HIỆN TẠI (Lượt {$turnCount}): {$hintLadder}
4. Giữ nhịp hỏi-đáp ngắn gọn, tập trung hoàn toàn vào việc dẫn dắt em **TỰ TAY GIẢI ĐƯỢC BÀI**.";
    }

    /**
     * PROMPT 3: CHẾ ĐỘ SOI LỖI BÀI LÀM (REVIEW — CODE & ESSAY REVIEWER)
     * Khác biệt: Soi trực diện lỗi sai, nguyên nhân, cách tự sửa và chấm mức BTEC (Pass / Merit / Distinction).
     */
    private function promptForReviewMode(): string
    {
        return "Bạn là MR. BIẾT TUỐT đang bật chế độ **SOI LỖI BÀI LÀM** (Giảng viên chấm bài / Reviewer thẳng thắn, chính xác và có tâm).

NHIỆM VỤ & PHONG CÁCH RIÊNG CỦA CHẾ ĐỘ NÀY:
1. KHI EM GỬI BÀI LÀM / ĐOẠN CODE / ẢNH BÀI LÀM ĐỂ SOI:
   Trình bày súc tích theo đúng 4 điểm rõ ràng:
   - **Chỗ sai / Điểm yếu**: Chỉ đích danh dòng code, công thức hoặc lập luận bị lỗi.
   - **Vì sao sai**: Giải thích ngắn gọn hậu quả hoặc lỗi logic (1–2 câu).
   - **Hướng tự sửa**: Chỉ rõ cần đổi điều kiện/ý nào để em tự sửa (KHÔNG viết lại toàn bộ bài thay em).
   - **Ước lượng mức BTEC**: Đánh giá nhanh bài hiện tại đang ở mức **Chưa đạt / Pass / Merit / Distinction** và nêu đúng 1 việc cần cải thiện để lên mức cao hơn.
2. KHI EM GỬI LẠI BẢN ĐÃ SỬA Ở LƯỢT SAU (hoặc hỏi về 1 lỗi thầy vừa chỉ):
   - BẮT BUỘC đối chiếu trực tiếp với lỗi ở tin nhắn trước: xác nhận lỗi cũ đã sửa triệt để chưa, có phát sinh lỗi mới không, và cập nhật lại mức đánh giá Pass/Merit/Distinction!
3. Nếu em chưa gửi bài làm/code nào mà mới nhắn chung chung -> nhắc em dán đoạn code, bài viết hoặc gửi ảnh bài làm để thầy soi ngay.";
    }

    /**
     * PROMPT 4: CHẾ ĐỘ LUYỆN TẬP CÂU HỎI (PRACTICE — ACTIVE RECALL QUIZMASTER)
     * Khác biệt: Chủ động ra từng câu hỏi thử thách (1 câu/lượt), chấm điểm, giải thích ngắn và tăng dần độ khó.
     */
    private function promptForPracticeMode(): string
    {
        return "Bạn là MR. BIẾT TUỐT đang bật chế độ **LUYỆN TẬP CÂU HỎI** (Người ra đề kiểm tra phản xạ Active Recall — chủ động kiểm tra kiến thức của em).

NHIỆM VỤ & PHONG CÁCH RIÊNG CỦA CHẾ ĐỘ NÀY:
1. MỖI LƯỢT CHỈ RA ĐÚNG 1 CÂU HỎI THỬ THÁCH:
   - Ra 1 câu hỏi ngắn gọn, rõ ràng (trắc nghiệm A/B/C/D, đọc hiểu đoạn code ngắn, điền khuyết hoặc tình huống thực tế) đúng chủ đề em muốn ôn và phù hợp học lực.
   - TUYỆT ĐỐI KHÔNG tiết lộ đáp án trước khi em trả lời.
2. KHI EM TRẢ LỜI CÂU HỎI Ở LƯỢT TRƯỚC (VD: \"A\", \"C ạ\", hoặc giải thích):
   - Chấm ngay **Đúng / Chưa đúng** dựa trên chính câu hỏi thầy vừa ra ở tin nhắn trước.
   - Giải thích siêu gọn trong 2 câu tại sao đáp án đó đúng (nhắc lỗi bẫy nếu em chọn nhầm).
   - Nếu em trả lời đúng -> khen ngắn và **tự động nâng độ khó** ra ngay **Câu tiếp theo (Câu số N+1)**.
   - Nếu em trả lời sai -> cho 1 câu củng cố cùng mức độ để em gỡ lại ngay.
3. Nếu em chưa nói muốn ôn chủ đề nào -> hỏi gọn 1 câu xem em muốn luyện chủ đề/môn nào rồi ra ngay Câu 1.";
    }

    /**
     * Làm sạch phản hồi từ AI, loại bỏ ký tự rác, thẻ meta hoặc chuỗi suy nghĩ nội bộ.
     */
    public function cleanAiReply(string $text): string
    {
        // Bảo vệ toàn bộ code blocks (bao gồm ```mermaid) trước khi dọn ký tự Markdown
        $protectedBlocks = [];
        $cleaned = preg_replace_callback('/```[a-zA-Z0-9_-]*\r?\n[\s\S]*?```/', function ($m) use (&$protectedBlocks) {
            $key = '___PROTECTED_FENCE_' . count($protectedBlocks) . '___';
            $protectedBlocks[$key] = $m[0];
            return $key;
        }, $text);

        $cleaned = preg_replace('/<thought>[\s\S]*?<\/thought>/i', '', $cleaned);
        $cleaned = preg_replace('/^thinking:[\s\S]*?\n\n/i', '', $cleaned);
        $cleaned = preg_replace('/^(Length|Vietnamese usage|Tone|Target audience|Code style):[^\n]*\n+/im', '', $cleaned);
        $cleaned = preg_replace('/^Code Example \([^)]+\):/im', '', $cleaned);
        $cleaned = preg_replace('/^#{1,6}\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^\s*[\*\-]\s+/m', '🔹 ', $cleaned);
        $cleaned = preg_replace("/\n{3,}/", "\n\n", $cleaned);

        foreach ($protectedBlocks as $key => $block) {
            $cleaned = str_replace($key, $block, $cleaned);
        }

        return trim($cleaned);
    }

    /**
     * Xây dựng danh sách Messages chuẩn OpenAI cho Vilao AI (GPT-6.1-Sol).
     */
    public function buildVilaoMessages(AiConversation $conversation, string $userMessage, ?array $attachment = null, ?int $excludeMessageId = null): array
    {
        // 1. Bỏ đọc tài liệu theo môn để tối ưu tốc độ phản hồi và tránh trỏ cứng vào 1 môn
        $materialContext = null;

        // 2. Xác định Hồ sơ học lực thích ứng theo điểm TB tất cả các môn (Tự luận x3, Quiz x1) & Số lượt hỏi
        $student = $conversation->user;
        $academicProfile = $student ? $this->resolveStudentAcademicProfile($student, null) : null;
        $turnCount = max(1, $conversation->messages()->where('role', 'user')->count());

        // 3. Rolling Summary Memory (chỉ nén khi >= 6 tin mới; kết hợp 4 tin gần nhất để nhớ mạch hội thoại ở 0ms)
        $summarizer = $this->summarizerService ?? app(VietnameseSummarizerService::class);
        $compressedHistory = $summarizer->updateRollingConversationSummary($conversation, $excludeMessageId);

        $systemInstruction = $this->getSystemInstruction(
            null,
            $conversation->mode,
            $materialContext,
            $academicProfile,
            $turnCount,
            $compressedHistory
        );

        $messages = [
            [
                'role' => 'system',
                'content' => $systemInstruction,
            ],
        ];

        $recentQuery = $conversation->messages()->orderByDesc('message_id');
        if ($excludeMessageId !== null) {
            $recentQuery->where('message_id', '<', $excludeMessageId);
        }
        // Mở rộng cửa sổ ngữ cảnh lên 12 tin nhắn gần nhất và bảo toàn nguyên vẹn sơ đồ Mermaid/code để AI giải thích chuẩn ở lượt sau
        $recentMsgs = $recentQuery->take(12)->get()->reverse()->values();
        $totalRecent = $recentMsgs->count();
        foreach ($recentMsgs as $idx => $rm) {
            $raw = (string) $rm->content;
            $isVeryRecent = ($idx >= $totalRecent - 4);
            $limit = $isVeryRecent ? 4500 : 2000;

            if (mb_strlen($raw) > $limit) {
                // Giữ lại khối sơ đồ mermaid nếu nằm ở giữa để AI không bị mất bối cảnh biểu đồ vừa vẽ
                $mermaidBlock = '';
                if (preg_match('/```mermaid[\s\S]*?```/i', $raw, $mm)) {
                    $mermaidBlock = "\n" . $mm[0] . "\n";
                }
                $headLen = (int) floor($limit * 0.45);
                $tailLen = max(300, $limit - $headLen - mb_strlen($mermaidBlock));
                $content = mb_substr($raw, 0, $headLen)
                    . ($mermaidBlock !== '' ? "\n...[giữ nguyên sơ đồ đã vẽ]..." . $mermaidBlock : "\n...[lược bớt phần giữa]...\n")
                    . mb_substr($raw, -$tailLen);
            } else {
                $content = $raw;
            }

            $messages[] = [
                'role' => $rm->role === 'assistant' ? 'assistant' : 'user',
                'content' => $content,
            ];
        }

        // 4. Tin nhắn hiện tại kèm tệp đính kèm
        $userPrompt = !empty($userMessage) ? $userMessage : ($attachment ? "Trợ giảng xem giúp mình tệp/hình ảnh '{$attachment['name']}' vừa gửi nhé!" : 'Xin chào trợ giảng!');

        if ($attachment && !empty($attachment['path'])) {
            $filePath = Storage::disk('public')->path($attachment['path']);
            if (file_exists($filePath)) {
                $mime = $attachment['mime'] ?? '';
                $fileName = $attachment['name'] ?? 'file';

                if (str_starts_with($mime, 'image/')) {
                    $fileBytes = file_get_contents($filePath);
                    $base64Image = 'data:' . $mime . ';base64,' . base64_encode($fileBytes);

                    $messages[] = [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $userPrompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $base64Image]],
                        ]
                    ];
                    return $messages;
                } else {
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $textContent = file_get_contents($filePath);
                    $truncatedContent = mb_substr($textContent, 0, 15000);
                    $fileBlock = "\n\n📎 [HỌC TRÒ ĐÍNH KÈM TỆP: {$fileName}]\n```{$ext}\n{$truncatedContent}\n```\n";
                    $userPrompt = $fileBlock . "\n" . $userPrompt;
                }
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt,
        ];

        return $messages;
    }

    /**
     * Gửi tin nhắn đồng bộ thông thường (qua Vilao GPT-6.1-Sol).
     */
    public function sendMessage(AiConversation $conversation, string $userMessage, ?array $attachment = null): AiMessage
    {
        $moderation = app(\App\Services\ContentModerationService::class)->moderate($userMessage, 'chat');
        $displayContent = $moderation['flagged'] ? $moderation['masked_text'] : $userMessage;
        if (empty($displayContent) && $attachment) {
            $displayContent = '📎 [Đã gửi tệp: ' . ($attachment['name'] ?? 'Tệp đính kèm') . ']';
        }

        $userMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'content' => $displayContent,
            'structured_output' => $attachment ? ['attachment' => $attachment] : null,
        ]);

        if ($moderation['flagged']) {
            $warnMsg = AiMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'content' => $moderation['warning'],
            ]);
            $warnMsg->setAttribute('masked_user_message', $displayContent);

            return $warnMsg;
        }

        $aiReplyText = '';
        $messages = $this->buildVilaoMessages($conversation, $userMessage, $attachment, $userMsgRecord->message_id);
        $url = $this->vilaoBaseUrl . '/chat/completions';

        // Chạy theo chuỗi: DeepSeek (Main/Content) -> dự phòng ChatGPT (vgpt/gpt-6.1-sol)
        foreach ($this->resolveProviderChain($conversation, $attachment) as $provider) {
            try {
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $provider['key'],
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'model' => $provider['model'],
                    'messages' => $messages,
                    'max_tokens' => 4096,
                    'temperature' => 0.6,
                ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');
                    if (! empty($content)) {
                        $aiReplyText = $this->cleanAiReply($content);
                        break;
                    }
                } else {
                    Log::warning("{$provider['name']} ({$provider['model']}) HTTP {$response->status()}: " . substr($response->body(), 0, 200));
                }
            } catch (\Throwable $e) {
                Log::warning("{$provider['name']} exception: " . $e->getMessage());
            }
        }

        // Dự phòng cuối cùng sang Google Gemini API nếu cả DeepSeek và ChatGPT đều lỗi
        if (empty($aiReplyText)) {
            $fallbackReply = $this->callGeminiFallback($messages);
            if (! empty($fallbackReply)) {
                $aiReplyText = $this->cleanAiReply($fallbackReply);
            }
        }

        if (empty($aiReplyText)) {
            $aiReplyText = "Chào học trò! Thầy Biết Tuốt đã tiếp nhận câu hỏi của em: \"{$userMessage}\". Thầy đang kiểm tra kết nối với hệ thống AI tri thức, em đợi thầy một lát rồi hỏi lại nhé!";
        }

        // Không chặn phản hồi text để vẽ ảnh đồng bộ: giữ thẻ [IMAGE: ...] để hiện text trước, ảnh tải ngầm (async) sau
        $deferred = $this->prepareDeferredImageTag($aiReplyText, $userMessage);
        $aiReplyText = $deferred['text'];

        $assistantMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'content' => $aiReplyText,
        ]);

        if (! empty($deferred['pending_prompt'])) {
            $assistantMsgRecord->setAttribute('pending_image_prompt', $deferred['pending_prompt']);
        }

        // Cập nhật ngay Trí nhớ cuốn chiếu (Rolling Summary) sau lượt chat này
        try {
            ($this->summarizerService ?? app(VietnameseSummarizerService::class))->updateRollingConversationSummary($conversation);
        } catch (\Throwable $e) {
            Log::warning('Update rolling summary after sendMessage failed: ' . $e->getMessage());
        }

        $this->updateConversationTitle($conversation, $userMessage, $attachment);

        return $assistantMsgRecord;
    }

    /**
     * STREAMING DÒNG DỮ LIỆU TỨC THÌ (Server-Sent Events):
     * - Ưu tiên DeepSeek (`DEEPSEEK_KEY_MAIN` / `DEEPSEEK_KEY_CONTENT`), tự động dự phòng `OPENAI_API_KEY` (`vgpt/gpt-6.1-sol`) rồi Gemini.
     */
    public function streamMessage(AiConversation $conversation, string $userMessage, ?array $attachment = null, ?callable $onChunk = null): AiMessage
    {
        $moderation = app(\App\Services\ContentModerationService::class)->moderate($userMessage, 'chat');
        $displayContent = $moderation['flagged'] ? $moderation['masked_text'] : $userMessage;
        if (empty($displayContent) && $attachment) {
            $displayContent = '📎 [Đã gửi tệp: ' . ($attachment['name'] ?? 'Tệp đính kèm') . ']';
        }

        $userMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'content' => $displayContent,
            'structured_output' => $attachment ? ['attachment' => $attachment] : null,
        ]);

        if ($moderation['flagged']) {
            if ($onChunk) {
                $onChunk($moderation['warning']);
            }

            $warnMsg = AiMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => 'assistant',
                'content' => $moderation['warning'],
            ]);
            $warnMsg->setAttribute('masked_user_message', $displayContent);

            return $warnMsg;
        }

        $messages = $this->buildVilaoMessages($conversation, $userMessage, $attachment, $userMsgRecord->message_id);
        $url = $this->vilaoBaseUrl . '/chat/completions';

        $totalText = '';
        $streamSuccess = false;

        foreach ($this->resolveProviderChain($conversation, $attachment) as $provider) {
            $totalText = '';
            $lineBuf = '';
            $payloadJson = json_encode([
                'model' => $provider['model'],
                'messages' => $messages,
                'max_tokens' => 4096,
                'temperature' => 0.6,
                'stream' => true,
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $provider['key'],
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
            curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 20);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$totalText, &$lineBuf, $onChunk) {
                $lineBuf .= $data;
                while (($pos = strpos($lineBuf, "\n")) !== false) {
                    $line = trim(substr($lineBuf, 0, $pos));
                    $lineBuf = substr($lineBuf, $pos + 1);
                    if ($line === '' || $line === 'data: [DONE]') {
                        continue;
                    }
                    if (str_starts_with($line, 'data: ')) {
                        $json = json_decode(substr($line, 6), true);
                        $delta = $json['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '') {
                            $totalText .= $delta;
                            if ($onChunk) {
                                $onChunk($delta);
                            }
                        }
                    }
                }
                return strlen($data);
            });

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && ! empty($totalText)) {
                $streamSuccess = true;
                break;
            }

            Log::warning("{$provider['name']} ({$provider['model']}) streaming failed with HTTP {$httpCode}. Trying next fallback...");
        }

        if (! $streamSuccess || empty($totalText)) {
            $fallbackReply = $this->callGeminiFallback($messages);
            if (! empty($fallbackReply)) {
                $totalText = $this->cleanAiReply($fallbackReply);
            } else {
                $totalText = "Chào học trò! Thầy Biết Tuốt đã tiếp nhận câu hỏi của em: \"{$userMessage}\". Hiện tại kết nối đến cổng tri thức của thầy đang được tối ưu, em thử bấm gửi lại nhé!";
            }
            if ($onChunk) {
                $onChunk($totalText);
            }
        } else {
            $totalText = $this->cleanAiReply($totalText);
        }

        // Không chặn stream để vẽ ảnh đồng bộ: giữ nguyên vị trí thẻ [IMAGE: ...] để hiện text trước, ảnh vẽ ngầm (async) sau
        $deferred = $this->prepareDeferredImageTag($totalText, $userMessage);
        if ($deferred['appended_tag'] !== '') {
            $totalText = $deferred['text'];
            if ($onChunk) {
                $onChunk($deferred['appended_tag']);
            }
        }

        $assistantMsgRecord = AiMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'content' => $totalText,
        ]);

        if (! empty($deferred['pending_prompt'])) {
            $assistantMsgRecord->setAttribute('pending_image_prompt', $deferred['pending_prompt']);
        }

        // Cập nhật ngay Trí nhớ cuốn chiếu (Rolling Summary) sau lượt chat này
        try {
            ($this->summarizerService ?? app(VietnameseSummarizerService::class))->updateRollingConversationSummary($conversation);
        } catch (\Throwable $e) {
            Log::warning('Update rolling summary after streamMessage failed: ' . $e->getMessage());
        }

        $this->updateConversationTitle($conversation, $userMessage, $attachment);

        return $assistantMsgRecord;
    }

    /**
     * Chuẩn bị thẻ [IMAGE: ...] trong 0ms (KHÔNG gọi API ảnh trong luồng stream text).
     * Trả về text kèm thẻ ở đúng vị trí và prompt ảnh chờ vẽ ngầm.
     */
    private function prepareDeferredImageTag(string $replyText, string $userMessage): array
    {
        $generator = $this->imageGenerator ?? app(AiImageGeneratorService::class);
        if (! $generator->isConfigured() || trim($replyText) === '') {
            return ['text' => $replyText, 'appended_tag' => '', 'pending_prompt' => null];
        }

        // Trường hợp 1: AI đã tự đặt thẻ [IMAGE: mô tả] vào đúng vị trí trong bài viết
        if (preg_match('/\[IMAGE:\s*([^\]]+)\]/i', $replyText, $m)) {
            return [
                'text' => $replyText,
                'appended_tag' => '',
                'pending_prompt' => trim($m[1]),
            ];
        }

        // Trường hợp 2: Sinh viên chủ động yêu cầu tạo/vẽ/xem ảnh minh họa
        if (preg_match('/(tạo ảnh|vẽ ảnh|vẽ hình|tạo hình|ảnh minh họa|hình minh họa|cho xem ảnh|cho xem hình|minh họa bằng ảnh|generate image)/iu', $userMessage)) {
            $prompt = 'Clean modern educational illustration for university and college students: ' . mb_substr($userMessage, 0, 220);
            $tag = "\n\n[IMAGE: {$prompt}]";

            return [
                'text' => $replyText . $tag,
                'appended_tag' => $tag,
                'pending_prompt' => $prompt,
            ];
        }

        return ['text' => $replyText, 'appended_tag' => '', 'pending_prompt' => null];
    }

    /**
     * Sinh ảnh bất đồng bộ (Async) cho tin nhắn đang có thẻ [IMAGE: ...], cập nhật luôn vào DB để F5 vẫn còn ảnh.
     */
    public function generateDeferredImageForMessage(AiMessage $message): ?string
    {
        $content = (string) $message->content;
        if (! preg_match('/\[IMAGE:\s*([^\]]+)\]/i', $content, $m)) {
            return null;
        }

        $generator = $this->imageGenerator ?? app(AiImageGeneratorService::class);
        if (! $generator->isConfigured()) {
            $message->update(['content' => trim(str_replace($m[0], '', $content))]);
            return null;
        }

        $imgUrl = $generator->generateIllustration(trim($m[1]));
        if ($imgUrl) {
            $updated = str_replace($m[0], "\n\n![Hình ảnh minh họa AI]({$imgUrl})\n", $content);
            $message->update(['content' => $updated]);
            return $imgUrl;
        }

        // Nếu API ảnh lỗi thì gỡ thẻ thô khỏi DB cho gọn
        $message->update(['content' => trim(str_replace($m[0], '', $content))]);
        return null;
    }

    /**
     * Nếu học sinh yêu cầu tạo ảnh minh họa hoặc AI gắn thẻ `[IMAGE: ...]`, gọi IMAGE_API_KEY (bh5/gemini-3.1-flash-lite-image).
     */
    private function attachGeneratedImageIfNeeded(string $replyText, string $userMessage): string
    {
        $generator = $this->imageGenerator ?? app(AiImageGeneratorService::class);
        if (! $generator->isConfigured() || trim($replyText) === '') {
            return $replyText;
        }

        // Trường hợp 1: AI chèn thẻ [IMAGE: mô tả]
        if (preg_match('/\[IMAGE:\s*([^\]]+)\]/i', $replyText, $m)) {
            $imgUrl = $generator->generateIllustration(trim($m[1]));
            if ($imgUrl) {
                return str_replace($m[0], "\n\n![Hình ảnh minh họa AI]({$imgUrl})\n", $replyText);
            }
            return str_replace($m[0], '', $replyText);
        }

        // Trường hợp 2: Học sinh chủ động yêu cầu tạo/vẽ ảnh minh họa (và chưa có sơ đồ mermaid)
        if (preg_match('/(tạo ảnh|vẽ ảnh|vẽ hình minh họa|tạo hình minh họa|generate image)/iu', $userMessage)) {
            $imgUrl = $generator->generateIllustration(
                'Clean modern educational illustration for university and college students: ' . mb_substr($userMessage, 0, 220)
            );
            if ($imgUrl) {
                return $replyText . "\n\n![Hình ảnh minh họa AI]({$imgUrl})";
            }
        }

        return $replyText;
    }

    /**
     * Truy xuất ngữ cảnh học liệu bằng Hybrid RAG:
     * Kết hợp Dense Vector (AITeamVN/Vietnamese_Embedding_v2) + Lexical Keyword Matching + Kho tri thức BTEC.
     */
    private function retrieveHybridContext(AiConversation $conversation, string $userMessage): ?string
    {
        $query = trim($userMessage);
        $cleanQuery = mb_strtolower($query);
        $terms = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanQuery)));

        $materialsQuery = Material::query();
        if ($conversation->subject_id) {
            $materialsQuery->where('subject_id', $conversation->subject_id);
        }
        $materials = $materialsQuery->take(20)->get();

        $summarizer = $this->summarizerService ?? app(VietnameseSummarizerService::class);
        $lexicalCandidates = [];

        // Giai đoạn 1 (0ms): Chấm điểm Lexical trên toàn bộ học liệu (có cache nội dung bóc tách .docx/.pdf 24h)
        foreach ($materials as $m) {
            $textCacheKey = 'mat_plain_' . $m->material_id . '_' . md5((string) ($m->file_path ?? '') . '|' . (string) ($m->updated_at ?? ''));
            $fileBody = cache()->remember($textCacheKey, 86400, fn () => $summarizer->extractMaterialPlainText($m, 4000));
            $fullDocText = trim("Tiêu đề: {$m->title}\nMô tả: {$m->description}" . ($fileBody !== '' ? "\nNội dung tài liệu:\n{$fileBody}" : ''));
            $lowerDoc = mb_strtolower($fullDocText);

            $lexicalScore = ($conversation->subject_id && $m->subject_id === $conversation->subject_id) ? 10.0 : 0.0;
            if ($cleanQuery !== '' && str_contains($lowerDoc, $cleanQuery)) {
                $lexicalScore += 40.0;
            }
            foreach ($terms as $term) {
                if (mb_strlen($term) >= 2 && str_contains($lowerDoc, $term)) {
                    $lexicalScore += 6.0;
                }
            }

            if ($lexicalScore > 0.0) {
                $lexicalCandidates[] = [
                    'material' => $m,
                    'full_text' => $fullDocText,
                    'raw_len' => mb_strlen($fullDocText),
                    'lexical' => $lexicalScore,
                ];
            }
        }

        // Chỉ rerank vector trên Top 4 ứng viên sát nhất; nếu Lexical đã khớp rõ (>= 16đ) thì bỏ qua gọi Embedding mạng để giảm 5-7s TTFT
        usort($lexicalCandidates, fn ($a, $b) => $b['lexical'] <=> $a['lexical']);
        $topForVector = array_slice($lexicalCandidates, 0, 4);
        $topLexicalScore = $topForVector[0]['lexical'] ?? 0.0;

        $queryVector = (! empty($topForVector) && $topLexicalScore < 16.0 && $query !== '' && $this->embeddingService?->isConfigured())
            ? $this->embeddingService->embed($query, 'RETRIEVAL_QUERY')
            : null;

        $scoredMaterials = [];
        foreach ($topForVector as $cand) {
            $vectorScore = 0.0;
            if ($queryVector !== null) {
                $docVec = $this->embeddingService->embed(mb_substr($cand['full_text'], 0, 1500), 'RETRIEVAL_DOCUMENT');
                if ($docVec !== null) {
                    $vectorScore = 100.0 * max(0.0, GeminiEmbeddingService::cosineSimilarity($queryVector, $docVec));
                }
            }

            $scoredMaterials[] = [
                'material' => $cand['material'],
                'full_text' => $cand['full_text'],
                'raw_len' => $cand['raw_len'],
                'score' => $vectorScore + 0.5 * $cand['lexical'],
            ];
        }

        usort($scoredMaterials, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topScored = array_slice($scoredMaterials, 0, 3);

        // Dùng bản tóm gọn nếu đã có sẵn trong cache, không gọi thêm LLM chặn luồng chat trực tiếp
        $topMaterialTexts = [];
        foreach ($topScored as $entry) {
            $m = $entry['material'];
            $body = $entry['full_text'];

            if ($entry['raw_len'] > 700) {
                $cachedSum = cache()->get('vi_sum_material_' . $m->material_id);
                if (is_array($cachedSum) && ! empty($cachedSum['summary'])) {
                    $body = "Tiêu đề: {$m->title}\n" . mb_substr($cachedSum['summary'], 0, 900);
                } else {
                    $body = mb_substr($body, 0, 900);
                }
            }

            $topMaterialTexts[] = $body;
        }

        // 3. Chỉ ghép thêm Tri thức quy chế BTEC khi câu hỏi có liên quan đến quy chế / học vụ (tránh nhiễu kiến thức môn học)
        $isPolicyQuery = (bool) preg_match('/(quy chế|điểm danh|vắng|trượt môn|20%|học lại|bảo lưu|phúc khảo|học phí|tín chỉ|btec|pass|merit|distinction)/iu', $query);
        if ($query !== '' && $this->ragService !== null && ($isPolicyQuery || empty($topMaterialTexts))) {
            $ragResult = $this->ragService->search($query);
            foreach (array_slice($ragResult['top_chunks'] ?? [], 0, 2) as $chunk) {
                $topMaterialTexts[] = "[Tri thức quy chế - {$chunk['title']}]\n" . mb_substr($chunk['text'], 0, 600);
            }
        }

        return ! empty($topMaterialTexts) ? implode("\n---\n", $topMaterialTexts) : null;
    }

    /**
     * Dự phòng gọi Google Gemini API nếu Vilao API gặp lỗi hoặc hết quota.
     */
    private function callGeminiFallback(array $messages): ?string
    {
        if (empty($this->geminiApiKey)) {
            return null;
        }

        $systemText = '';
        $contents = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? '') === 'system') {
                $systemText = is_string($m['content']) ? $m['content'] : '';
                continue;
            }
            $text = is_string($m['content']) ? $m['content'] : ($m['content'][0]['text'] ?? '');
            if ($text !== '') {
                $contents[] = [
                    'role' => ($m['role'] === 'assistant') ? 'model' : 'user',
                    'parts' => [['text' => $text]],
                ];
            }
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent";
            $response = Http::timeout(20)->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->geminiApiKey,
            ])->post($url, [
                'system_instruction' => ['parts' => [['text' => $systemText]]],
                'contents' => $contents,
                'generationConfig' => ['temperature' => 0.5, 'maxOutputTokens' => 1024],
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text');
            }
            Log::warning('Gemini fallback error: ' . $response->status());
        } catch (\Throwable $e) {
            Log::warning('Gemini fallback exception: ' . $e->getMessage());
        }

        return null;
    }

    private function updateConversationTitle(AiConversation $conversation, string $userMessage, ?array $attachment = null): void
    {
        if ($conversation->title === 'Cuộc trò chuyện mới') {
            $rawTitle = !empty($userMessage) ? $userMessage : ($attachment['name'] ?? 'Hỏi thầy Biết Tuốt');
            $shortTitle = mb_substr($rawTitle, 0, 45);
            if (mb_strlen($rawTitle) > 45) {
                $shortTitle .= '...';
            }
            $conversation->update(['title' => $shortTitle]);
        } else {
            $conversation->touch();
        }
    }
}
