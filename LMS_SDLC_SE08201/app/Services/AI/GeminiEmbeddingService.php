<?php

namespace App\Services\AI;

// =========================================================================
// KHAI BÁO THƯ VIỆN LARAVEL CORE PHỤC VỤ KẾT NỐI EMBEDDING API
// =========================================================================
use Illuminate\Support\Facades\Http; // Thư viện HTTP Client (Guzzle wrapper): gửi request POST tới Python FastAPI (/embed), HuggingFace Router (AITeamVN/Vietnamese_Embedding_v2) và Google Gemini API
use Illuminate\Support\Facades\Log;  // Thư viện Logging hệ thống: ghi nhật ký cảnh báo vào storage/logs/laravel.log khi một tầng Embedding gặp lỗi để tự động chuyển tầng (fallback)

/**
 * MULTI-PROVIDER VIETNAMESE EMBEDDING SERVICE
 * Ưu tiên sử dụng mô hình tiếng Việt chuyên biệt: AITeamVN/Vietnamese_Embedding_v2
 * 1. Local FastAPI ML Service (/embed) chạy sentence-transformers (AITeamVN/Vietnamese_Embedding_v2).
 * 2. HuggingFace Inference API (AITeamVN/Vietnamese_Embedding_v2) qua HF_API_TOKEN.
 * 3. Fallback: Google Gemini Embedding API (gemini-embedding-001) qua GEMINI_API_KEY.
 */
class GeminiEmbeddingService
{
    public const VI_EMBED_MODEL = 'AITeamVN/Vietnamese_Embedding_v2';

    private string $hfToken;
    private string $hfModel;
    private string $mlServiceUrl;
    private string $geminiApiKey;
    private string $geminiModel;
    private string $lastUsedModel;

    public function __construct()
    {
        $this->hfToken = (string) env('HF_API_TOKEN', '');
        $this->hfModel = (string) env('VI_EMBED_MODEL', self::VI_EMBED_MODEL);
        $this->mlServiceUrl = rtrim((string) env('ML_SERVICE_URL', 'http://127.0.0.1:8001'), '/');

        $this->geminiApiKey = (string) env('GEMINI_API_KEY', '');
        $this->geminiModel = (string) env('GEMINI_EMBED_MODEL', 'gemini-embedding-001');
        $this->lastUsedModel = $this->hfModel;
    }

    public function isConfigured(): bool
    {
        return $this->hfToken !== '' || $this->geminiApiKey !== '' || $this->mlServiceUrl !== '';
    }

    public function model(): string
    {
        return $this->lastUsedModel;
    }

    /**
     * Nhúng văn bản thành vector float theo thứ tự ưu tiên:
     * 1) Local ML Service (AITeamVN/Vietnamese_Embedding_v2)
     * 2) HuggingFace Inference API (AITeamVN/Vietnamese_Embedding_v2)
     * 3) Gemini Embedding API
     */
    public function embed(string $text, ?string $taskType = null): ?array
    {
        $clean = trim($text);
        if ($clean === '') {
            return null;
        }

        // Cache vector 24 giờ để không bao giờ phải gọi lại API cho cùng 1 văn bản / học liệu
        $cacheKey = 'vi_embed_v2_' . md5($clean . '|' . ($taskType ?? ''));
        $cached = cache()->get($cacheKey);
        if (is_array($cached) && ! empty($cached)) {
            return $cached;
        }

        // 1. Thử Local FastAPI ML Service (có circuit breaker 10 phút nếu cổng 8001 chưa bật)
        if ($this->mlServiceUrl !== '' && ! cache()->get('vi_embed_ml_down')) {
            $vec = $this->embedViaLocalMl($clean);
            if ($vec !== null) {
                $this->lastUsedModel = $this->hfModel;
                cache()->put($cacheKey, $vec, 86400);
                return $vec;
            }
            cache()->put('vi_embed_ml_down', true, 600);
        }

        // 2. Thử HuggingFace Inference API (có circuit breaker 5 phút nếu lỗi/timeout)
        if ($this->hfToken !== '' && ! cache()->get('vi_embed_hf_down')) {
            $vec = $this->embedViaHuggingFace($clean);
            if ($vec !== null) {
                $this->lastUsedModel = $this->hfModel;
                cache()->put($cacheKey, $vec, 86400);
                return $vec;
            }
            cache()->put('vi_embed_hf_down', true, 300);
        }

        // 3. Fallback sang Gemini Embedding API (có circuit breaker 3 phút nếu lỗi/429)
        if ($this->geminiApiKey !== '' && ! cache()->get('vi_embed_gemini_down')) {
            $vec = $this->embedViaGemini($clean, $taskType);
            if ($vec !== null) {
                $this->lastUsedModel = $this->geminiModel;
                cache()->put($cacheKey, $vec, 86400);
                return $vec;
            }
            cache()->put('vi_embed_gemini_down', true, 180);
        }

        return null;
    }

