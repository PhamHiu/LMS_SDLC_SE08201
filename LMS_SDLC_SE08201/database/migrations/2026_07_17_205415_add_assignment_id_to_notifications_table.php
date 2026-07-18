<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cột có thể đã được tạo sẵn trong migration create (do nhánh nhắc-hạn đã merge).
        if (Schema::hasColumn('notifications', 'assignment_id')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('assignment_id')
                ->nullable()
                ->after('class_id')
                ->comment('NULL: thông báo thường; có giá trị: nhắc hạn bài tập')
                ->constrained('assignments', 'assignment_id')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
            $table->dropColumn('assignment_id');
        });
    }
};
