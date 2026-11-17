<?php

namespace App\Console\Commands;

use App\Models\BtecKbEmbedding;
use App\Services\AI\GeminiEmbeddingService;
use Illuminate\Console\Command;

/**
 * Đánh chỉ mục vector cho kho tri thức BTEC (RAG).
 * Đọc storage/btec_ai_kb/kb_chunks.jsonl, nhúng từng đoạn bằng Gemini Embedding
 * và lưu vector vào bảng btec_kb_embeddings trong MySQL.
 *
 * Dùng: php artisan btec:index-kb [--fresh]
 */
class IndexBtecKnowledgeBase extends Command
{
    protected $signature = 'btec:index-kb {--fresh : Xóa toàn bộ chỉ mục cũ trước khi nhúng lại}';

    protected $description = 'Nhúng (embedding) kho tri thức BTEC và lưu vector vào MySQL phục vụ RAG';

    public function handle(GeminiEmbeddingService $embeddings): int
    {
        if (! $embeddings->isConfigured()) {
            $this->error('Chưa cấu hình GEMINI_API_KEY trong .env — không thể tạo embedding.');
            return self::FAILURE;
        }

        $path = storage_path('btec_ai_kb/kb_chunks.jsonl');
        if (! file_exists($path)) {
            $this->error("Không tìm thấy file KB: {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            BtecKbEmbedding::query()->delete();
            $this->warn('Đã xóa toàn bộ chỉ mục embedding cũ.');
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->error('Không mở được file KB.');
            return self::FAILURE;
        }

        $model = $embeddings->model();
        $indexed = 0;
        $skipped = 0;
        $failed = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $chunk = json_decode($line, true);
            if (! is_array($chunk) || empty($chunk['chunk_id'])) {
                $skipped++;
                continue;
            }

            $title = (string) ($chunk['title'] ?? '');
            $text = (string) ($chunk['text'] ?? '');
            $content = trim($title . "\n" . $text);

            $vector = $embeddings->embed($content, 'RETRIEVAL_DOCUMENT');
            if ($vector === null) {
                $failed++;
                $this->warn("  ✗ Nhúng thất bại: {$chunk['chunk_id']}");
                continue;
            }

            BtecKbEmbedding::updateOrCreate(
                ['chunk_id' => $chunk['chunk_id']],
                [
                    'topic_id' => $chunk['topic_id'] ?? null,
                    'title' => $title,
                    'content' => $content,
                    'source_refs' => $chunk['source_refs'] ?? [],
                    'embedding_model' => $model,
                    'dimension' => count($vector),
                    'embedding' => json_encode($vector),
                ]
            );

            $indexed++;
            $this->info("  ✓ {$chunk['chunk_id']} ({$chunk['topic_id']}) — " . count($vector) . ' chiều');
        }

        fclose($handle);

        $this->newLine();
        $this->info("Hoàn tất: {$indexed} đoạn đã nhúng, {$skipped} bỏ qua, {$failed} lỗi. Model: {$model}");

        return $failed > 0 && $indexed === 0 ? self::FAILURE : self::SUCCESS;
    }
}
