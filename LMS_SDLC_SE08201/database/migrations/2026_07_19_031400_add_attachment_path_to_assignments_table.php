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
        // ( Đăng thêm ) Thêm cột attachment_path
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('description')->comment('File tài liệu đính kèm của giáo viên');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
