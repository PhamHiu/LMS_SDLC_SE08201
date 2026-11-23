<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bộ lọc kiểm duyệt ngôn từ không phù hợp (chửi tục, xúc phạm, vô lễ) bằng cả Tiếng Việt & Tiếng Anh.
 * Kết hợp 2 tầng:
 *  - Tầng 1: Bộ quy tắc nhanh (có dấu, không dấu, viết tắt, lách ký tự, xưng hô vô lễ mày/tao, xúc phạm).
 *  - Tầng 2: AI Semantic Moderation (tùy chọn khi có Vilao key) bắt các câu xúc phạm/chế giễu mới không cần hardcode.
 * Che mờ nội dung vi phạm bằng thẻ [[BLUR]]...[[/BLUR]] (CSS filter: blur).
 */
class ContentModerationService
{
    public const WARNING_CHAT = '⚠️ **Cảnh báo:** Nội dung em vừa gửi chứa ngôn từ không phù hợp với môi trường học tập và đã bị hệ thống tự động làm mờ. Em vui lòng sử dụng ngôn từ lịch sự, tôn trọng để thầy có thể hỗ trợ tốt nhất nhé!';

    public const WARNING_COMMENT = 'Cảnh báo: Bình luận của bạn chứa ngôn từ không phù hợp với môi trường học tập và đã bị hệ thống tự động làm mờ. Vui lòng giữ thái độ văn minh, tôn trọng khi trao đổi trên LMS.';

