<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ( Đăng thêm ) Service kết nối Google Gemini AI (có dự phòng Vilao AI) để sinh câu hỏi trắc nghiệm
class GeminiService
{
    private string $apiKey;
    private string $geminiModel;
    private string $vilaoApiKey;
    private string $vilaoBaseUrl;
    private string $vilaoModel;

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        $this->geminiModel = (string) env('GEMINI_MODEL', 'gemini-2.0-flash');
        $this->vilaoApiKey = (string) env('VILAO_API_KEY', '');
        $this->vilaoBaseUrl = rtrim((string) env('VILAO_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $this->vilaoModel = (string) env('VILAO_MODEL', 'vgpt/gpt-6.1-sol');
    }

    /**
     * Generate questions based on topic, count, and optional document text or file.
     */
    public function generateQuestions(string $topic, int $count, ?string $documentText = null, ?array $inlineData = null): array
    {
        if (empty($this->apiKey) && empty($this->vilaoApiKey)) {
            throw new \Exception('Chưa cấu hình API Key AI (GEMINI_API_KEY hoặc VILAO_API_KEY) trong .env');
        }

        $systemInstruction = "Bạn là một giáo sư đại học chuyên ngành khắt khe. Nhiệm vụ của bạn là tạo ra $count câu hỏi trắc nghiệm dựa trên chủ đề được giao. 
BẮT BUỘC TUÂN THỦ CÁC QUY TẮC SAU:
1. " . ($documentText ? "CHỈ ĐƯỢC PHÉP sử dụng kiến thức có TRONG TÀI LIỆU CUNG CẤP BÊN DƯỚI. Tuyệt đối không được bịa đặt (hallucinate) hay sử dụng kiến thức bên ngoài nếu tài liệu không đề cập." : "Đảm bảo kiến thức chính xác tuyệt đối theo chuẩn học thuật.") . "
2. Format output trả về BẮT BUỘC phải là một mảng JSON nguyên chất, KHÔNG bọc trong markdown block (như ```json ... ```). Hãy bắt đầu bằng dấu [ và kết thúc bằng dấu ].
3. Mỗi object trong mảng đại diện cho một câu hỏi và phải có chính xác các trường sau:
- \"content\": nội dung câu hỏi
- \"option_a\": đáp án A
- \"option_b\": đáp án B
- \"option_c\": đáp án C
- \"option_d\": đáp án D
- \"correct_answer\": một chữ cái in hoa (A, B, C, hoặc D)";

        $userPrompt = "Chủ đề: $topic\nSố lượng câu hỏi: $count\n\n";
        if ($documentText) {
            $userPrompt .= "TÀI LIỆU THAM KHẢO:\n```\n" . mb_substr($documentText, 0, 15000) . "\n```\n";
        }
        $userPrompt .= "Hãy sinh ra danh sách $count câu hỏi trắc nghiệm dưới dạng mảng JSON.";

        $lastError = null;

        // 1. Thử gọi Google Gemini API nếu có key hợp lệ
        if (! empty($this->apiKey)) {
            try {
                $parts = [];
                if ($inlineData) {
                    $parts[] = ['inlineData' => $inlineData];
                }
                $parts[] = ['text' => $userPrompt];

                $payload = [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemInstruction],
                        ],
                    ],
                    'contents' => [
                        [
                            'parts' => $parts,
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                    ],
                ];

                $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent";
                $response = Http::timeout(25)->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])->post($geminiUrl, $payload);

                if ($response->successful()) {
                    $textResult = (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
                    if ($textResult !== '') {
                        return $this->parseAndNormalizeQuestions($textResult);
                    }
                } else {
                    Log::warning('Gemini API non-200 (' . $response->status() . '), falling back to Vilao AI.');
                    $lastError = 'Gemini HTTP ' . $response->status();
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini Service warning, falling back to Vilao AI: ' . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        // 2. Dự phòng tự động sang Vilao AI (OpenAI-compatible API)
        if (! empty($this->vilaoApiKey)) {
            try {
                $response = Http::timeout(35)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->vilaoApiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->vilaoBaseUrl . '/chat/completions', [
                    'model' => $this->vilaoModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 3000,
                ]);

                if (! $response->successful()) {
                    Log::error('Vilao Quiz AI Error: ' . $response->body());
                    throw new \Exception('Không thể kết nối dịch vụ AI (' . $response->status() . ')');
                }

                $textResult = (string) ($response->json('choices.0.message.content') ?? '');

                return $this->parseAndNormalizeQuestions($textResult);
            } catch (\Throwable $e) {
                Log::error('Vilao Quiz AI Exception: ' . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        throw new \Exception($lastError ?: 'Không thể kết nối đến dịch vụ AI');
    }

    /**
     * Làm sạch chuỗi JSON trả về từ AI và chuẩn hóa các trường câu hỏi trắc nghiệm.
     */
    private function parseAndNormalizeQuestions(string $rawText): array
    {
        $cleaned = preg_replace('/<thought>[\s\S]*?<\/thought>/i', '', $rawText);
        $cleaned = trim((string) $cleaned);

        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $cleaned, $m)) {
            $cleaned = trim($m[1]);
        }

        $startPos = strpos($cleaned, '[');
        $endPos = strrpos($cleaned, ']');
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $cleaned = substr($cleaned, $startPos, $endPos - $startPos + 1);
        }

        $decoded = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            Log::error('AI Quiz JSON Parse Error. Raw output: ' . $rawText);
            throw new \Exception('Định dạng dữ liệu câu hỏi trả về từ AI không hợp lệ.');
        }

        $normalized = [];
        foreach ($decoded as $item) {
            if (! is_array($item) || empty($item['content'])) {
                continue;
            }
            $ans = strtoupper(trim((string) ($item['correct_answer'] ?? 'A')));
            $ans = substr($ans, 0, 1);
            if (! in_array($ans, ['A', 'B', 'C', 'D'], true)) {
                $ans = 'A';
            }

            $normalized[] = [
                'content' => trim((string) $item['content']),
                'option_a' => trim((string) ($item['option_a'] ?? '')),
                'option_b' => trim((string) ($item['option_b'] ?? '')),
                'option_c' => trim((string) ($item['option_c'] ?? '')),
                'option_d' => trim((string) ($item['option_d'] ?? '')),
                'correct_answer' => $ans,
            ];
        }

        if (empty($normalized)) {
            throw new \Exception('AI không trả về câu hỏi hợp lệ nào, vui lòng thử lại.');
        }

        return $normalized;
    }
}
