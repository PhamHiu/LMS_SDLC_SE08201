<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Vector embedding của một đoạn tri thức (KB chunk) trong RAG của AI Student Support.
 */
class BtecKbEmbedding extends Model
{
    protected $table = 'btec_kb_embeddings';

    protected $fillable = [
        'chunk_id', 'topic_id', 'title', 'content',
        'source_refs', 'embedding_model', 'dimension', 'embedding',
    ];

    protected $casts = [
        'source_refs' => 'array',
    ];

    /** Giải mã vector embedding JSON thành mảng float. */
    public function vector(): array
    {
        $decoded = json_decode((string) $this->embedding, true);

        return is_array($decoded) ? $decoded : [];
    }
}