    /**
     * Kiểm duyệt văn bản: phát hiện từ tục/xúc phạm/vô lễ (Việt + Anh), đánh dấu làm mờ [[BLUR]]...[[/BLUR]].
     *
     * @return array{flagged: bool, masked_text: string, matched: array<int, string>, warning: string}
     */
    public function moderate(string $text, string $context = 'chat'): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return [
                'flagged' => false,
                'masked_text' => $text,
                'matched' => [],
                'warning' => '',
            ];
        }

        $matched = [];
        $masked = $text;

        foreach ($this->patterns() as $pattern) {
            $masked = (string) preg_replace_callback($pattern, function (array $m) use (&$matched) {
                $matched[] = mb_strtolower($m[0]);
                return '[[BLUR]]' . $m[0] . '[[/BLUR]]';
            }, $masked);
        }

        // Gộp các thẻ [[BLUR]] lồng nhau nếu có
        $masked = (string) preg_replace('/\[\[BLUR\]\](?:\[\[BLUR\]\])+/u', '[[BLUR]]', $masked);
        $masked = (string) preg_replace('/\[\[\/BLUR\]\](?:\[\[\/BLUR\]\])+/u', '[[/BLUR]]', $masked);

        $flagged = ! empty($matched);

        // Tầng 2: AI Semantic Moderation cho các câu xúc phạm gián tiếp / sáng tạo mới (chỉ chạy ở runtime, không cản unit test offline)
        if (! $flagged && ! app()->runningUnitTests() && $this->shouldCheckSemanticAbuse($trimmed)) {
            if ($this->isSemanticallyAbusive($trimmed)) {
                $flagged = true;
                $matched[] = 'semantic_abuse';
                $masked = '[[BLUR]]' . $trimmed . '[[/BLUR]]';
            }
        }

        if ($flagged) {
            Log::warning('ContentModeration flagged inappropriate language', [
                'context' => $context,
                'matched_count' => count($matched),
            ]);
        }

        return [
            'flagged' => $flagged,
            'masked_text' => $masked,
            'matched' => array_values(array_unique($matched)),
            'warning' => $flagged
                ? ($context === 'comment' ? self::WARNING_COMMENT : self::WARNING_CHAT)
                : '',
        ];
    }

    /**
     * Render chuỗi có chứa [[BLUR]]...[[/BLUR]] thành HTML an toàn với hiệu ứng làm mờ CSS.
     */
    public static function renderHtml(string $text): string
    {
        $escaped = e($text);

        return (string) preg_replace(
            '/\[\[BLUR\]\]([\s\S]*?)\[\[\/BLUR\]\]/u',
            '<span title="Nội dung bị làm mờ do ngôn từ không phù hợp" style="filter: blur(5px); user-select: none; -webkit-user-select: none; pointer-events: none; display: inline-block; padding: 0 3px;">$1</span>',
            $escaped
        );
    }

    /**
     * Danh sách regex có ranh giới từ chặt (UTF-8):
     * - Chặn xưng hô vô lễ ("mày", "tao" có dấu — KHÔNG bắt nhầm "máy", "tạo", "táo").
     * - Chặn chửi tục, xúc phạm bằng con vật ("con chó", "con lợn", "đồ chó", "súc sinh"...).
     * - Chặn biến thể lách ("lol", "loz", "l0n", "cái l", "đm", "vcl", "f*ck"...).
     * - Tránh false positive ("class", "assignment", "document", "các", "buổi", "lớn", "luôn").
     *
     * @return array<int, string>
     */
    private function patterns(): array
    {
        $bStart = '(?<![\p{L}\p{N}])';
        $bEnd = '(?![\p{L}\p{N}])';

        return [
            // === TIẾNG VIỆT: XƯNG HÔ VÔ LỄ & CHỬI TỤC CÓ DẤU ===
            "/{$bStart}(?:mày|tao|địt|đụ|đéo|lồn|cặc|buồi|đĩ|điếm|chó\s+đẻ|con\s+chó|thằng\s+chó|đồ\s+chó|con\s+lợn|đồ\s+lợn|thằng\s+lợn|con\s+heo\s+này|súc\s+vật|súc\s+sinh|khốn\s+nạn|mất\s+dạy|vô\s+học|bố\s+láo|láo\s+toét|óc\s+chó|ngu\s+vãi|ngu\s+như\s+chó|ngu\s+si|ngu\s+dốt|đồ\s+ngu|thằng\s+ngu|con\s+ngu|đồ\s+điên|thằng\s+điên|con\s+điên|mẹ\s+mày|bố\s+mày|cút\s+mẹ|cút\s+đi|vãi\s+lồn|vãi\s+cặc|cái\s+lồn|cái\s+cặc|đm|đmm|đcm|đkm|đệt\s+mẹ){$bEnd}/iu",

            // === TIẾNG VIỆT: KHÔNG DẤU / VIẾT TẮT / LÁCH KÝ TỰ (BAO GỒM LOL / LOZ) ===
            "/{$bStart}(?:dit\s*me|dit\s*con\s*me|du\s*ma|duma|du\s*me|dume|deo\s+hieu|deo\s+biet|deo\s+can|deo\s+co|mat\s+day|vo\s+hoc|bo\s+lao|oc\s+cho|cho\s+de|con\s+cho|thang\s+cho|do\s+cho|con\s+lon|do\s+lon|thang\s+lon|khon\s+nan|suc\s+vat|suc\s+sinh|do\s+ngu|thang\s+ngu|con\s+ngu|ngu\s+si|ngu\s+dot|con\s+di|me\s+may|bo\s+may|cut\s+me|cut\s+di|vai\s+lon|vai\s+lol|vai\s+loz|vai\s+cac|cai\s+lon|cai\s+lol|cai\s+loz|cai\s+cac|con\s+cac){$bEnd}/iu",
            "/{$bStart}(?:d[\.\*_\-]?m+|d[\.\*_\-]?c[\.\*_\-]?m+|d[\.\*_\-]?k[\.\*_\-]?m+|đ[\.\*_\-]?m+|đ[\.\*_\-]?c[\.\*_\-]?m+|v[\.\*_\-]?c[\.\*_\-]?l|v[\.\*_\-]?k[\.\*_\-]?l|c[\.\*_\-]?l[\.\*_\-]?m+|c[\.\*_\-]?m[\.\*_\-]?m|c[\.\*_\-]?c|v[\.\*_\-]?l|l[o0]z|l0n|cái\s+lol|cái\s+loz|đ[\*\.]+t|l[\*\.]+n|c[\*\.]+c){$bEnd}/iu",
            "/{$bStart}(?:dit|deo|lon\s+me|lon\s+que|lol\s+gì|lol\s+gi){$bEnd}/iu",

            // === TIẾNG ANH (BAO GỒM LÁCH DẤU SAO / CHẤM) ===
            "/{$bStart}(?:mother\s*f[\*\.u]+ck(?:er|ing|ed)?|f[\*\.u]+c[\*\.]*k(?:ing|er|ed|s)?|fck(?:ing|er)?|fuk(?:ing|er)?|bull\s*sh[\*\.i]+t|sh[\*\.i]+t(?:ty|s|head)?|b[\*\.i]+tch(?:es|ing)?|son\s+of\s+a\s+bitch|a[\*\.s]+s\s*h[\*\.o]+le(?:s)?|bastard(?:s)?|dick\s*head(?:s)?|c[\*\.u]+nt(?:s)?|whore(?:s)?|slut(?:s)?|dumb\s*ass(?:es)?|jack\s*ass|stfu|wtf|shut\s+up|idiot(?:s)?|moron(?:s)?|retard(?:ed)?){$bEnd}/iu",
        ];
    }

    /**
     * Chỉ gọi AI Semantic Moderation khi câu có dấu hiệu công kích / xúc phạm ngầm (để không tốn 1.5-2.5s cho câu hỏi bình thường).
     */
    private function shouldCheckSemanticAbuse(string $text): bool
    {
        if (mb_strlen($text) > 220) {
            return false;
        }

        // Chỉ kích hoạt Tầng 2 khi có từ khóa nghi ngờ công kích, xúc phạm, thô lỗ gián tiếp
        return (bool) preg_match(
            '/(?:^|[^\p{L}\p{N}])(đồ\s|thằng\s|con\s+(?!số|trỏ|đường|người|cái|vật\s+lý)|cút|xéo|biến\s+đi|câm|ngu|điên|khùng|hâm|rác|phế|đểu|hèn|rẻ\s+rách|mặt\s+dày|chó|lợn|heo|súc|đập|đấm|tát|chết\s+đi|vô\s+dụng|bố\s+láo|mất\s+nết|wtf|dumb|stupid|trash|suck|shut\s+up|ugly|hate\s+you|kill)(?:$|[^\p{L}\p{N}])/iu',
            $text
        );
    }

    private function isSemanticallyAbusive(string $text): bool
    {
        $key = env('DEEPSEEK_KEY_MAIN') ?: env('OPENAI_API_KEY');
        if (empty($key)) {
            return false;
        }

        $baseUrl = rtrim((string) env('OPENAI_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $model = env('DEEPSEEK_KEY_MAIN')
            ? env('DEEPSEEK_MODEL_MAIN', 'deepseek-v4.1-flash')
            : env('OPENAI_MODEL', 'vgpt/gpt-6.1-sol');

        try {
            $response = Http::timeout(2.5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.0,
                    'max_tokens' => 25,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Bạn là bộ kiểm duyệt môi trường giáo dục. Nếu tin nhắn chứa lời chửi tục, xúc phạm, miệt thị, gọi đối phương bằng tên con vật hoặc thái độ vô lễ (tiếng Việt/Anh), trả về {"abusive":true}. Ngược lại trả về {"abusive":false}. Chỉ trả JSON thuần.',
                        ],
                        ['role' => 'user', 'content' => $text],
                    ],
                ]);

            if (! $response->successful()) {
                return false;
            }
            $content = (string) $response->json('choices.0.message.content', '');
            return (bool) preg_match('/"abusive"\s*:\s*true/i', $content);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