    private function embedViaLocalMl(string $text): ?array
    {
        try {
            $response = Http::connectTimeout(0.5)->timeout(1.2)->post($this->mlServiceUrl . '/embed', [
                'text' => mb_substr($text, 0, 8000),
            ]);

            if ($response->successful()) {
                $values = $response->json('embedding');
                if (is_array($values) && ! empty($values)) {
                    return array_map('floatval', $values);
                }
            }
        } catch (\Throwable $e) {
            // ML service chưa bật -> chuyển tiếp sang HF / Gemini
        }

        return null;
    }

    private function embedViaHuggingFace(string $text): ?array
    {
        $url = "https://router.huggingface.co/hf-inference/models/{$this->hfModel}/pipeline/feature-extraction";

        try {
            $response = Http::connectTimeout(1.5)->timeout(3.5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->hfToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'inputs' => mb_substr($text, 0, 8000),
                'options' => ['wait_for_model' => false],
            ]);

            if (! $response->successful()) {
                Log::warning('HF Vietnamese_Embedding_v2 non-200 (' . $response->status() . '): ' . $response->body());
                return null;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data)) {
                return null;
            }

            // Nếu trả về mảng 2 chiều [[f1, f2, ...]], lấy phần tử đầu
            $vector = is_array($data[0] ?? null) ? $data[0] : $data;
            if (! is_array($vector) || empty($vector) || ! is_numeric($vector[0] ?? null)) {
                return null;
            }

            return array_map('floatval', $vector);
        } catch (\Throwable $e) {
            Log::warning('HF Vietnamese_Embedding_v2 exception: ' . $e->getMessage());
            return null;
        }
    }

    private function embedViaGemini(string $text, ?string $taskType = null): ?array
    {
        $modelPath = str_starts_with($this->geminiModel, 'models/') ? $this->geminiModel : "models/{$this->geminiModel}";
        $url = "https://generativelanguage.googleapis.com/v1beta/{$modelPath}:embedContent";

        $payload = [
            'model' => $modelPath,
            'content' => [
                'parts' => [
                    ['text' => mb_substr($text, 0, 8000)],
                ],
            ],
        ];
        if ($taskType) {
            $payload['taskType'] = $taskType;
        }

        try {
            $response = Http::connectTimeout(1.5)->timeout(3.5)->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->geminiApiKey,
            ])->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Gemini Embedding non-200 (' . $response->status() . '): ' . $response->body());
                return null;
            }

            $values = $response->json('embedding.values');
            if (! is_array($values) || empty($values)) {
                return null;
            }

            return array_map('floatval', $values);
        } catch (\Throwable $e) {
            Log::warning('Gemini Embedding exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Độ tương đồng cosine giữa hai vector (trả về trong khoảng [-1, 1]).
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0 || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $normA += $x * $x;
            $normB += $y * $y;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
