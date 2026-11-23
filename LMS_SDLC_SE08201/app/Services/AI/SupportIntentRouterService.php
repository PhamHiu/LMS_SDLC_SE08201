<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LLM Structured Intent & Tool Router cho BTEC AI Support.
 *
 * Nguyên tắc bảo mật (Zero-PII):
 * - CHỈ gửi duy nhất câu hỏi của người dùng + danh mục tên Tool backend hợp lệ.
 * - KHÔNG gửi điểm số, lịch học, email, mã SV, danh sách lớp hay lịch sử riêng tư ra ngoài.
 * - Backend luôn kiểm tra lại quyền (RBAC) trước khi đọc dữ liệu từ CSDL.
 */
class SupportIntentRouterService
{
    public const VALID_ACADEMIC_INTENTS = [
        'SCHEDULE',
        'INSTRUCTOR',
        'SUBJECTS',
        'GRADES',
        'ATTENDANCE',
        'ATTENDANCE_HYPOTHETICAL',
        'SCHEDULE_CHANGE',
    ];

    /**
     * Phân loại ý định câu hỏi bằng LLM (JSON structured output).
     * Trả về null nếu không cấu hình API key hoặc API lỗi/timeout.
     */
    public function classify(string $userMessage): ?array
    {
        $cleanKey = mb_strtolower(trim(preg_replace('/\s+/', ' ', $userMessage) ?? ''));
        $cacheKey = 'support_intent_router_v2_' . md5($cleanKey);
        if (! app()->runningUnitTests()) {
            $cached = cache()->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $providers = $this->resolveProviders();
        if (empty($providers)) {
            return null;
        }

        $systemPrompt = <<<'PROMPT'
Bạn là bộ định tuyến ý định (Intent Router) cho hệ thống Smart LMS của trường đại học.
Nhiệm vụ duy nhất: đọc câu hỏi của người dùng và trả về ĐÚNG 1 đối tượng JSON (không markdown, không giải thích thêm).

Danh sách intents hợp lệ:
- "SCHEDULE": hỏi lịch học, buổi học, ca học, phòng học, thời khóa biểu (VD: "mai em có buổi học không", "tuần này học khi nào", "sáng mai có tiết không").
- "INSTRUCTOR": hỏi ai dạy môn/lớp nào, tên giảng viên/thầy/cô phụ trách (VD: "thầy nào dạy em môn dữ liệu giải thuật", "ai đứng lớp Java").
- "SUBJECTS": hỏi danh sách môn học/lớp đang học kỳ này.
- "GRADES": hỏi điểm số, bảng điểm, đã chấm bài chưa, bài tập được mấy điểm.
- "ATTENDANCE": hỏi tình trạng điểm danh, tổng số buổi của 1 môn/lớp, nghỉ quá 20% môn X là bao nhiêu buổi, được nghỉ tối đa mấy buổi môn X, đã nghỉ mấy buổi, tỷ lệ vắng (VD: "môn giải thuật em nghỉ quá 20% là bao nhiêu buổi", "môn Java có bao nhiêu buổi", "em còn nghỉ được mấy buổi").
- "ATTENDANCE_HYPOTHETICAL": giả định nghỉ thêm buổi ("nếu mai nghỉ thêm 1 buổi có trượt không").
- "SCHEDULE_CHANGE": hỏi có đổi phòng, đổi lịch, chuyển ca, nghỉ bù không.
- "POLICY_OR_GUIDE": CHỈ dùng khi hỏi quy chế chung KHÔNG gắn với một môn học cụ thể của sinh viên, hoặc thủ tục hành chính (bảo lưu, học phí, phúc khảo, khôi phục điểm danh, cách dùng LMS).
- "STUDY_ENCOURAGEMENT": sinh viên than mệt, kêu học nhiều, áp lực, nản, chán học, nhiều bài tập/deadline, lo thi cử, sợ trượt, buồn ngủ, căng thẳng về việc học (VD: "học nhiều thế", "mệt quá", "chán học quá", "nhiều bài tập quá", "áp lực quá", "sợ rớt môn quá").
- "GREETING_OR_THANKS": lời chào hỏi, cảm ơn, tạm biệt xã giao ngắn gọn (VD: "chào bạn", "hello", "cảm ơn nhé", "ok thanks").
- "OUT_OF_SCOPE": câu hỏi hoàn toàn không liên quan học tập/LMS (thời tiết, mua sắm, bóng đá, vô nghĩa, hoặc quá mơ hồ không xác định được).

Định dạng JSON bắt buộc:
{
  "intents": ["..."],
  "subject_hint": "tên môn học nếu câu hỏi có nhắc tới, ngược lại null",
  "date_hint": "today | tomorrow | day_after_tomorrow | this_week | next_week | YYYY-MM-DD | null",
  "asks_other_person": false,
  "confidence": 0.0
}

Quy tắc:
- Nếu người dùng hỏi dữ liệu cá nhân của NGƯỜI KHÁC (bạn bè, sinh viên khác), đặt "asks_other_person": true.
- Chỉ trả JSON thuần hợp lệ.
PROMPT;

        foreach ($providers as $provider) {
            try {
                $response = Http::connectTimeout(2.0)->timeout(3.2)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $provider['key'],
                        'Content-Type' => 'application/json',
                    ])
                    ->post($provider['base_url'] . '/chat/completions', [
                        'model' => $provider['model'],
                        'temperature' => 0.0,
                        'max_tokens' => 110,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => trim($userMessage)],
                        ],
                    ]);

