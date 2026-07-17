<?php

namespace App\Services\Teacher;

use Illuminate\Support\Facades\Http;

class AiQuizService
{
    /**
     * Call Gemini API to generate questions
     */
    public function generateQuestions(string $topic, int $quantity, ?string $referenceDocumentPath = null, ?string $originalExtension = null): array
    {
        $docText = "";
        if (!empty($referenceDocumentPath) && file_exists($referenceDocumentPath)) {
            $docText = $this->extractTextFromFile($referenceDocumentPath, $originalExtension);
        }
        
        $docText = mb_scrub($docText, 'UTF-8');
        $topic = mb_scrub($topic, 'UTF-8');

        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            if (!empty($docText)) {
                return $this->generateQuestionsFromDocFallback($docText, $topic, $quantity);
            }
            return $this->generateMockQuestions($topic, $quantity);
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';

        $prompt = "Bạn là một giảng viên xuất sắc. Hãy tạo ra {$quantity} câu hỏi trắc nghiệm về chủ đề: '{$topic}'.";
        
        if (!empty($docText)) {
            $prompt .= "\n\nTÀI LIỆU THAM KHẢO ĐƯỢC CUNG CẤP (Hãy tạo các câu hỏi bám sát vào nội dung tài liệu này):\n\"\"\"\n" . mb_substr($docText, 0, 10000) . "\n\"\"\"";
        }

        $prompt .= "\n\nYêu cầu BẮT BUỘC: Bạn phải trả về nội dung dưới dạng một mảng JSON nguyên gốc (không dùng markdown backticks, không kèm giải thích hay văn bản thừa nào cả).
        Mỗi object trong mảng JSON phải có cấu trúc chính xác như sau:
        {
            \"content\": \"Nội dung câu hỏi\",
            \"option_a\": \"Đáp án A\",
            \"option_b\": \"Đáp án B\",
            \"option_c\": \"Đáp án C\",
            \"option_d\": \"Đáp án D\",
            \"correct_answer\": \"A hoặc B hoặc C hoặc D\"
        }";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
            ]
        ];

        $maxRetries = 3;
        $retryDelay = 1000; // in milliseconds
        $response = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey
                ])->post($url, $payload);

                if ($response->successful()) {
                    break;
                }

                // If transient server overloaded (503) or rate limit (429), wait and retry
                if (($response->status() === 503 || $response->status() === 429) && $attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                    $retryDelay = (int)($retryDelay * 1.5);
                    continue;
                }

                break;
            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                usleep($retryDelay * 1000);
                $retryDelay = (int)($retryDelay * 1.5);
            }
        }

        if (!$response || !$response->successful()) {
            $errorMsg = $response ? $response->body() : 'Không nhận được phản hồi từ máy chủ AI.';
            throw new \Exception('Lỗi khi gọi API tới AI: ' . $errorMsg);
        }

        $result = $response->json();
        
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Extract pure JSON array block robustly
        $startPos = strpos($text, '[');
        $endPos = strrpos($text, ']');
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $text = substr($text, $startPos, $endPos - $startPos + 1);
        } else {
            // Remove markdown formatting if AI still outputs it without standard array format
            $text = str_replace(['```json', '```'], '', $text);
            $text = trim($text);
        }

        $questions = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('AI trả về dữ liệu không đúng định dạng JSON: ' . json_last_error_msg() . '. Dữ liệu gốc: ' . substr($text, 0, 500));
        }

        return $this->cleanUtf8($questions);
    }

    /**
     * Fallback mock question generator to ensure robust offline testing
     */
    private function generateMockQuestions(string $topic, int $quantity): array
    {
        $topicLower = mb_strtolower($topic);
        $pool = [];
        
        if (str_contains($topicLower, 'bài hát') || str_contains($topicLower, 'nhạc') || str_contains($topicLower, 'âm nhạc') || str_contains($topicLower, 'ca sĩ')) {
            $pool = [
                [
                    "content" => "Ai là tác giả của ca khúc Tiến Quân Ca (Quốc ca nước Cộng hòa xã hội chủ nghĩa Việt Nam)?",
                    "option_a" => "Phạm Tuyên",
                    "option_b" => "Văn Cao",
                    "option_c" => "Trịnh Công Sơn",
                    "option_d" => "Đỗ Nhuận",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Ca sĩ nào thể hiện bài hát 'Em của ngày hôm qua' và là chủ nhân của album 'Chúng ta của hiện tại'?",
                    "option_a" => "Sơn Tùng M-TP",
                    "option_b" => "Soobin Hoàng Sơn",
                    "option_c" => "Noo Phước Thịnh",
                    "option_d" => "Jack",
                    "correct_answer" => "A"
                ],
                [
                    "content" => "Ca khúc nổi tiếng 'Nhật ký của mẹ' là sáng tác của nhạc sĩ nào?",
                    "option_a" => "Nguyễn Văn Chung",
                    "option_b" => "Phan Mạnh Quỳnh",
                    "option_c" => "Khắc Việt",
                    "option_d" => "Mr. Siro",
                    "correct_answer" => "A"
                ],
                [
                    "content" => "Nhạc sĩ Trịnh Công Sơn nổi tiếng với loạt tác phẩm nào sau đây?",
                    "option_a" => "Cát bụi, Diễm xưa, Còn tuổi nào cho em",
                    "option_b" => "Lạc trôi, Chạy ngay đi, Nơi này có anh",
                    "option_c" => "Hà Nội mùa vắng những cơn mưa, Im lặng đêm Hà Nội",
                    "option_d" => "Tình ca, Trường sơn đông Trường sơn tây",
                    "correct_answer" => "A"
                ],
                [
                    "content" => "Bài hát thiếu nhi quốc dân 'Cho con' (Ba sẽ là cánh chim, đưa con đi thật xa...) là sáng tác của nhạc sĩ nào?",
                    "option_a" => "Phạm Tuyên",
                    "option_b" => "Trần Long Ẩn",
                    "option_c" => "Phạm Trọng Cầu",
                    "option_d" => "Hoàng Vân",
                    "correct_answer" => "C"
                ]
            ];
        } elseif (str_contains($topicLower, 'lịch sử') || str_contains($topicLower, 'sử')) {
            $pool = [
                [
                    "content" => "Ai là người lãnh đạo trận chiến sông Bạch Đằng năm 938 đại bại quân Nam Hán?",
                    "option_a" => "Lý Thường Kiệt",
                    "option_b" => "Ngô Quyền",
                    "option_c" => "Trần Hưng Đạo",
                    "option_d" => "Lê Lợi",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Chiến dịch Điện Biên Phủ giành thắng lợi hoàn toàn vào năm nào?",
                    "option_a" => "1945",
                    "option_b" => "1954",
                    "option_c" => "1968",
                    "option_d" => "1975",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Ai là vị hoàng đế đầu tiên của nước Việt Nam sau khi dẹp loạn 12 sứ quân?",
                    "option_a" => "Đinh Bộ Lĩnh",
                    "option_b" => "Lê Hoàn",
                    "option_c" => "Lý Thái Tổ",
                    "option_d" => "Ngô Quyền",
                    "correct_answer" => "A"
                ],
                [
                    "content" => "Anh hùng Tây Sơn Nguyễn Huệ lên ngôi hoàng đế, lấy niên hiệu là gì?",
                    "option_a" => "Gia Long",
                    "option_b" => "Tự Đức",
                    "option_c" => "Quang Trung",
                    "option_d" => "Minh Mạng",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Bản Tuyên ngôn Độc lập khai sinh ra nước Việt Nam Dân chủ Cộng hòa được đọc tại đâu?",
                    "option_a" => "Dinh Độc Lập",
                    "option_b" => "Quảng trường Ba Đình",
                    "option_c" => "Nhà hát Lớn Hà Nội",
                    "option_d" => "Chợ Bến Thành",
                    "correct_answer" => "B"
                ]
            ];
        } elseif (str_contains($topicLower, 'toán')) {
            $pool = [
                [
                    "content" => "Đạo hàm của hàm số y = x^2 là gì?",
                    "option_a" => "x",
                    "option_b" => "2x",
                    "option_c" => "x^3",
                    "option_d" => "2",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Hằng số Pi (π) xấp xỉ bằng bao nhiêu?",
                    "option_a" => "3.12",
                    "option_b" => "3.14",
                    "option_c" => "3.16",
                    "option_d" => "3.20",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Phương trình x^2 - 9 = 0 có các nghiệm là gì?",
                    "option_a" => "x = 3",
                    "option_b" => "x = -3",
                    "option_c" => "x = 3 hoặc x = -3",
                    "option_d" => "Vô nghiệm",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Công thức tính diện tích hình tròn có bán kính r là?",
                    "option_a" => "2 * π * r",
                    "option_b" => "π * r^2",
                    "option_c" => "4 * π * r",
                    "option_d" => "π * d",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Số tự nhiên chẵn nhỏ nhất là số nào?",
                    "option_a" => "0",
                    "option_b" => "2",
                    "option_c" => "1",
                    "option_d" => "4",
                    "correct_answer" => "A"
                ]
            ];
        } elseif (str_contains($topicLower, 'văn') || str_contains($topicLower, 'thơ') || str_contains($topicLower, 'ngữ văn')) {
            $pool = [
                [
                    "content" => "Kiệt tác 'Truyện Kiều' do ai sáng tác?",
                    "option_a" => "Nguyễn Trãi",
                    "option_b" => "Nguyễn Du",
                    "option_c" => "Hồ Xuân Hương",
                    "option_d" => "Đoàn Thị Điểm",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Nhân vật Lão Hạc trong truyện ngắn cùng tên của Nam Cao nuôi con chó tên là gì?",
                    "option_a" => "Cậu Vàng",
                    "option_b" => "Cậu Mực",
                    "option_c" => "Cậu Vện",
                    "option_d" => "Cậu Đốm",
                    "correct_answer" => "A"
                ],
                [
                    "content" => "Tập thơ 'Nhật ký trong tù' là tác phẩm của ai?",
                    "option_a" => "Tố Hữu",
                    "option_b" => "Hồ Chí Minh",
                    "option_c" => "Xuân Diệu",
                    "option_d" => "Huy Cận",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Bài thơ 'Sóng' được sáng tác bởi nhà thơ nào?",
                    "option_a" => "Lâm Thị Mỹ Dạ",
                    "option_b" => "Xuân Quỳnh",
                    "option_c" => "Ý Nhi",
                    "option_d" => "Anh Thơ",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Truyện ngắn 'Chí Phèo' ban đầu có tên là gì?",
                    "option_a" => "Cái lò gạch cũ",
                    "option_b" => "Đôi lứa xứng đôi",
                    "option_c" => "Một đám cưới",
                    "option_d" => "Nửa đêm",
                    "correct_answer" => "B"
                ]
            ];
        } elseif (str_contains($topicLower, 'địa')) {
            $pool = [
                [
                    "content" => "Thủ đô của nước Việt Nam là thành phố nào?",
                    "option_a" => "Đà Nẵng",
                    "option_b" => "Hà Nội",
                    "option_c" => "Hồ Chí Minh",
                    "option_d" => "Huế",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Đỉnh núi cao nhất Đông Dương nằm ở nước nào?",
                    "option_a" => "Lào",
                    "option_b" => "Campuchia",
                    "option_c" => "Việt Nam",
                    "option_d" => "Thái Lan",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Đại dương nào có diện tích lớn nhất hành tinh?",
                    "option_a" => "Đại Tây Dương",
                    "option_b" => "Thái Bình Dương",
                    "option_c" => "Ấn Độ Dương",
                    "option_d" => "Bắc Băng Dương",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Quốc gia nào có diện tích lãnh thổ lớn nhất thế giới?",
                    "option_a" => "Canada",
                    "option_b" => "Mỹ",
                    "option_c" => "Nga",
                    "option_d" => "Trung Quốc",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Dòng sông nào dài nhất thế giới?",
                    "option_a" => "Sông Amazon",
                    "option_b" => "Sông Nile",
                    "option_c" => "Sông Mê Kông",
                    "option_d" => "Sông Trường Giang",
                    "correct_answer" => "B"
                ]
            ];
        } elseif (str_contains($topicLower, 'anh') || str_contains($topicLower, 'english')) {
            $pool = [
                [
                    "content" => "What is the past tense of the verb 'go'?",
                    "option_a" => "goed",
                    "option_b" => "gone",
                    "option_c" => "went",
                    "option_d" => "going",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Which word is a synonym of 'happy'?",
                    "option_a" => "sad",
                    "option_b" => "cheerful",
                    "option_c" => "angry",
                    "option_d" => "tired",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Fill in the blank: She ___ English every day.",
                    "option_a" => "study",
                    "option_b" => "studies",
                    "option_c" => "studying",
                    "option_d" => "studied",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "What is the opposite of the word 'cold'?",
                    "option_a" => "cool",
                    "option_b" => "hot",
                    "option_c" => "freezing",
                    "option_d" => "wet",
                    "correct_answer" => "B"
                ]
            ];
        } elseif (str_contains($topicLower, 'lý') || str_contains($topicLower, 'vật lý')) {
            $pool = [
                [
                    "content" => "Vận tốc ánh sáng truyền trong chân không xấp xỉ bằng bao nhiêu?",
                    "option_a" => "30.000 km/s",
                    "option_b" => "300.000 km/s",
                    "option_c" => "3.000.000 km/s",
                    "option_d" => "150.000 km/s",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Ai là nhà vật lý phát hiện ra Lực vạn vật hấp dẫn nhờ quả táo rơi?",
                    "option_a" => "Galileo Galilei",
                    "option_b" => "Albert Einstein",
                    "option_c" => "Isaac Newton",
                    "option_d" => "Stephen Hawking",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Đơn vị đo cường độ dòng điện là gì?",
                    "option_a" => "Vôn (V)",
                    "option_b" => "Ôm (Ω)",
                    "option_c" => "Ampe (A)",
                    "option_d" => "Oát (W)",
                    "correct_answer" => "C"
                ]
            ];
        } elseif (str_contains($topicLower, 'hóa') || str_contains($topicLower, 'hóa học')) {
            $pool = [
                [
                    "content" => "Công thức hóa học của nước tinh khiết là gì?",
                    "option_a" => "H2",
                    "option_b" => "O2",
                    "option_c" => "H2O",
                    "option_d" => "CO2",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Ký hiệu hóa học của kim loại Vàng là gì?",
                    "option_a" => "Fe",
                    "option_b" => "Ag",
                    "option_c" => "Au",
                    "option_d" => "Cu",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Độ pH của nước tinh khiết ở điều kiện thường là bao nhiêu?",
                    "option_a" => "5",
                    "option_b" => "7",
                    "option_c" => "9",
                    "option_d" => "14",
                    "correct_answer" => "B"
                ]
            ];
        } elseif (str_contains($topicLower, 'php')) {
            $pool = [
                [
                    "content" => "PHP là viết tắt của cụm từ nào?",
                    "option_a" => "Personal Hypertext Processor",
                    "option_b" => "PHP: Hypertext Preprocessor",
                    "option_c" => "Private Home Page",
                    "option_d" => "Processor Hypertext Program",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Để khai báo biến trong PHP, ta dùng ký tự nào?",
                    "option_a" => "@",
                    "option_b" => "#",
                    "option_c" => "$",
                    "option_d" => "&",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Hàm nào dùng để in dữ liệu ra màn hình trong PHP?",
                    "option_a" => "echo",
                    "option_b" => "print_out",
                    "option_c" => "display",
                    "option_d" => "write",
                    "correct_answer" => "A"
                ]
            ];
        } elseif (str_contains($topicLower, 'html') || str_contains($topicLower, 'web') || str_contains($topicLower, 'css')) {
            $pool = [
                [
                    "content" => "Thẻ HTML nào dùng để tạo một liên kết (hyperlink)?",
                    "option_a" => "<link>",
                    "option_b" => "<a>",
                    "option_c" => "<href>",
                    "option_d" => "<anchor>",
                    "correct_answer" => "B"
                ],
                [
                    "content" => "Thẻ nào dùng để định nghĩa kiểu CSS nội bộ (internal stylesheet) trong HTML?",
                    "option_a" => "<css>",
                    "option_b" => "<script>",
                    "option_c" => "<style>",
                    "option_d" => "<link>",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Thuộc tính CSS nào dùng để thay đổi màu nền của một phần tử?",
                    "option_a" => "color",
                    "option_b" => "background-color",
                    "option_c" => "bg-color",
                    "option_d" => "bgcolor",
                    "correct_answer" => "B"
                ]
            ];
        } else {
            $pool = [
                [
                    "content" => "Học máy (Machine Learning) là một nhánh của ngành nào?",
                    "option_a" => "Trí tuệ nhân tạo (AI)",
                    "option_b" => "Hệ quản trị CSDL",
                    "option_c" => "Mạng máy tính",
                    "option_d" => "Đồ họa máy tính",
                    "correct_answer" => "A"
                ],
                [
                    "content" => "Giao thức nào được sử dụng để truyền tải an toàn các trang web?",
                    "option_a" => "HTTP",
                    "option_b" => "FTP",
                    "option_c" => "HTTPS",
                    "option_d" => "SMTP",
                    "correct_answer" => "C"
                ],
                [
                    "content" => "Hệ điều hành nào sau đây là mã nguồn mở?",
                    "option_a" => "Windows",
                    "option_b" => "macOS",
                    "option_c" => "Linux",
                    "option_d" => "iOS",
                    "correct_answer" => "C"
                ]
            ];
        }
        
        $result = [];
        for ($i = 0; $i < $quantity; $i++) {
            $base = $pool[$i % count($pool)];
            $newQ = $base;
            $newQ['content'] = $base['content'];
            $result[] = $newQ;
        }
        
        return $result;
    }

    /**
     * Parse text content from uploaded file (.txt, .pdf, .docx)
     */
    public function extractTextFromFile(string $filePath, ?string $originalExtension = null): string
    {
        $extension = $originalExtension ? strtolower($originalExtension) : strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'txt':
                return file_get_contents($filePath) ?: '';
            case 'docx':
                return $this->parseDocx($filePath);
            case 'pdf':
                return $this->parsePdf($filePath);
            default:
                return '';
        }
    }

    /**
     * Extract text from .docx
     */
    private function parseDocx(string $filePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xmlContent) {
                $text = strip_tags($xmlContent);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }
        return '';
    }

    /**
     * Extract text from .pdf
     */
    private function parsePdf(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if (!$content) return '';

        $streams = [];
        $pos = 0;
        while (($start = strpos($content, 'stream', $pos)) !== false) {
            $start += 6;
            if ($content[$start] === "\r") $start++;
            if ($content[$start] === "\n") $start++;
            
            $end = strpos($content, 'endstream', $start);
            if ($end === false) break;
            
            $streamData = substr($content, $start, $end - $start);
            $streams[] = $streamData;
            $pos = $end + 9;
        }

        $extractedText = '';
        foreach ($streams as $stream) {
            $decompressed = @gzuncompress($stream);
            if ($decompressed === false) {
                $decompressed = $stream;
            }

            preg_match_all('/\((.*?)\)\s*(?:Tj|TJ|Td)/s', $decompressed, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $txt) {
                    $txt = preg_replace_callback('/\\\\([0-7]{3})/', function($m) {
                        return chr(octdec($m[1]));
                    }, $txt);
                    $txt = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $txt);
                    $extractedText .= $txt . ' ';
                }
            }
        }

        $extractedText = trim($extractedText);
        if (strlen($extractedText) > 50) {
            return mb_convert_encoding($extractedText, 'UTF-8', 'UTF-8');
        }

        preg_match_all('/\((.*?)\)/', $content, $matches);
        $fallbackText = '';
        foreach ($matches[1] as $txt) {
            if (strlen($txt) > 2 && preg_match('/^[a-zA-Z0-9\s\x80-\xff]+$/', $txt)) {
                $fallbackText .= $txt . ' ';
            }
        }
        return mb_convert_encoding(trim($fallbackText), 'UTF-8', 'UTF-8');
    }

    /**
     * Fallback generator to parse uploaded document and construct quiz questions offline
     */
    private function generateQuestionsFromDocFallback(string $docText, string $topic, int $quantity): array
    {
        // Split into sentences
        $lines = preg_split('/[\n\r.!?]/', $docText);
        $validStatements = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 20 && strlen($line) < 180) {
                if (str_contains($line, ' là ') || str_contains($line, ' được ') || str_contains($line, ' có ') || str_contains($line, ' is ') || str_contains($line, ' has ')) {
                    $validStatements[] = $line;
                }
            }
        }

        if (count($validStatements) < $quantity) {
            return $this->generateMockQuestions($topic, $quantity);
        }

        $questions = [];
        for ($i = 0; $i < $quantity; $i++) {
            $statement = $validStatements[$i % count($validStatements)];
            $content = $statement;
            $answer = "Đúng";
            
            if (str_contains($statement, ' là ')) {
                $parts = explode(' là ', $statement, 2);
                $content = "Chọn đáp án đúng điền vào chỗ trống: \"" . trim($parts[0]) . " là ______.\"";
                $answer = trim($parts[1]);
            } elseif (str_contains($statement, ' có ')) {
                $parts = explode(' có ', $statement, 2);
                $content = "Chọn đáp án đúng điền vào chỗ trống: \"" . trim($parts[0]) . " có ______.\"";
                $answer = trim($parts[1]);
            } elseif (str_contains($statement, ' is ')) {
                $parts = explode(' is ', $statement, 2);
                $content = "Fill in the blank: \"" . trim($parts[0]) . " is ______.\"";
                $answer = trim($parts[1]);
            }

            $answer = rtrim($answer, '. ');

            $options = [
                'A' => $answer,
                'B' => "Phương án B (Nội dung nhiễu tham khảo)",
                'C' => "Phương án C (Nội dung nhiễu tham khảo)",
                'D' => "Phương án D (Nội dung nhiễu tham khảo)"
            ];

            $correctKeys = ['A', 'B', 'C', 'D'];
            $correctKey = $correctKeys[$i % 4];
            
            $questions[] = [
                "content" => $content,
                "option_a" => $correctKey === 'A' ? $options['A'] : $options['B'],
                "option_b" => $correctKey === 'B' ? $options['A'] : ($correctKey === 'A' ? $options['B'] : $options['C']),
                "option_c" => $correctKey === 'C' ? $options['A'] : ($correctKey === 'A' || $correctKey === 'B' ? $options['C'] : $options['D']),
                "option_d" => $correctKey === 'D' ? $options['A'] : $options['D'],
                "correct_answer" => $correctKey
            ];
        }

        return $questions;
    }

    /**
     * Clean and sanitize UTF-8 strings recursively
     */
    private function cleanUtf8($data)
    {
        if (is_string($data)) {
            return mb_scrub($data, 'UTF-8');
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUtf8($value);
            }
        }
        return $data;
    }
}
