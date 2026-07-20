<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ( Đăng thêm ) Service kết nối Google Gemini AI để sinh câu hỏi trắc nghiệm
class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    /**
     * Generate questions based on topic, count, and optional document text or file.
     */
    public function generateQuestions(string $topic, int $count, ?string $documentText = null, ?array $inlineData = null): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('GEMINI_API_KEY is not configured in .env');
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
            $userPrompt .= "TÀI LIỆU THAM KHẢO:\n```\n$documentText\n```\n";
        }
        $userPrompt .= "Hãy sinh ra danh sách $count câu hỏi trắc nghiệm dưới dạng mảng JSON.";

        $parts = [];
        if ($inlineData) {
            $parts[] = ['inlineData' => $inlineData];
        }
        $parts[] = ['text' => $userPrompt];

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => [
                [
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2, // Low temperature for high precision/grounding
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])->post($this->baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gemini API Error: ' . $response->body());
                throw new \Exception('Failed to communicate with Gemini API');
            }

            $data = $response->json();
            $textResult = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
            
            // Clean markdown json formatting if Gemini disobeys rule 2
            $textResult = trim($textResult);
            if (str_starts_with($textResult, '```json')) {
                $textResult = substr($textResult, 7);
                $textResult = substr($textResult, 0, strrpos($textResult, '```'));
                $textResult = trim($textResult);
            } elseif (str_starts_with($textResult, '```')) {
                $textResult = substr($textResult, 3);
                $textResult = substr($textResult, 0, strrpos($textResult, '```'));
                $textResult = trim($textResult);
            }

            $decoded = json_decode($textResult, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini JSON Parse Error. Raw output: ' . $textResult);
                throw new \Exception('Invalid JSON format returned from AI');
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
