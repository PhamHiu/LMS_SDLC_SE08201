<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Gỡ bỏ ràng buộc UNIQUE trên assignment_id:
        // Cần drop foreign key trước, drop unique index, thêm index thông thường rồi re-add foreign key.
        try {
            DB::statement('ALTER TABLE `notifications` DROP FOREIGN KEY `notifications_assignment_id_foreign`');
        } catch (\Throwable $e) {}

        try {
            DB::statement('ALTER TABLE `notifications` DROP INDEX `notifications_assignment_id_unique`');
        } catch (\Throwable $e) {}

        try {
            DB::statement('ALTER TABLE `notifications` ADD INDEX `notifications_assignment_id_index` (`assignment_id`)');
        } catch (\Throwable $e) {}

        try {
            DB::statement('ALTER TABLE `notifications` ADD CONSTRAINT `notifications_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE');
        } catch (\Throwable $e) {}

        // 2. Thêm cột target_url (tùy chọn đường dẫn trực tiếp)
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'target_url')) {
                $table->string('target_url')->nullable()->after('body');
            }
        });

        // 3. Tạo bảng lưu trạng thái Đã đọc / Chưa đọc của từng học sinh/người dùng
        if (!Schema::hasTable('notification_reads')) {
            Schema::create('notification_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notification_id')->constrained('notifications', 'notification_id')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->timestamp('read_at')->useCurrent();
                $table->unique(['notification_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'target_url')) {
                $table->dropColumn('target_url');
            }
        });
    }
};
