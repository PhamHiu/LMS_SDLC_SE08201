<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MÔ-ĐUN AI LEARNING TUTOR
 * Quản lý các phiên hội thoại, tin nhắn gia sư và ngữ cảnh học tập
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng lưu trữ phiên trò chuyện của học sinh với AI Tutor
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->foreignId('user_id')->comment('Học sinh sở hữu cuộc trò chuyện')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->comment('Môn học ngữ cảnh')->constrained('subjects', 'subject_id')->nullOnDelete();
            $table->string('title')->default('Cuộc trò chuyện mới');
            $table->string('mode', 50)->default('EXPLAIN')->comment('EXPLAIN: Giải thích, GUIDANCE: Hướng dẫn bài tập, REVIEW: Kiểm tra bài làm, PRACTICE: Luyện tập');
            $table->string('model_used', 50)->default('gemini-1.5-flash');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // 2. Bảng lưu trữ chi tiết các tin nhắn trong phiên trò chuyện
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('conversation_id')->constrained('ai_conversations', 'conversation_id')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->longText('content')->comment('Nội dung tin nhắn');
            $table->json('structured_output')->nullable()->comment('Dữ liệu JSON có cấu trúc (gợi ý bước, code mẫu, câu hỏi follow-up)');
            $table->integer('tokens_used')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};

