<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GEMINI EMBEDDING SERVICE
 * Sinh vector embedding cho văn bản bằng Google Gemini Embedding API,
 * phục vụ RAG (Retrieval-Augmented Generation) của AI Student Support.
 * Nếu chưa cấu hình API Key, service trả về null để tầng gọi tự fallback sang tìm từ khóa.
 */
class GeminiEmbeddingService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        // Model embedding của Gemini. Có thể đổi qua GEMINI_EMBED_MODEL.
        $this->model = (string) env('GEMINI_EMBED_MODEL', 'gemini-embedding-001');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Nhúng một đoạn văn bản thành vector float. Trả về null nếu không gọi được.
     */
    public function embed(string $text, ?string $taskType = null): ?array
    {
        if (! $this->isConfigured() || trim($text) === '') {
            return null;
        }

        $modelPath = str_starts_with($this->model, 'models/') ? $this->model : "models/{$this->model}";
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
            $response = Http::timeout(20)->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
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
        if ($n === 0) {
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
