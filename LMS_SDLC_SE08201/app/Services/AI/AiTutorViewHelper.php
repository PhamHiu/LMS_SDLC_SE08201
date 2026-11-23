<?php

namespace App\Services\AI;

class AiTutorViewHelper
{
    /**
     * Chuyển đổi nội dung tin nhắn Markdown của MR. BIẾT TUỐT sang HTML hiển thị an toàn:
     * - Tự động render bảng biểu Markdown sang giao diện Table hiện đại, thanh lịch
     * - Loại bỏ hoàn toàn ký tự thô như ### và dấu sao bullet *
     * - Làm nổi bật từ khóa quan trọng và giữ nguyên định dạng Code Block
     */
    public static function renderMarkdown(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // 1. Tách và bảo vệ các khối Code Blocks ```...``` (và dựng card sơ đồ UML/Mermaid)
        $codeBlocks = [];
        $text = preg_replace_callback('/```([a-zA-Z0-9_-]*)\r?\n([\s\S]*?)```/', function ($matches) use (&$codeBlocks) {
            $placeholder = '___CODE_BLOCK_' . count($codeBlocks) . '___';
            $rawLang = strtolower(trim($matches[1] ?: 'code'));
            $rawCode = trim($matches[2]);
            $escapedCode = htmlspecialchars($rawCode, ENT_QUOTES, 'UTF-8');

            if ($rawLang === 'mermaid') {
                $codeBlocks[$placeholder] =
                    '<div class="ai-mermaid-container my-3 rounded-lg border border-slate-200 bg-white shadow-xs overflow-hidden" data-rendered="false">'
                    . '<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 text-[11px]">'
                    . '<span class="ai-mermaid-label font-bold text-[#1e4f8f]">Sơ đồ minh họa (UML / Mermaid)</span>'
                    . '<div class="flex items-center gap-1.5">'
                    . '<button type="button" onclick="window.zoomMermaidCard(this)" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold text-slate-700 hover:bg-slate-100 transition">Phóng to</button>'
                    . '<button type="button" onclick="window.downloadMermaidCardSvg(this)" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold text-slate-700 hover:bg-slate-100 transition">Tải SVG</button>'
                    . '<button type="button" onclick="window.toggleMermaidSource(this)" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold text-slate-600 hover:bg-slate-100 transition">Mã nguồn</button>'
                    . '</div></div>'
                    . '<div class="ai-mermaid-canvas p-3 overflow-x-auto flex justify-center bg-white min-h-[72px]"><span class="text-xs text-slate-400 py-4">Đang vẽ sơ đồ...</span></div>'
                    . '<pre class="ai-mermaid-src hidden border-t border-slate-200 bg-slate-900 text-slate-100 p-3 text-[11px] overflow-x-auto m-0 rounded-none">' . $escapedCode . '</pre>'
                    . '</div>';
            } else {
                $cleanLang = preg_replace('/[^a-z0-9_+#-]/', '', $rawLang) ?: 'code';
                $lang = htmlspecialchars($cleanLang, ENT_QUOTES, 'UTF-8');
                $displayLang = $cleanLang === 'code' ? 'TEXT' : strtoupper($lang);
                $codeBlocks[$placeholder] =
                    '<div class="vscode-code-block" data-highlighted="false">'
                    . '<div class="vscode-code-header">'
                    . '<span class="vscode-code-lang">&lt;/&gt; ' . $displayLang . '</span>'
                    . '<button type="button" class="vscode-copy-btn" onclick="window.copyVsCodeBlock(this)">Sao chép</button>'
                    . '</div>'
                    . '<pre><code class="language-' . $lang . '">' . $escapedCode . '</code></pre>'
                    . '</div>';
            }

            return $placeholder;
        }, $text);

        // 1b. Phòng trường hợp khối code bị ngắt giữa chừng chưa kịp đóng ``` -> vẫn bọc vào khung VS Code gọn gàng
        $text = preg_replace_callback('/```([a-zA-Z0-9_-]*)\r?\n([\s\S]+)$/', function ($matches) use (&$codeBlocks) {
            $placeholder = '___CODE_BLOCK_' . count($codeBlocks) . '___';
            $rawLang = strtolower(trim($matches[1] ?: 'code'));
            if ($rawLang === 'mermaid') {
                return $matches[0];
            }
            $cleanLang = preg_replace('/[^a-z0-9_+#-]/', '', $rawLang) ?: 'code';
            $lang = htmlspecialchars($cleanLang, ENT_QUOTES, 'UTF-8');
            $displayLang = $cleanLang === 'code' ? 'TEXT' : strtoupper($lang);
            $escapedCode = htmlspecialchars(trim($matches[2]), ENT_QUOTES, 'UTF-8');
            $codeBlocks[$placeholder] =
                '<div class="vscode-code-block" data-highlighted="false">'
                . '<div class="vscode-code-header">'
                . '<span class="vscode-code-lang">&lt;/&gt; ' . $displayLang . '</span>'
                . '<button type="button" class="vscode-copy-btn" onclick="window.copyVsCodeBlock(this)">Sao chép</button>'
                . '</div>'
                . '<pre><code class="language-' . $lang . '">' . $escapedCode . '</code></pre>'
                . '</div>';

            return $placeholder;
        }, $text);

        // 2. Tách và Parse Markdown Tables thành giao diện Bảng đẹp mắt
        $tableBlocks = [];
        $tablePattern = '/((?:^[ \t]*\|.+?\|[ \t]*\r?\n)+)/m';
        $text = preg_replace_callback($tablePattern, function ($matches) use (&$tableBlocks) {
            $block = trim($matches[1]);
            $lines = array_filter(array_map('trim', explode("\n", $block)));
            if (count($lines) < 2) return $matches[0];

            $headerLine = array_shift($lines);
            $separatorLine = array_shift($lines);
            if (!preg_match('/^\|[\s\-:|]+\|$/', $separatorLine)) return $matches[0];

            $headerCells = array_values(array_filter(array_map('trim', explode('|', $headerLine)), fn ($c) => $c !== ''));

            $html = '<div class="my-4 overflow-x-auto rounded-xl border border-indigo-200/80 bg-white shadow-xs">';
            $html .= '<table class="w-full text-left text-xs border-collapse">';
            $html .= '<thead class="bg-gradient-to-r from-indigo-50 to-indigo-100/70 text-indigo-950 font-black tracking-wide border-b border-indigo-200/80">';
            $html .= '<tr>';
            foreach ($headerCells as $h) {
                $hFormatted = preg_replace('/\*\*(.*?)\*\*/', '<strong class="keyword-highlight">$1</strong>', htmlspecialchars($h, ENT_QUOTES, 'UTF-8'));
                $html .= '<th class="px-4 py-3 border-r border-indigo-100/80 last:border-r-0 whitespace-nowrap">' . $hFormatted . '</th>';
            }
            $html .= '</tr></thead>';

            $html .= '<tbody class="divide-y divide-slate-100 text-slate-700">';
            foreach ($lines as $rowLine) {
                if (!str_contains($rowLine, '|')) continue;
                $cells = array_values(array_filter(array_map('trim', explode('|', $rowLine)), fn ($c) => $c !== ''));
                $html .= '<tr class="hover:bg-indigo-50/40 transition">';
                foreach ($cells as $c) {
                    $cFormatted = preg_replace('/\*\*(.*?)\*\*/', '<strong class="keyword-highlight">$1</strong>', htmlspecialchars($c, ENT_QUOTES, 'UTF-8'));
                    $html .= '<td class="px-4 py-2.5 border-r border-slate-100 last:border-r-0 align-top leading-relaxed">' . $cFormatted . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            $placeholder = '___TABLE_BLOCK_' . count($tableBlocks) . '___';
            $tableBlocks[$placeholder] = $html;
            return $placeholder;
        }, $text);

        // 2b. Escape toàn bộ HTML thô (vd: <section>, <main>, <article>, <div>) để không bị trình duyệt nuốt mất
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 3. Chuyển đổi headings '### Tiêu đề' hoặc '## Tiêu đề' thành khối tiêu đề đẹp
        $text = preg_replace('/^#{1,6}\s*(.+)$/m', '<div class="text-sm font-black text-slate-800 mt-3 mb-1.5 flex items-center gap-1.5">$1</div>', $text);

        // 4. Chuyển đổi đường kẻ ngang: ---
        $text = preg_replace('/^---+$/m', '<hr class="my-3 border-slate-200">', $text);

        // 5. In đậm từ khóa cần nhớ: **từ khóa**
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong class="keyword-highlight">$1</strong>', $text);

        // 6. Chuyển đổi bullet points đầu dòng '* ' hoặc '- ' thành biểu tượng danh sách đẹp
        $text = preg_replace('/^\s*[\*\-]\s+(.+)$/m', '<div class="flex items-start gap-2 my-1 text-slate-700"><span class="text-indigo-500 font-bold">•</span><span>$1</span></div>', $text);

        // 7. Loại bỏ các dấu * lẻ loi còn sót lại (không phải phép nhân số học giữa 2 số)
        $text = preg_replace('/(?<!\d)\*(?!\d)/', '', $text);

        // 8. Loại bỏ dấu ### còn sót lại nếu có
        $text = preg_replace('/#{2,}/', '', $text);

        // 9. Inline code: `code` (font & màu kiểu VS Code)
        $text = preg_replace('/`([^`]+)`/', '<code class="vscode-inline-code">$1</code>', $text);

        // 9b. Thẻ ảnh đang chờ vẽ bất đồng bộ [IMAGE: ...]
        $text = preg_replace(
            '/\[IMAGE:\s*[^\]]+\]/i',
            '<div class="ai-image-skeleton my-3 rounded-lg border border-slate-200 bg-slate-50 p-4 flex items-center gap-3 animate-pulse"><div class="h-10 w-10 rounded-md bg-slate-200 shrink-0"></div><div class="space-y-1.5 flex-1"><div class="h-3 w-40 rounded bg-slate-200"></div><div class="text-[11px] text-slate-500">Đang vẽ hình minh họa AI...</div></div></div>',
            $text
        );

        // 9b. Ảnh minh họa Markdown: ![alt](url)
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($m) {
            $alt = htmlspecialchars($m[1] ?: 'Hình ảnh minh họa AI', ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars(trim($m[2]), ENT_QUOTES, 'UTF-8');
            return '<div class="my-3 rounded-lg border border-slate-200 bg-white p-2 shadow-xs max-w-md">'
                . '<a href="' . $url . '" target="_blank" class="block overflow-hidden rounded">'
                . '<img src="' . $url . '" alt="' . $alt . '" class="w-full h-auto rounded object-contain">'
                . '</a>'
                . '<p class="mt-1.5 text-[11px] text-slate-500 text-center">' . $alt . ' (bấm để xem kích thước gốc)</p>'
                . '</div>';
        }, $text);

        // 10. Xuống dòng (không ảnh hưởng đến các khối table và code đã tách)
        $text = nl2br($text);

        // 11. Trả lại các khối Table đẹp mắt
        foreach ($tableBlocks as $placeholder => $tableHtml) {
            $text = str_replace($placeholder, $tableHtml, $text);
        }

        // 12. Trả lại các khối Code Blocks nguyên bản
        foreach ($codeBlocks as $placeholder => $codeHtml) {
            $text = str_replace($placeholder, $codeHtml, $text);
        }

        return $text;
    }
}
