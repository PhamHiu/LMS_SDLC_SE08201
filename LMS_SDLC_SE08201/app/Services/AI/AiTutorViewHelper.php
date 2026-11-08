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

        // 1. Tách và bảo vệ các khối Code Blocks ```...```
        $codeBlocks = [];
        $text = preg_replace_callback('/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/', function ($matches) use (&$codeBlocks) {
            $placeholder = '___CODE_BLOCK_' . count($codeBlocks) . '___';
            $lang = htmlspecialchars($matches[1] ?: 'code', ENT_QUOTES, 'UTF-8');
            $code = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
            $codeBlocks[$placeholder] = '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
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

        // 9. Inline code: `code`
        $text = preg_replace('/`([^`]+)`/', '<code class="bg-slate-100 text-indigo-700 px-1.5 py-0.5 rounded text-xs">$1</code>', $text);

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