                if (! $response->successful()) {
                    continue;
                }

                $content = $response->json('choices.0.message.content');
                if (! is_string($content) || trim($content) === '') {
                    continue;
                }

                $parsed = $this->parseJson($content);
                if ($parsed !== null) {
                    if (! app()->runningUnitTests()) {
                        cache()->put($cacheKey, $parsed, 86400);
                    }
                    return $parsed;
                }
            } catch (\Throwable $e) {
                Log::info('SupportIntentRouter fallback: ' . $e->getMessage());
            }
        }

        return null;
    }

    private function resolveProviders(): array
    {
        $baseUrl = rtrim((string) env('OPENAI_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $providers = [];

        $deepseekKey = env('DEEPSEEK_KEY_MAIN');
        if (! empty($deepseekKey)) {
            $providers[] = [
                'base_url' => $baseUrl,
                'key' => $deepseekKey,
                'model' => env('DEEPSEEK_MODEL_MAIN', 'deepseek-v4.1-flash'),
            ];
        }

        $openaiKey = env('OPENAI_API_KEY');
        if (! empty($openaiKey)) {
            $providers[] = [
                'base_url' => $baseUrl,
                'key' => $openaiKey,
                'model' => env('OPENAI_MODEL', 'vgpt/gpt-6.1-sol'),
            ];
        }

        return $providers;
    }

    private function parseJson(string $raw): ?array
    {
        $clean = trim($raw);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $clean, $m)) {
            $clean = trim($m[1]);
        }

        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($clean, $start, $end - $start + 1), true);
        if (! is_array($decoded)) {
            return null;
        }

        $allowed = array_merge(self::VALID_ACADEMIC_INTENTS, ['POLICY_OR_GUIDE', 'OUT_OF_SCOPE']);
        $intents = [];
        foreach ((array) ($decoded['intents'] ?? []) as $item) {
            $upper = strtoupper(trim((string) $item));
            if (in_array($upper, $allowed, true)) {
                $intents[] = $upper;
            }
        }

        return [
            'intents' => array_values(array_unique($intents)),
            'subject_hint' => is_string($decoded['subject_hint'] ?? null) && trim($decoded['subject_hint']) !== ''
                ? trim($decoded['subject_hint'])
                : null,
            'date_hint' => is_string($decoded['date_hint'] ?? null) && trim($decoded['date_hint']) !== ''
                ? trim($decoded['date_hint'])
                : null,
            'asks_other_person' => (bool) ($decoded['asks_other_person'] ?? false),
            'confidence' => max(0.0, min(1.0, (float) ($decoded['confidence'] ?? 0.0))),
        ];
    }
}
