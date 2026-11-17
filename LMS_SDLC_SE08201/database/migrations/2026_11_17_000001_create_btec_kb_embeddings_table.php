<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng chỉ mục vector (embedding) cho RAG của AI Student Support.
 * Mỗi đoạn tri thức (KB chunk) được nhúng bằng Gemini Embedding và lưu vector
 * dưới dạng JSON trong MySQL; truy xuất bằng độ tương đồng cosine ở tầng ứng dụng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('btec_kb_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('chunk_id')->unique();
            $table->string('topic_id', 32)->nullable();
            $table->string('title')->nullable();
            $table->longText('content');                    // nội dung đã nhúng (title + text)
            $table->json('source_refs')->nullable();        // mã nguồn trích dẫn [S01, ...]
            $table->string('embedding_model', 80);          // ví dụ: text-embedding-004
            $table->unsignedSmallInteger('dimension');      // số chiều vector
            $table->longText('embedding');                  // vector JSON [0.12, -0.03, ...]
            $table->timestamps();

            $table->index('topic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('btec_kb_embeddings');
    }
};
