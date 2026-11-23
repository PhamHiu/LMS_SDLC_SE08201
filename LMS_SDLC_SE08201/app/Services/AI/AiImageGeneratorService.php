<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dịch vụ tạo ảnh minh họa học tập bằng AI (IMAGE_API_KEY — bh5/gemini-3.1-flash-lite-image).
 * Hỗ trợ cả 2 chuẩn phản hồi OpenAI-compatible: `data[0].b64_json` và `data[0].url`.
 */
class AiImageGeneratorService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('IMAGE_API_KEY', '');
        $this->baseUrl = rtrim((string) env('VILAO_BASE_URL', 'https://api.vilao.ai/v1'), '/');
        $this->model = (string) env('IMAGE_MODEL', 'gemini-3.1-flash-lite-image');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Sinh ảnh minh họa và lưu về `storage/app/public/ai_tutor_images/`, trả về đường dẫn web `/storage/...`.
     */
    public function generateIllustration(string $prompt): ?string
    {
        if (! $this->isConfigured() || trim($prompt) === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->connectTimeout(8)->timeout(45)->post($this->baseUrl . '/images/generations', [
                'model' => $this->model,
                'prompt' => trim($prompt),
                'size' => '1024x1024',
            ]);

            if (! $response->successful()) {
                Log::warning('AiImageGeneratorService HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 200));
                return null;
            }

            $json = $response->json();
            $item = $json['data'][0] ?? null;
            if (! is_array($item)) {
                return null;
            }

            $binary = null;
            $ext = 'png';

            if (! empty($item['b64_json'])) {
                $b64 = $item['b64_json'];
                if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,/', $b64, $m)) {
                    $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                    $b64 = substr($b64, strpos($b64, ',') + 1);
                }
                $binary = base64_decode($b64, true);
                if (is_string($binary) && str_starts_with($binary, "\xFF\xD8\xFF")) {
                    $ext = 'jpg';
                } elseif (is_string($binary) && str_starts_with($binary, 'RIFF') && str_contains(substr($binary, 0, 12), 'WEBP')) {
                    $ext = 'webp';
                }
            } elseif (! empty($item['url'])) {
                $imgResp = Http::timeout(20)->get($item['url']);
                if ($imgResp->successful()) {
                    $binary = $imgResp->body();
                    $ct = (string) $imgResp->header('Content-Type');
                    if (str_contains($ct, 'jpeg') || str_contains($ct, 'jpg')) {
                        $ext = 'jpg';
                    } elseif (str_contains($ct, 'webp')) {
                        $ext = 'webp';
                    }
                } else {
                    return $item['url'];
                }
            }

            if (! $binary) {
                return null;
            }

            $filename = 'ai_tutor_images/' . date('Ymd_His') . '_' . Str::random(8) . '.' . $ext;
            Storage::disk('public')->put($filename, $binary);

            return '/storage/' . $filename;
        } catch (\Throwable $e) {
            Log::warning('AiImageGeneratorService exception: ' . $e->getMessage());
            return null;
        }
    }
}
