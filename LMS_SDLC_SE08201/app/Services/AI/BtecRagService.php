<?php

namespace App\Services\AI;

use App\Models\User;

/**
 * BTEC FPT RAG SERVICE
 * Quản lý kho tri thức (Knowledge Base) và thuật ngữ quy chế BTEC FPT + Hướng dẫn ứng dụng Smart LMS.
 */
class BtecRagService
{
    private array $sources = [];
    private array $chunks = [];
    private array $procedures = [];
    private array $faqs = [];
    private array $appFeatures = [];

    public function __construct()
    {
        $this->loadKnowledgeBase();
        $this->initAppFeatures();
    }

    private function loadKnowledgeBase(): void
    {
        $kbDir = storage_path('btec_ai_kb');

        // 1. Tải danh mục nguồn chính thức
        if (file_exists("$kbDir/sources.json")) {
            $raw = json_decode(file_get_contents("$kbDir/sources.json"), true);
            foreach ($raw ?: [] as $s) {
                $this->sources[$s['source_id']] = $s;
            }
        }

        // 2. Tải các đoạn tri thức (kb_chunks.jsonl)
        if (file_exists("$kbDir/kb_chunks.jsonl")) {
            $handle = fopen("$kbDir/kb_chunks.jsonl", "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $c = json_decode($line, true);
                    if ($c) {
                        $this->chunks[] = $c;
                    }
                }
                fclose($handle);
            }
        }

        // 3. Tải quy trình thủ tục (procedures.json)
        if (file_exists("$kbDir/procedures.json")) {
            $this->procedures = json_decode(file_get_contents("$kbDir/procedures.json"), true) ?: [];
        }

