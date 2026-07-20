<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN HỆ 5: TƯƠNG TÁC & THÔNG BÁO
 * Bảng: discussions, notifications
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id('discussion_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->comment('NULL: bình luận gốc')->constrained('discussions', 'discussion_id')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('assignments', 'assignment_id')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->foreignId('user_id')->comment('Người đăng thông báo')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->comment('NULL: thông báo toàn hệ thống')->constrained('classes', 'class_id')->cascadeOnDelete();
            // Gắn với bài tập khi là thông báo nhắc hạn tự động (dùng để chống gửi trùng)
            $table->foreignId('assignment_id')->nullable()->comment('NULL: thông báo thường; có giá trị: nhắc hạn bài tập')->constrained('assignments', 'assignment_id')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            // Mỗi bài tập chỉ có duy nhất 1 thông báo nhắc hạn → chống trùng ở tầng CSDL
            $table->unique('assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('discussions');
    }
};
