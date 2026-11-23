<?php

namespace App\Http\Controllers\Admin;

// =========================================================================
// CONTROLLER QUẢN LÝ & CẬP NHẬT DỮ LIỆU RAG CHO BTEC AI STUDENT SUPPORT (ADMIN)
// =========================================================================
use App\Http\Controllers\Controller;
use App\Models\BtecKbEmbedding;              // Bảng MySQL lưu vector embedding của các chunk tri thức RAG
use App\Services\AI\GeminiEmbeddingService;  // Động cơ nhúng vector 3 tầng (AITeamVN/Vietnamese_Embedding_v2 -> HF -> Gemini)
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;      // Gọi lệnh `btec:index-kb --fresh` trực tiếp từ giao diện web
use Illuminate\Support\Facades\Cache;        // Xóa cache FAQ khi Admin cập nhật quy chế mới để AI trả lời ngay theo bản mới
use Illuminate\View\View;

class RagKnowledgeController extends Controller
{
    public function __construct(
        private readonly GeminiEmbeddingService $embeddingService,
    ) {
    }

    private function kbChunksPath(): string
    {
        return storage_path('btec_ai_kb/kb_chunks.jsonl');
    }

    /** Đọc toàn bộ chunks từ storage/btec_ai_kb/kb_chunks.jsonl */
    private function readChunks(): array
    {
        $path = $this->kbChunksPath();
        if (! file_exists($path)) {
            return [];
        }

        $chunks = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded) && ! empty($decoded['chunk_id'])) {
                $chunks[] = $decoded;
            }
        }

        return $chunks;
    }

    /** Ghi lại toàn bộ chunks ra file kb_chunks.jsonl (định dạng JSONL UTF-8) */
    private function writeChunks(array $chunks): void
    {
        $path = $this->kbChunksPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = array_map(
            fn (array $c) => json_encode($c, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            array_values($chunks)
        );

        file_put_contents($path, implode("\n", $lines) . "\n");
    }

    /** Nhúng vector cho 1 chunk đơn lẻ và lưu vào MySQL `btec_kb_embeddings` */
    private function embedSingleChunk(array $chunk): bool
    {
        $content = trim(($chunk['title'] ?? '') . "\n" . ($chunk['text'] ?? ''));
        $vector = $this->embeddingService->embed($content, 'RETRIEVAL_DOCUMENT');

        if ($vector === null) {
            return false;
        }

        BtecKbEmbedding::updateOrCreate(
            ['chunk_id' => $chunk['chunk_id']],
            [
                'topic_id' => $chunk['topic_id'] ?? null,
                'title' => (string) ($chunk['title'] ?? ''),
                'content' => $content,
                'source_refs' => $chunk['source_refs'] ?? [],
                'embedding_model' => $this->embeddingService->model(),
                'dimension' => count($vector),
                'embedding' => json_encode($vector),
            ]
        );

        return true;
    }

    /** Màn hình quản lý kho tri thức RAG */
    public function index(Request $request): View
    {
        $chunks = $this->readChunks();
        $search = trim((string) $request->query('search', ''));
        $topicFilter = trim((string) $request->query('topic_id', ''));

        $allTopics = collect($chunks)->pluck('topic_id')->filter()->unique()->sort()->values();

        $filtered = array_values(array_filter($chunks, function (array $c) use ($search, $topicFilter) {
            if ($topicFilter !== '' && ($c['topic_id'] ?? '') !== $topicFilter) {
                return false;
            }
            if ($search !== '') {
                $haystack = mb_strtolower(($c['chunk_id'] ?? '') . ' ' . ($c['title'] ?? '') . ' ' . ($c['text'] ?? ''));
                return str_contains($haystack, mb_strtolower($search));
            }
            return true;
        }));

        $indexedMap = BtecKbEmbedding::all(['chunk_id', 'embedding_model', 'dimension', 'updated_at'])
            ->keyBy('chunk_id');

        $latestVector = BtecKbEmbedding::latest('updated_at')->first();

        return view('admin.rag.index', [
            'chunks' => $filtered,
            'totalChunks' => count($chunks),
            'indexedCount' => $indexedMap->count(),
            'indexedMap' => $indexedMap,
            'topics' => $allTopics,
            'search' => $search,
            'topicFilter' => $topicFilter,
            'activeModel' => $latestVector?->embedding_model ?? GeminiEmbeddingService::VI_EMBED_MODEL,
            'activeDimension' => $latestVector?->dimension ?? 0,
            'lastIndexedAt' => $latestVector?->updated_at,
        ]);
    }

    /** Thêm mới 1 đoạn tri thức RAG và tự động nhúng Vector vào MySQL */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chunk_id' => ['nullable', 'string', 'max:60'],
            'topic_id' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'min:20'],
            'source_refs' => ['nullable', 'string', 'max:100'],
        ]);

        $chunks = $this->readChunks();
        $topicId = strtoupper(trim($validated['topic_id']));
        $chunkId = trim((string) ($validated['chunk_id'] ?? ''));

        if ($chunkId === '') {
            $chunkId = 'KB-' . $topicId . '-' . str_pad((string) (count($chunks) + 1), 3, '0', STR_PAD_LEFT);
        }

        // Tránh trùng chunk_id
        foreach ($chunks as $c) {
            if (($c['chunk_id'] ?? '') === $chunkId) {
                return back()->withInput()->with('error', "Mã đoạn tri thức {$chunkId} đã tồn tại.");
            }
        }

        $refs = array_values(array_filter(array_map('trim', explode(',', (string) ($validated['source_refs'] ?? 'S01')))));

        $newChunk = [
            'chunk_id' => $chunkId,
            'topic_id' => $topicId,
            'title' => trim($validated['title']),
            'text' => trim($validated['text']),
            'source_refs' => ! empty($refs) ? $refs : ['S01'],
            'last_compiled_on' => now()->format('Y-m-d'),
            'public_only' => true,
        ];

        $chunks[] = $newChunk;
        $this->writeChunks($chunks);

        $embedded = $this->embedSingleChunk($newChunk);
        Cache::flush();

        $msg = $embedded
            ? "Đã thêm đoạn tri thức {$chunkId} và nhúng Vector thành công!"
            : "Đã thêm đoạn tri thức {$chunkId} vào kho KB (chưa nhúng được Vector — hãy bấm Đồng bộ lại sau).";

        return redirect()->route('admin.rag.index')->with('success', $msg);
    }

    /** Cập nhật nội dung 1 đoạn tri thức RAG và tự động nhúng lại Vector */
    public function update(Request $request, string $chunkId): RedirectResponse
    {
        $validated = $request->validate([
            'topic_id' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'min:20'],
            'source_refs' => ['nullable', 'string', 'max:100'],
        ]);

        $chunks = $this->readChunks();
        $found = false;
        $updatedChunk = null;

        foreach ($chunks as &$c) {
            if (($c['chunk_id'] ?? '') === $chunkId) {
                $refs = array_values(array_filter(array_map('trim', explode(',', (string) ($validated['source_refs'] ?? '')))));
                $c['topic_id'] = strtoupper(trim($validated['topic_id']));
                $c['title'] = trim($validated['title']);
                $c['text'] = trim($validated['text']);
                if (! empty($refs)) {
                    $c['source_refs'] = $refs;
                }
                $c['last_compiled_on'] = now()->format('Y-m-d');
                $updatedChunk = $c;
                $found = true;
                break;
            }
        }
        unset($c);

        if (! $found || ! $updatedChunk) {
            return back()->with('error', "Không tìm thấy đoạn tri thức {$chunkId}.");
        }

        $this->writeChunks($chunks);
        $embedded = $this->embedSingleChunk($updatedChunk);
        Cache::flush();

        return redirect()->route('admin.rag.index')->with(
            'success',
            $embedded
                ? "Đã cập nhật {$chunkId} và đồng bộ lại Vector thành công!"
                : "Đã cập nhật {$chunkId} vào file KB (hãy bấm Đồng bộ Vector nếu cần)."
        );
    }

    /** Xóa 1 đoạn tri thức RAG khỏi file JSONL và bảng Vector MySQL */
    public function destroy(string $chunkId): RedirectResponse
    {
        $chunks = $this->readChunks();
        $remaining = array_values(array_filter($chunks, fn (array $c) => ($c['chunk_id'] ?? '') !== $chunkId));

        if (count($remaining) === count($chunks)) {
            return back()->with('error', "Không tìm thấy đoạn tri thức {$chunkId}.");
        }

        $this->writeChunks($remaining);
        BtecKbEmbedding::where('chunk_id', $chunkId)->delete();
        Cache::flush();

        return redirect()->route('admin.rag.index')->with('success', "Đã xóa đoạn tri thức {$chunkId} khỏi kho RAG.");
    }

    /** Import hàng loạt dữ liệu RAG từ file .txt, .md hoặc .jsonl */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'kb_file' => ['required', 'file', 'mimes:txt,md,jsonl,json', 'max:5120'],
            'topic_id' => ['nullable', 'string', 'max:20'],
            'title_prefix' => ['nullable', 'string', 'max:150'],
        ]);

        $file = $request->file('kb_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $rawContent = (string) file_get_contents($file->getRealPath());
        $chunks = $this->readChunks();
        $existingIds = array_column($chunks, 'chunk_id');

        $added = 0;
        $embeddedCount = 0;
        $topicId = strtoupper(trim((string) ($request->input('topic_id') ?: 'T99')));
        $titlePrefix = trim((string) ($request->input('title_prefix') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)));

        if (in_array($ext, ['jsonl', 'json'], true)) {
            $lines = preg_split('/\r\n|\r|\n/', $rawContent) ?: [];
            foreach ($lines as $line) {
                $decoded = json_decode(trim($line), true);
                if (! is_array($decoded) || empty($decoded['text'])) {
                    continue;
                }
                $cid = $decoded['chunk_id'] ?? ('KB-' . $topicId . '-' . str_pad((string) (count($chunks) + 1), 3, '0', STR_PAD_LEFT));
                if (in_array($cid, $existingIds, true)) {
                    continue;
                }
                $decoded['chunk_id'] = $cid;
                $decoded['topic_id'] = $decoded['topic_id'] ?? $topicId;
                $decoded['title'] = $decoded['title'] ?? $titlePrefix;
                $decoded['source_refs'] = $decoded['source_refs'] ?? ['S01'];
                $chunks[] = $decoded;
                $existingIds[] = $cid;
                $added++;
                if ($this->embedSingleChunk($decoded)) {
                    $embeddedCount++;
                }
            }
        } else {
            // Tách văn bản .txt/.md theo đoạn (mỗi đoạn ~1200 ký tự)
            $paragraphs = preg_split('/\n{2,}/', trim($rawContent)) ?: [];
            $buffer = '';
            $partIndex = 1;

            foreach ($paragraphs as $p) {
                $p = trim($p);
                if ($p === '') {
                    continue;
                }
                if (mb_strlen($buffer . "\n\n" . $p) > 1200 && $buffer !== '') {
                    $cid = 'KB-' . $topicId . '-' . str_pad((string) (count($chunks) + 1), 3, '0', STR_PAD_LEFT);
                    $newChunk = [
                        'chunk_id' => $cid,
                        'topic_id' => $topicId,
                        'title' => "{$titlePrefix} (Phần {$partIndex})",
                        'text' => trim($buffer),
                        'source_refs' => ['S01'],
                        'last_compiled_on' => now()->format('Y-m-d'),
                        'public_only' => true,
                    ];
                    $chunks[] = $newChunk;
                    $added++;
                    if ($this->embedSingleChunk($newChunk)) {
                        $embeddedCount++;
                    }
                    $partIndex++;
                    $buffer = $p;
                } else {
                    $buffer = $buffer === '' ? $p : ($buffer . "\n\n" . $p);
                }
            }

            if ($buffer !== '') {
                $cid = 'KB-' . $topicId . '-' . str_pad((string) (count($chunks) + 1), 3, '0', STR_PAD_LEFT);
                $newChunk = [
                    'chunk_id' => $cid,
                    'topic_id' => $topicId,
                    'title' => $partIndex > 1 ? "{$titlePrefix} (Phần {$partIndex})" : $titlePrefix,
                    'text' => trim($buffer),
                    'source_refs' => ['S01'],
                    'last_compiled_on' => now()->format('Y-m-d'),
                    'public_only' => true,
                ];
                $chunks[] = $newChunk;
                $added++;
                if ($this->embedSingleChunk($newChunk)) {
                    $embeddedCount++;
                }
            }
        }

        if ($added === 0) {
            return back()->with('error', 'Không tìm thấy đoạn văn bản hợp lệ nào trong file tải lên.');
        }

        $this->writeChunks($chunks);
        Cache::flush();

        return redirect()->route('admin.rag.index')->with(
            'success',
            "Đã import thành công {$added} đoạn tri thức mới ({$embeddedCount}/{$added} đã nhúng Vector vào MySQL)!"
        );
    }

    /** Chạy đồng bộ & nhúng lại toàn bộ Vector RAG (php artisan btec:index-kb --fresh) */
    public function reindex(): RedirectResponse
    {
        try {
            set_time_limit(180);
            $exitCode = Artisan::call('btec:index-kb', ['--fresh' => true]);
            Cache::flush();

            if ($exitCode !== 0) {
                return back()->with('error', 'Đồng bộ Vector thất bại. Hãy kiểm tra cấu hình ML_SERVICE_URL / HF_API_TOKEN / GEMINI_API_KEY.');
            }

            $count = BtecKbEmbedding::count();
            return redirect()->route('admin.rag.index')->with('success', "Đã đồng bộ & nhúng lại toàn bộ {$count} vector vào MySQL thành công!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi đồng bộ Vector: ' . $e->getMessage());
        }
    }
}