        // 4. Tải FAQ (faq_seed.json)
        if (file_exists("$kbDir/faq_seed.json")) {
            $this->faqs = json_decode(file_get_contents("$kbDir/faq_seed.json"), true) ?: [];
        }
    }

    /**
     * Khởi tạo bản đồ tri thức ứng dụng Smart LMS (App Knowledge Base).
     */
    private function initAppFeatures(): void
    {
        $this->appFeatures = [
            [
                'feature_id' => 'APP_SCHEDULE',
                'name' => 'Thời khóa biểu & Lịch học',
                'route' => '/student/schedule',
                'icon' => '🗓️',
                'keywords' => ['lịch học', 'thời khóa biểu', 'ca học', 'phòng học', 'lịch tuần', 'tkb', 'hôm nay học gì'],
                'description' => 'Xem toàn bộ lịch học theo tuần và theo năm. Cho biết ca học (Ca 1 đến Ca 4), phòng học cụ thể và giảng viên đứng lớp.',
                'steps' => [
                    'Bấm vào mục "🗓️ Thời khóa biểu" trên thanh menu bên trái (hoặc bấm nút mở bên dưới).',
                    'Chọn Năm học và Tuần học cần xem ở thanh trên cùng.',
                    'Quan sát bảng lịch học từ Thứ 2 đến Chủ nhật, nhấp vào từng ô để xem chi tiết phòng học và ca học.'
                ]
            ],
            [
                'feature_id' => 'APP_ATTENDANCE',
                'name' => 'Lịch sử Điểm danh & Chuyên cần',
                'route' => '/student/attendance',
                'icon' => '✅',
                'keywords' => ['điểm danh', 'vắng', 'chuyên cần', 'nghỉ học', 'vắng mặt', 'muộn', 'tỷ lệ chuyên cần', 'cấm thi'],
                'description' => 'Xem lịch sử điểm danh chi tiết từng buổi học, thống kê Có mặt (P), Vắng (A), Muộn (L) và tỷ lệ chuyên cần tổng hợp.',
                'steps' => [
                    'Bấm vào mục "✅ Lịch sử điểm danh" trên thanh menu bên trái.',
                    'Xem 4 thẻ thống kê ở đầu trang (Tổng số buổi, Có mặt, Vắng mặt, Tỷ lệ chuyên cần).',
                    'Có thể dùng ô chọn "Lọc theo Lớp học" để kiểm tra riêng từng môn hoặc xem tổng hợp toàn khóa.'
                ]
            ],
            [
                'feature_id' => 'APP_ASSIGNMENTS',
                'name' => 'Bài tập & Quiz trực tuyến',
                'route' => '/student/assignments',
                'icon' => '📝',
                'keywords' => ['bài tập', 'assignment', 'asm', 'quiz', 'trắc nghiệm', 'nộp bài', 'hạn nộp', 'deadline', 'nộp bài tập'],
                'description' => 'Xem danh sách bài tập được giao, thời hạn nộp bài, làm bài trắc nghiệm (Quiz) và nộp tệp bài làm tự luận.',
                'steps' => [
                    'Truy cập mục "📝 Bài tập & Quiz" trên menu.',
                    'Chọn bài tập cần làm: Nếu là Quiz bấm "Làm bài trắc nghiệm", nếu là Assignment bấm "Xem chi tiết".',
                    'Đính kèm tệp bài làm (Word, PDF, ZIP...) hoặc điền câu trả lời và nhấn nút "Nộp bài".'
                ]
            ],
            [
                'feature_id' => 'APP_GRADES',
                'name' => 'Bảng điểm & Kết quả học tập',
                'route' => '/student/grades',
                'icon' => '🏆',
                'keywords' => ['bảng điểm', 'điểm số', 'kết quả', 'gpa', 'điểm tổng kết', 'điểm thi', 'điểm môn'],
                'description' => 'Xem điểm quá trình, điểm assignment, điểm chuyên cần và điểm tổng kết của tất cả các môn học đã tham gia.',
                'steps' => [
                    'Nhấp vào mục "🏆 Bảng điểm" trên thanh menu.',
                    'Xem bảng tổng hợp điểm theo từng học kỳ và môn học.',
                    'Quan sát cột Trạng thái để biết môn học đã Đạt (Passed) hay Cần thi lại/Học lại (Retake).'
                ]
            ],
            [
                'feature_id' => 'APP_MATERIALS',
                'name' => 'Kho Học liệu & Giáo trình',
                'route' => '/student/materials',
                'icon' => '📚',
                'keywords' => ['học liệu', 'tài liệu', 'giáo trình', 'slide', 'bài giảng', 'sách', 'tải tài liệu'],
                'description' => 'Tải xuống slide bài giảng, đề cương chi tiết và tài liệu học tập chính thức do giảng viên bộ môn cung cấp.',
                'steps' => [
                    'Mở mục "📚 Học liệu" từ menu.',
                    'Chọn môn học cần tải tài liệu.',
                    'Nhấn vào nút "Tải về" cạnh mỗi giáo trình hoặc slide bài giảng.'
                ]
            ],
            [
                'feature_id' => 'APP_PROFILE',
                'name' => 'Hồ sơ cá nhân & Đổi mật khẩu',
                'route' => '/profile',
                'icon' => '👤',
                'keywords' => ['đổi mật khẩu', 'mật khẩu', 'password', 'hồ sơ', 'thông tin cá nhân', 'avatar', 'tài khoản'],
                'description' => 'Xem thông tin sinh viên, mã số sinh viên, cập nhật ảnh đại diện và thay đổi mật khẩu đăng nhập an toàn.',
                'steps' => [
                    'Chọn "👤 Hồ sơ cá nhân" ở góc dưới thanh menu bên trái.',
                    'Cuộn xuống phần "Cập nhật mật khẩu".',
                    'Nhập mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu mới, sau đó nhấn "Lưu thay đổi".'
                ]
            ],
            [
                'feature_id' => 'APP_AI_TUTOR',
                'name' => 'MR. BIẾT TUỐT (Gia sư học tập)',
                'route' => '/student/ai-tutor',
                'icon' => '🧙‍♂️',
                'keywords' => ['mr biết tuốt', 'thầy biết tuốt', 'ai tutor', 'gia sư', 'hỏi bài', 'soi code', 'luyện tập'],
                'description' => 'Gia sư AI học thuật MR. BIẾT TUỐT hỗ trợ giải thích bản chất kiến thức lập trình, gợi ý hướng giải bài tập và soi lỗi code 24/7.',
                'steps' => [
                    'Nhấp vào mục "🧙‍♂️ MR. BIẾT TUỐT" trên menu.',
                    'Bấm "Cuộc trò chuyện mới", chọn môn học và chế độ cần hỗ trợ (Giải thích, Hướng dẫn bài tập, Soi lỗi code, Luyện tập).',
                    'Nhập câu hỏi hoặc đính kèm ảnh/file code (tối đa 5MB) để Thầy hướng dẫn.'
                ]
            ],
            [
                'feature_id' => 'APP_NOTIFICATIONS',
                'name' => 'Thông báo nhà trường',
                'route' => '/student/notifications',
                'icon' => '🔔',
                'keywords' => ['thông báo', 'tin tức', 'lịch thi', 'nhắc nhở'],
                'description' => 'Xem toàn bộ các thông báo mới từ Phòng Đào tạo, Phòng CTSV và nhắc nhở bài tập sắp hạn.',
                'steps' => [
                    'Nhấp vào biểu tượng "🔔 Thông báo" trên thanh menu.',
                    'Bấm vào từng thông báo để đọc chi tiết nội dung và các mốc thời gian quan trọng.'
                ]
            ]
        ];
    }

    /**
     * Nhận diện ý định (Intent Classification).
     */
    public function classifyIntent(string $query): string
    {
        $q = mb_strtolower($query);

        // Escalation / Human support
        if (str_contains($q, 'gặp nhân viên') || str_contains($q, 'gặp người') || str_contains($q, 'khiếu nại') || str_contains($q, 'phúc tra') || str_contains($q, 'sai điểm') || str_contains($q, 'hỗ trợ trực tiếp') || str_contains($q, 'tạo ticket')) {
            return 'HUMAN_SUPPORT';
        }

        // App Navigation & Troubleshooting
        foreach ($this->appFeatures as $feat) {
            foreach ($feat['keywords'] as $kw) {
                if (str_contains($q, $kw)) {
                    if (str_contains($q, 'làm sao') || str_contains($q, 'ở đâu') || str_contains($q, 'xem thế nào') || str_contains($q, 'hướng dẫn') || str_contains($q, 'đổi mật khẩu') || str_contains($q, 'trên app') || str_contains($q, 'chỗ nào')) {
                        return 'APP_NAVIGATION';
                    }
                }
            }
        }

        if (str_contains($q, 'lỗi') || str_contains($q, 'không vào được') || str_contains($q, 'không đăng nhập') || str_contains($q, 'quên mật khẩu') || str_contains($q, 'không tải được')) {
            return 'APP_TROUBLESHOOTING';
        }

        // Financial
        if (str_contains($q, 'học phí') || str_contains($q, 'đóng tiền') || str_contains($q, 'ngân hàng') || str_contains($q, 'chuyển khoản') || str_contains($q, 'hoàn phí') || str_contains($q, 'học bổng') || str_contains($q, 'nợ phí') || str_contains($q, 'tiền học')) {
            return 'FINANCIAL_SUPPORT';
        }

        // Academic & Assignment
        if (str_contains($q, 'assignment') || str_contains($q, 'asm') || str_contains($q, 'đạo văn') || str_contains($q, 'nộp muộn') || str_contains($q, 'resubmission') || str_contains($q, 'retake') || str_contains($q, 'thi lại') || str_contains($q, 'học lại')) {
            return 'ACADEMIC_SUPPORT';
        }

        // Attendance & Regulations
        if (str_contains($q, 'nghỉ') || str_contains($q, 'cấm thi') || str_contains($q, 'chuyên cần') || str_contains($q, 'điểm danh') || str_contains($q, '20%') || str_contains($q, 'bảo lưu') || str_contains($q, 'thôi học') || str_contains($q, 'nội quy') || str_contains($q, 'đồng phục')) {
            return 'REGULATION_QUESTION';
        }

        // Admission
        if (str_contains($q, 'tuyển sinh') || str_contains($q, 'nhập học') || str_contains($q, 'xét tuyển') || str_contains($q, 'ielts') || str_contains($q, 'hồ sơ')) {
            return 'ADMISSION_SUPPORT';
        }

        // Contact
        if (str_contains($q, 'phòng đào tạo') || str_contains($q, 'ctsv') || str_contains($q, 'liên hệ') || str_contains($q, 'số điện thoại') || str_contains($q, 'email')) {
            return 'STUDENT_SERVICE';
        }

        return 'GENERAL_QUESTION';
    }

    /**
     * Tìm kiếm Hybrid Search kết hợp: Knowledge Base + Procedures + App Knowledge.
     */
    public function search(string $query, ?User $student = null): array
    {
        $intent = $this->classifyIntent($query);
        $cleanQuery = mb_strtolower(trim($query));
        $terms = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanQuery)));

        // 1. Tìm trong App Features
        $matchedAppFeatures = [];
        foreach ($this->appFeatures as $feat) {
            $score = 0;
            foreach ($feat['keywords'] as $kw) {
                if (str_contains($cleanQuery, $kw)) {
                    $score += 15;
                }
            }
            if ($score > 0) {
                $matchedAppFeatures[] = ['feature' => $feat, 'score' => $score];
            }
        }
        usort($matchedAppFeatures, fn ($a, $b) => $b['score'] <=> $a['score']);

        // 2. Tìm trong Knowledge Chunks
        $matchedChunks = [];
        foreach ($this->chunks as $c) {
            $score = 0;
            $text = mb_strtolower($c['title'] . ' ' . $c['text']);

            // Trùng cụm từ nguyên vẹn
            if (str_contains($text, $cleanQuery)) {
                $score += 40;
            }

            // Trùng từng từ khóa
            foreach ($terms as $term) {
                if (mb_strlen($term) >= 2 && str_contains($text, $term)) {
                    $score += 5;
                }
            }

            // Boost theo intent
            if ($intent === 'REGULATION_QUESTION' && in_array($c['topic_id'], ['T07', 'T08', 'T13', 'T14'])) $score += 20;
            if ($intent === 'FINANCIAL_SUPPORT' && in_array($c['topic_id'], ['T05', 'T06'])) $score += 20;
            if ($intent === 'ACADEMIC_SUPPORT' && in_array($c['topic_id'], ['T09', 'T10', 'T11', 'T12'])) $score += 20;
            if ($intent === 'ADMISSION_SUPPORT' && in_array($c['topic_id'], ['T03', 'T04'])) $score += 20;
            if ($intent === 'STUDENT_SERVICE' && in_array($c['topic_id'], ['T02', 'T16', 'T17'])) $score += 20;

            if ($score > 0) {
                $matchedChunks[] = ['chunk' => $c, 'score' => $score];
            }
        }
        usort($matchedChunks, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topChunks = array_slice(array_column($matchedChunks, 'chunk'), 0, 4);

        // 3. Tìm trong Procedures
        $matchedProcedures = [];
        foreach ($this->procedures as $proc) {
            $score = 0;
            $pText = mb_strtolower($proc['title'] . ' ' . implode(' ', $proc['suggested_steps'] ?? []));
            foreach ($terms as $term) {
                if (mb_strlen($term) >= 2 && str_contains($pText, $term)) {
                    $score += 5;
                }
            }
            if ($score > 0) {
                $matchedProcedures[] = ['proc' => $proc, 'score' => $score];
            }
        }
        usort($matchedProcedures, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topProcedures = array_slice(array_column($matchedProcedures, 'proc'), 0, 2);

        // 4. Thu thập nguồn trích dẫn (Citations)
        $citations = [];
        $collectedSourceIds = [];
        foreach ($topChunks as $c) {
            foreach ($c['source_refs'] ?? [] as $sId) {
                if (!in_array($sId, $collectedSourceIds) && isset($this->sources[$sId])) {
                    $collectedSourceIds[] = $sId;
                    $src = $this->sources[$sId];
                    $citations[] = [
                        'source_id' => $src['source_id'],
                        'title' => $src['title'],
                        'url' => $src['url'] ?? '',
                        'class' => $src['source_class'] ?? 'btec_public',
                    ];
                }
            }
        }

        // 5. Chuẩn bị các nút hành động (App Navigation Actions)
        $actions = [];
        if (!empty($matchedAppFeatures)) {
            $bestFeature = $matchedAppFeatures[0]['feature'];
            $actions[] = [
                'label' => 'Mở màn hình ' . $bestFeature['name'],
                'url' => $bestFeature['route'],
                'icon' => $bestFeature['icon'],
            ];
        }

        return [
            'intent' => $intent,
            'top_chunks' => $topChunks,
            'top_procedures' => $topProcedures,
            'top_app_feature' => !empty($matchedAppFeatures) ? $matchedAppFeatures[0]['feature'] : null,
            'citations' => $citations,
            'actions' => $actions,
            'needs_escalation' => in_array($intent, ['HUMAN_SUPPORT']) || str_contains($cleanQuery, 'khiếu nại') || str_contains($cleanQuery, 'phúc tra'),
        ];
    }
}

