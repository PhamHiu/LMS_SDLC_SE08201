<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Tạo tài liệu học thật (.docx Word và .pdf) cho toàn bộ các môn học trong hệ thống,
 * lưu tại `storage/app/public/materials/` và đồng bộ vào bảng `materials`.
 */
class SubjectMaterialsSeeder extends Seeder
{
    public function run(): void
    {
        $teacherId = User::where('role', User::ROLE_TEACHER)->value('user_id')
            ?? User::where('role', User::ROLE_ADMIN)->value('user_id')
            ?? 1;

        $disk = Storage::disk('public');
        $disk->makeDirectory('materials');

        // Xóa các bản ghi demo trỏ tới file không tồn tại
        Material::whereIn('file_path', [
            'materials/demo-giao-trinh.pdf',
            'materials/demo-slide-tuan1.pdf',
        ])->delete();

        $catalog = $this->materialsCatalog();

        foreach (Subject::orderBy('subject_id')->get() as $subject) {
            $code = strtoupper($subject->subject_code);
            $pack = $catalog[$code] ?? $this->buildGenericPack($subject);

            foreach ($pack as $item) {
                $relativePath = 'materials/' . $item['filename'];
                $fullPath = $disk->path($relativePath);

                if (str_ends_with($item['filename'], '.docx')) {
                    $this->writeDocx($fullPath, $item['title'], $subject->subject_name, $item['sections']);
                } else {
                    $this->writePdf($fullPath, $item['title'], $subject->subject_name, $item['sections']);
                }

                Material::updateOrCreate(
                    [
                        'subject_id' => $subject->subject_id,
                        'file_path' => $relativePath,
                    ],
                    [
                        'user_id' => $teacherId,
                        'title' => $item['title'],
                        'description' => $item['description'],
                    ]
                );
            }
        }
    }

