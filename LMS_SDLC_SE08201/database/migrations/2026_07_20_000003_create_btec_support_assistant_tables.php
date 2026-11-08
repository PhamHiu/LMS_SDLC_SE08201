<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Phiên hội thoại với BTEC FPT AI Student Support Assistant
        Schema::create('btec_support_conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('session_token', 80)->index()->nullable();
            $table->string('title', 255)->default('Hội thoại hỗ trợ mới');
            $table->timestamps();
        });

        // 2. Chi tiết tin nhắn
        Schema::create('btec_support_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('conversation_id')->constrained('btec_support_conversations', 'conversation_id')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant'])->default('user');
            $table->longText('message');
            $table->string('intent', 60)->nullable();
            $table->json('citations')->nullable();
            $table->json('actions')->nullable();
            $table->string('feedback', 20)->nullable(); // helpful, not_helpful
            $table->timestamps();

            $table->index('conversation_id');
        });

        // 3. Phiếu chuyển tiếp hỗ trợ (Support Tickets)
        Schema::create('btec_support_tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('ticket_code', 30)->unique();
            $table->string('topic', 150);
            $table->text('description');
            $table->string('department', 100)->default('Phòng Đào tạo');
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])->default('OPEN');
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('btec_support_tickets');
        Schema::dropIfExists('btec_support_messages');
        Schema::dropIfExists('btec_support_conversations');
    }
};