    /**
     * Tạo file Microsoft Word (.docx) chuẩn OpenXML hỗ trợ đầy đủ tiếng Việt UTF-8.
     */
    private function writeDocx(string $path, string $docTitle, string $subjectName, array $sections): void
    {
        $paragraphs = [];
        $paragraphs[] = $this->docxParagraph($docTitle, true, 32);
        $paragraphs[] = $this->docxParagraph("Môn học: {$subjectName}", true, 24);
        $paragraphs[] = $this->docxParagraph('Tài liệu học tập chính thức trên hệ thống LMS.', false, 22);
        $paragraphs[] = $this->docxParagraph('', false, 20);

        foreach ($sections as $sec) {
            $paragraphs[] = $this->docxParagraph($sec['heading'], true, 26);
            foreach ($sec['lines'] as $line) {
                $paragraphs[] = $this->docxParagraph($line, false, 22);
            }
            $paragraphs[] = $this->docxParagraph('', false, 18);
        }

        $bodyXml = implode("\n", $paragraphs);
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $bodyXml . '<w:sectPr/></w:body></w:document>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('[Content_Types].xml', $contentTypesXml);
            $zip->addFromString('_rels/.rels', $relsXml);
            $zip->addFromString('word/document.xml', $documentXml);
            $zip->close();
        }
    }

    private function docxParagraph(string $text, bool $bold = false, int $halfPoints = 22): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $rPr = '<w:rPr>'
            . ($bold ? '<w:b/>' : '')
            . '<w:sz w:val="' . $halfPoints . '"/>'
            . '<w:szCs w:val="' . $halfPoints . '"/>'
            . '</w:rPr>';

        return '<w:p><w:r>' . $rPr . '<w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
    }

    /**
     * Tạo file PDF 1.4 chuẩn mở được trên mọi trình đọc PDF (Chrome, Edge, Adobe Acrobat).
     */
    private function writePdf(string $path, string $docTitle, string $subjectName, array $sections): void
    {
        $lines = [];
        $lines[] = $this->toPdfAscii($docTitle);
        $lines[] = 'Mon hoc: ' . $this->toPdfAscii($subjectName);
        $lines[] = str_repeat('-', 78);

        foreach ($sections as $sec) {
            $lines[] = '';
            $lines[] = $this->toPdfAscii($sec['heading']);
            foreach ($sec['lines'] as $l) {
                foreach ($this->wrapAscii($this->toPdfAscii($l), 86) as $wrapped) {
                    $lines[] = $wrapped;
                }
            }
        }

        // Chia thành trang (tối đa 46 dòng/trang)
        $pages = array_chunk($lines, 46);
        $objects = [];

        // Object 1: Catalog, Object 2: Pages, Object 3: Font Helvetica
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pageObjIds = [];
        $nextId = 4;

        foreach ($pages as $pageLines) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $pageObjIds[] = $pageId;

            $stream = "BT\n/F1 11 Tf\n50 780 Td\n15 TL\n";
            foreach ($pageLines as $idx => $ln) {
                $esc = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ln);
                $stream .= ($idx === 0 ? "({$esc}) Tj\n" : "T* ({$esc}) Tj\n");
            }
            $stream .= "ET";

            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
        }

        $kids = implode(' ', array_map(fn ($id) => "{$id} 0 R", $pageObjIds));
        $objects[2] = "<< /Type /Pages /Kids [ {$kids} ] /Count " . count($pageObjIds) . " >>";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $objBody) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$objBody}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $totalObjs = count($objects) + 1;
        $pdf .= "xref\n0 {$totalObjs}\n0000000000 65535 f \n";
        for ($i = 1; $i < $totalObjs; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        file_put_contents($path, $pdf);
    }

    private function toPdfAscii(string $str): string
    {
        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
            'À'=>'A','Á'=>'A','Ạ'=>'A','Ả'=>'A','Ã'=>'A','Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ậ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ặ'=>'A','Ẳ'=>'A','Ẵ'=>'A',
            'È'=>'E','É'=>'E','Ẹ'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ê'=>'E','Ề'=>'E','Ế'=>'E','Ệ'=>'E','Ể'=>'E','Ễ'=>'E',
            'Ì'=>'I','Í'=>'I','Ị'=>'I','Ỉ'=>'I','Ĩ'=>'I',
            'Ò'=>'O','Ó'=>'O','Ọ'=>'O','Ỏ'=>'O','Õ'=>'O','Ô'=>'O','Ồ'=>'O','Ố'=>'O','Ộ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ơ'=>'O','Ờ'=>'O','Ớ'=>'O','Ợ'=>'O','Ở'=>'O','Ỡ'=>'O',
            'Ù'=>'U','Ú'=>'U','Ụ'=>'U','Ủ'=>'U','Ũ'=>'U','Ư'=>'U','Ừ'=>'U','Ứ'=>'U','Ự'=>'U','Ử'=>'U','Ữ'=>'U',
            'Ỳ'=>'Y','Ý'=>'Y','Ỵ'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y','Đ'=>'D',
            '–'=>'-','—'=>'-','“'=>'"','”'=>'"','’'=>"'",'•'=>'-','→'=>'->','×'=>'x',
        ];
        $clean = strtr($str, $map);
        return preg_replace('/[^\x20-\x7E]/', ' ', $clean) ?? '';
    }

    private function wrapAscii(string $text, int $width): array
    {
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function buildGenericPack(Subject $subject): array
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $subject->subject_code));
        return [
            [
                'filename' => "{$slug}-giao-trinh-ly-thuyet.docx",
                'title' => "Giáo trình Lý thuyết {$subject->subject_name}",
                'description' => "Tài liệu Word (.docx) tổng hợp kiến thức trọng tâm môn {$subject->subject_name}.",
                'sections' => [
                    ['heading' => '1. Mục tiêu môn học', 'lines' => ["Nắm vững nền tảng lý thuyết và kỹ năng thực hành môn {$subject->subject_name}."]],
                    ['heading' => '2. Nội dung cốt lõi', 'lines' => ['- Tổng quan khái niệm và nguyên lý nền tảng.', '- Phân tích bài toán thực tế và bài tập ứng dụng.', '- Tiêu chí đánh giá bài tập lớn và ôn tập cuối kỳ.']],
                ],
            ],
            [
                'filename' => "{$slug}-bai-giang-thuc-hanh.pdf",
                'title' => "Bài giảng & Hướng dẫn Thực hành {$subject->subject_name}",
                'description' => "Tài liệu PDF hướng dẫn từng bước thực hành và câu hỏi ôn tập môn {$subject->subject_name}.",
                'sections' => [
                    ['heading' => '1. Hướng dẫn thực hành', 'lines' => ['- Chuẩn bị môi trường và tài liệu tham khảo.', '- Thực hành bài lab theo từng tuần học và nộp bài đúng hạn.']],
                    ['heading' => '2. Câu hỏi ôn tập', 'lines' => ['- Trình bày các khái niệm chính của môn học.', '- Phân tích một tình huống ứng dụng thực tế.']],
                ],
            ],
        ];
    }

    private function materialsCatalog(): array
    {
        return [
            'WEB301' => [
                [
                    'filename' => 'web301-giao-trinh-laravel-mvc.docx',
                    'title' => 'Giáo trình Lập trình Web với Laravel (MVC, Eloquent, Blade)',
                    'description' => 'Tài liệu Word (.docx) đầy đủ về kiến trúc MVC, Routing, Middleware, Eloquent ORM và bảo mật CSRF/XSS trong Laravel.',
                    'sections' => [
                        ['heading' => 'Chương 1: Kiến trúc MVC & Vòng đời Request trong Laravel', 'lines' => [
                            '- Mô hình MVC tách biệt Model (dữ liệu), View (giao diện Blade) và Controller (điều phối logic).',
                            '- Mọi HTTP Request đi qua public/index.php -> Kernel -> Middleware -> Router -> Controller -> Response.',
                            '- Service Container hỗ trợ Dependency Injection giúp code dễ kiểm thử và bảo trì.',
                        ]],
                        ['heading' => 'Chương 2: Eloquent ORM & Migration', 'lines' => [
                            '- Migration quản lý phiên bản cấu trúc bảng CSDL bằng mã PHP (up/down).',
                            '- Eloquent cung cấp quan hệ hasOne, hasMany, belongsTo, belongsToMany và Eager Loading (with) để tránh lỗi N+1 query.',
                        ]],
                        ['heading' => 'Chương 3: Bảo mật Web cơ bản', 'lines' => [
                            '- Luôn sử dụng @csrf trong form POST/PUT/DELETE để chống tấn công CSRF.',
                            '- Sử dụng {{ $var }} trong Blade để tự động escape HTML chống XSS; dùng FormRequest để validate dữ liệu đầu vào.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'web301-huong-dan-thuc-hanh-crud.pdf',
                    'title' => 'Bài giảng Thực hành CRUD & REST API trong Laravel',
                    'description' => 'Tài liệu PDF hướng dẫn xây dựng chức năng CRUD chuẩn RESTful, phân quyền Middleware và tối ưu truy vấn.',
                    'sections' => [
                        ['heading' => '1. Quy ước RESTful Resource Route', 'lines' => [
                            '- GET /items (index), GET /items/create (create), POST /items (store), GET /items/{id} (show), PUT /items/{id} (update), DELETE /items/{id} (destroy).',
                        ]],
                        ['heading' => '2. Bài tập thực hành', 'lines' => [
                            '- Xây dựng module Quản lý Sinh viên có tìm kiếm, phân trang và xác thực dữ liệu bằng FormRequest.',
                            '- Áp dụng Middleware kiểm tra quyền Admin/Giảng viên trước khi cho phép thêm/sửa/xóa.',
                        ]],
                    ],
                ],
            ],
            'DB201' => [
                [
                    'filename' => 'db201-giao-trinh-co-so-du-lieu-mysql.docx',
                    'title' => 'Giáo trình Cơ sở dữ liệu MySQL (ERD, Chuẩn hóa 1NF–3NF, SQL)',
                    'description' => 'Tài liệu Word (.docx) về thiết kế ERD, chuẩn hóa dữ liệu 1NF/2NF/3NF, khóa chính/khóa ngoại và các câu lệnh JOIN.',
                    'sections' => [
                        ['heading' => 'Chương 1: Thiết kế ERD & Khóa chính / Khóa ngoại', 'lines' => [
                            '- Khóa chính (PRIMARY KEY) định danh duy nhất mỗi bản ghi và không được NULL.',
                            '- Khóa ngoại (FOREIGN KEY) đảm bảo toàn vẹn tham chiếu giữa các bảng (quan hệ 1-N, N-N).',
                        ]],
                        ['heading' => 'Chương 2: Chuẩn hóa dữ liệu 1NF, 2NF, 3NF', 'lines' => [
                            '- 1NF: Mỗi ô chỉ chứa giá trị nguyên tố (atomic), không lặp nhóm cột.',
                            '- 2NF: Đạt 1NF và mọi thuộc tính không khóa phụ thuộc hoàn toàn vào khóa chính.',
                            '- 3NF: Đạt 2NF và không có phụ thuộc bắc cầu giữa các thuộc tính không khóa.',
                        ]],
                        ['heading' => 'Chương 3: Truy vấn SQL nâng cao', 'lines' => [
                            '- Sử dụng INNER JOIN, LEFT JOIN, GROUP BY, HAVING, Subquery và Index để tối ưu tốc độ truy vấn.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'db201-bai-tap-thuc-hanh-sql.pdf',
                    'title' => 'Bài giảng & Lab Thực hành Truy vấn SQL MySQL',
                    'description' => 'Tài liệu PDF tổng hợp câu lệnh DDL/DML, bài tập JOIN nhiều bảng và tối ưu chỉ mục Index.',
                    'sections' => [
                        ['heading' => '1. Câu lệnh mẫu trọng tâm', 'lines' => [
                            '- SELECT, WHERE, ORDER BY, LIMIT, COUNT/SUM/AVG kết hợp GROUP BY và HAVING.',
                            '- Phân biệt WHERE (lọc trước khi gom nhóm) và HAVING (lọc sau khi gom nhóm).',
                        ]],
                        ['heading' => '2. Transaction & ACID', 'lines' => [
                            '- Đảm bảo 4 tính chất Atomicity, Consistency, Isolation, Durability bằng BEGIN / COMMIT / ROLLBACK.',
                        ]],
                    ],
                ],
            ],
            'DSA101' => [
                [
                    'filename' => 'dsa101-giao-trinh-cau-truc-du-lieu-giai-thuat.docx',
                    'title' => 'Giáo trình Cấu trúc dữ liệu & Giải thuật (Big-O, Stack/Queue, Tree)',
                    'description' => 'Tài liệu Word (.docx) phân tích độ phức tạp Big-O, danh sách liên kết, ngăn xếp, hàng đợi, cây nhị phân và sắp xếp.',
                    'sections' => [
                        ['heading' => 'Chương 1: Đánh giá độ phức tạp Big-O', 'lines' => [
                            '- Thứ tự tăng trưởng phổ biến: O(1) < O(log n) < O(n) < O(n log n) < O(n^2) < O(2^n).',
                            '- Luôn phân tích cả độ phức tạp thời gian (Time Complexity) và bộ nhớ (Space Complexity).',
                        ]],
                        ['heading' => 'Chương 2: Cấu trúc tuyến tính & Cây', 'lines' => [
                            '- Stack hoạt động theo nguyên tắc LIFO (Vào sau ra trước); Queue hoạt động theo FIFO (Vào trước ra trước).',
                            '- Binary Search Tree (BST) hỗ trợ tìm kiếm trung bình O(log n) khi cây cân bằng.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'dsa101-thuat-toan-sap-xep-tim-kiem.pdf',
                    'title' => 'Slide Bài giảng Thuật toán Sắp xếp & Tìm kiếm',
                    'description' => 'Tài liệu PDF so sánh QuickSort, MergeSort, Binary Search và duyệt đồ thị BFS/DFS.',
                    'sections' => [
                        ['heading' => '1. Tìm kiếm & Sắp xếp', 'lines' => [
                            '- Binary Search yêu cầu mảng đã sắp xếp, độ phức tạp O(log n).',
                            '- MergeSort luôn O(n log n) và ổn định (stable); QuickSort trung bình O(n log n), trường hợp xấu nhất O(n^2).',
                        ]],
                        ['heading' => '2. Duyệt đồ thị BFS & DFS', 'lines' => [
                            '- BFS dùng Queue để tìm đường đi ngắn nhất trên đồ thị không trọng số; DFS dùng Stack/đệ quy.',
                        ]],
                    ],
                ],
            ],
            'OOP102' => [
                [
                    'filename' => 'oop102-giao-trinh-lap-trinh-huong-doi-tuong.docx',
                    'title' => 'Giáo trình Lập trình Hướng đối tượng (4 tính chất OOP & SOLID)',
                    'description' => 'Tài liệu Word (.docx) chi tiết về Đóng gói, Kế thừa, Đa hình, Trừu tượng và 5 nguyên lý thiết kế SOLID.',
                    'sections' => [
                        ['heading' => 'Chương 1: 4 tính chất cốt lõi của OOP', 'lines' => [
                            '- Đóng gói (Encapsulation): Che giấu dữ liệu nội bộ bằng private/protected, truy xuất qua getter/setter.',
                            '- Kế thừa (Inheritance): Tái sử dụng thuộc tính và phương thức từ lớp cha.',
                            '- Đa hình (Polymorphism): Cùng một thông điệp/phương thức nhưng hành vi khác nhau ở lớp con (Override/Overload).',
                            '- Trừu tượng (Abstraction): Chỉ hiển thị giao diện cần thiết thông qua Abstract Class và Interface.',
                        ]],
                        ['heading' => 'Chương 2: Nguyên lý SOLID', 'lines' => [
                            '- Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'oop102-so-do-uml-class-diagram.pdf',
                    'title' => 'Hướng dẫn thiết kế Class Diagram & Quan hệ OOP',
                    'description' => 'Tài liệu PDF phân biệt Association, Aggregation, Composition và Inheritance trong UML.',
                    'sections' => [
                        ['heading' => '1. Ký hiệu trong UML Class Diagram', 'lines' => [
                            '- (+) public, (-) private, (#) protected.',
                            '- Composition (hình thoi đặc) biểu diễn quan hệ sở hữu chặt; Aggregation (hình thoi rỗng) sở hữu lỏng.',
                        ]],
                    ],
                ],
            ],
            'UIX201' => [
                [
                    'filename' => 'uix201-giao-trinh-thiet-ke-ui-ux.docx',
                    'title' => 'Giáo trình Thiết kế Giao diện UI/UX (Design Thinking & Usability)',
                    'description' => 'Tài liệu Word (.docx) về quy trình Design Thinking 5 bước, Wireframe, Prototype và 10 nguyên tắc Heuristic của Nielsen.',
                    'sections' => [
                        ['heading' => 'Chương 1: Quy trình Design Thinking 5 bước', 'lines' => [
                            '- Empathize (Thấu cảm người dùng) -> Define (Xác định vấn đề) -> Ideate (Lên ý tưởng) -> Prototype (Tạo mẫu) -> Test (Kiểm thử).',
                        ]],
                        ['heading' => 'Chương 2: Nguyên lý UI & Khả năng tiếp cận (WCAG)', 'lines' => [
                            '- Đảm bảo hệ thống lưới (Grid 8pt), phân cấp thị giác (Visual Hierarchy), độ tương phản màu tối thiểu 4.5:1 cho văn bản thường.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'uix201-checklist-danh-gia-usability.pdf',
                    'title' => 'Tài liệu 10 Nguyên tắc Heuristic & Đánh giá UX',
                    'description' => 'Tài liệu PDF hướng dẫn kiểm thử khả năng sử dụng (Usability Testing) và thiết kế Figma.',
                    'sections' => [
                        ['heading' => '1. Các tiêu chí Nielsen tiêu biểu', 'lines' => [
                            '- Hiển thị trạng thái hệ thống rõ ràng, nhất quán chuẩn mực thiết kế, phòng ngừa lỗi cho người dùng và thiết kế tối giản.',
                        ]],
                    ],
                ],
            ],
            'MKT101' => [
                [
                    'filename' => 'mkt101-giao-trinh-digital-marketing.docx',
                    'title' => 'Giáo trình Digital Marketing (Phễu khách hàng, SEO, Content, Ads)',
                    'description' => 'Tài liệu Word (.docx) tổng hợp mô hình AIDA/RACE, chiến lược SEO On-page/Off-page, Content Marketing và đo lường KPI.',
                    'sections' => [
                        ['heading' => 'Chương 1: Hành trình khách hàng & Phễu Marketing', 'lines' => [
                            '- Mô hình AIDA: Awareness (Nhận biết) -> Interest (Quan tâm) -> Desire (Mong muốn) -> Action (Hành động).',
                            '- Xác định chân dung khách hàng mục tiêu (Buyer Persona) trước khi chọn kênh truyền thông.',
                        ]],
                        ['heading' => 'Chương 2: SEO & Chỉ số đo lường hiệu quả', 'lines' => [
                            '- Các chỉ số cốt lõi: CTR (Tỷ lệ nhấp), CPC (Chi phí mỗi nhấp), CPA (Chi phí chuyển đổi), ROAS và ROI.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'mkt101-lap-ke-hoach-chien-dich-digital.pdf',
                    'title' => 'Hướng dẫn Lập kế hoạch Chiến dịch Digital Marketing',
                    'description' => 'Tài liệu PDF hướng dẫn đặt mục tiêu SMART, phân bổ ngân sách đa kênh và lập báo cáo chiến dịch.',
                    'sections' => [
                        ['heading' => '1. Đặt mục tiêu SMART', 'lines' => [
                            '- Specific (Cụ thể), Measurable (Đo lường được), Achievable (Khả thi), Relevant (Phù hợp), Time-bound (Có thời hạn).',
                        ]],
                    ],
                ],
            ],
            'PRO192' => [
                [
                    'filename' => 'pro192-giao-trinh-java-oop.docx',
                    'title' => 'Giáo trình Lập trình Hướng đối tượng Java (Class, Collection, Exception)',
                    'description' => 'Tài liệu Word (.docx) đầy đủ cho môn PRO192: cú pháp Java, OOP, ArrayList/HashMap, xử lý ngoại lệ và File I/O.',
                    'sections' => [
                        ['heading' => 'Chương 1: Đối tượng, Lớp & 4 tính chất OOP trong Java', 'lines' => [
                            '- JVM giúp chương trình Java chạy đa nền tảng (Write Once, Run Anywhere).',
                            '- Phân biệt Abstract Class (có thể có thuộc tính và constructor) với Interface (định nghĩa hợp đồng hành vi).',
                        ]],
                        ['heading' => 'Chương 2: Java Collections & Exception Handling', 'lines' => [
                            '- Sử dụng ArrayList, LinkedList, HashSet, HashMap linh hoạt theo bài toán.',
                            '- Bắt và xử lý lỗi bằng try-catch-finally, phân biệt Checked Exception và Unchecked Exception.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'pro192-bai-tap-lab-java-oop.pdf',
                    'title' => 'Bộ Bài tập Thực hành & Ôn thi Practical Exam PRO192',
                    'description' => 'Tài liệu PDF tổng hợp bài tập quản lý danh sách đối tượng, sắp xếp Comparator và đọc ghi file trong Java.',
                    'sections' => [
                        ['heading' => '1. Dạng bài thường gặp trong bài thi thực hành', 'lines' => [
                            '- Tạo lớp thực thể, kế thừa, ghi đè toString(), quản lý danh sách bằng ArrayList và sắp xếp bằng Collections.sort / Comparator.',
                        ]],
                    ],
                ],
            ],
            'DBI202' => [
                [
                    'filename' => 'dbi202-giao-trinh-he-quan-tri-mysql.docx',
                    'title' => 'Giáo trình Cơ sở dữ liệu & Hệ Quản trị MySQL (DBI202)',
                    'description' => 'Tài liệu Word (.docx) về mô hình quan hệ, đại số quan hệ, Stored Procedure, Trigger, View và quản trị người dùng.',
                    'sections' => [
                        ['heading' => 'Chương 1: Mô hình quan hệ & Ràng buộc toàn vẹn', 'lines' => [
                            '- Các ràng buộc chuẩn: PRIMARY KEY, FOREIGN KEY, UNIQUE, NOT NULL, CHECK, DEFAULT.',
                            '- Chuẩn hóa lược đồ quan hệ lên 3NF / BCNF để loại bỏ dị thường thêm, sửa, xóa.',
                        ]],
                        ['heading' => 'Chương 2: View, Stored Procedure & Trigger', 'lines' => [
                            '- View tạo bảng ảo giúp đơn giản hóa truy vấn phức tạp và bảo mật cột dữ liệu.',
                            '- Trigger tự động thực thi trước/sau INSERT, UPDATE, DELETE để giữ nhất quán nghiệp vụ.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'dbi202-tong-hop-truy-van-nang-cao.pdf',
                    'title' => 'Tài liệu Thực hành Truy vấn Nâng cao & Tối ưu MySQL',
                    'description' => 'Tài liệu PDF hướng dẫn CTE, Window Functions, EXPLAIN và thiết kế Index hiệu quả.',
                    'sections' => [
                        ['heading' => '1. Tối ưu truy vấn với EXPLAIN & Index', 'lines' => [
                            '- Dùng EXPLAIN để phát hiện Full Table Scan; tạo Composite Index theo thứ tự cột lọc có độ chọn lọc cao.',
                        ]],
                    ],
                ],
            ],
            'WEB2013' => [
                [
                    'filename' => 'web2013-giao-trinh-web-backend-laravel.docx',
                    'title' => 'Giáo trình Lập trình Web Backend với Laravel (API, Auth, Queue)',
                    'description' => 'Tài liệu Word (.docx) về xây dựng Backend chuẩn Service Layer, xác thực Sanctum, Validation, Cache và Testing.',
                    'sections' => [
                        ['heading' => 'Chương 1: Kiến trúc Controller – Service – Model', 'lines' => [
                            '- Controller chỉ tiếp nhận Request và trả Response; toàn bộ nghiệp vụ tách sang lớp Service để dễ mở rộng và viết Unit Test.',
                        ]],
                        ['heading' => 'Chương 2: Authentication, Authorization & API', 'lines' => [
                            '- Phân quyền bằng Middleware / Policy / Gate; trả dữ liệu JSON chuẩn hóa bằng API Resource.',
                            '- Sử dụng Cache (Redis/File) và Queue để xử lý tác vụ nặng bất đồng bộ.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'web2013-huong-dan-xay-dung-du-an-lms.pdf',
                    'title' => 'Hướng dẫn Thực hành Xây dựng Backend Dự án Web',
                    'description' => 'Tài liệu PDF hướng dẫn tổ chức thư mục, viết Migration/Seeder, kiểm thử Feature Test và triển khai.',
                    'sections' => [
                        ['heading' => '1. Quy trình triển khai chuẩn', 'lines' => [
                            '- Thiết kế CSDL -> Migration & Seeder -> Service & Controller -> Validation -> Kiểm thử tích hợp -> Đóng gói triển khai.',
                        ]],
                    ],
                ],
            ],
            'SWE2011' => [
                [
                    'filename' => 'swe2011-giao-trinh-phan-tich-thiet-ke-sdlc.docx',
                    'title' => 'Giáo trình Phân tích & Thiết kế Hệ thống (SDLC, SRS, UML)',
                    'description' => 'Tài liệu Word (.docx) đầy đủ về các mô hình SDLC (Waterfall, Agile/Scrum, Iterative), đặc tả SRS và các biểu đồ UML.',
                    'sections' => [
                        ['heading' => 'Chương 1: Vòng đời phát triển phần mềm (SDLC)', 'lines' => [
                            '- 6 giai đoạn chuẩn: Planning -> Analysis -> Design -> Implementation -> Testing -> Deployment & Maintenance.',
                            '- So sánh Waterfall (yêu cầu cố định, tài liệu chặt) với Agile/Scrum (lặp ngắn Sprint 2–4 tuần, thích ứng thay đổi nhanh).',
                        ]],
                        ['heading' => 'Chương 2: Đặc tả yêu cầu SRS & Thiết kế UML', 'lines' => [
                            '- Phân biệt Yêu cầu chức năng (Functional Requirements) và Yêu cầu phi chức năng (Non-Functional: hiệu năng, bảo mật, sẵn sàng).',
                            '- Bộ sơ đồ UML trọng tâm: Use Case Diagram, Activity Diagram, Sequence Diagram, Class Diagram và ERD.',
                        ]],
                    ],
                ],
                [
                    'filename' => 'swe2011-mau-tai-lieu-srs-va-uml.pdf',
                    'title' => 'Hướng dẫn Viết Đặc tả SRS & Vẽ Sơ đồ UML Chuẩn',
                    'description' => 'Tài liệu PDF hướng dẫn lập tài liệu SRS theo chuẩn IEEE 830, viết Use Case Specification và thiết kế Test Case.',
                    'sections' => [
                        ['heading' => '1. Cấu trúc đặc tả Use Case chuẩn', 'lines' => [
                            '- Gồm: Tên Use Case, Actor chính, Điều kiện tiên quyết (Pre-condition), Luồng chính (Main Flow), Luồng thay thế/ngoại lệ và Hậu điều kiện.',
                        ]],
                        ['heading' => '2. Kiểm thử phần mềm', 'lines' => [
                            '- Phân biệt Black-box Testing (kiểm thử hộp đen theo chức năng) và White-box Testing (kiểm thử hộp trắng theo luồng code).',
                        ]],
                    ],
                ],
            ],
        ];
    }
}
