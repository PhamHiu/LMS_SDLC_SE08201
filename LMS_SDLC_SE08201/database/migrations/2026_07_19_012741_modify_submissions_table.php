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
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('result_file_path');
            $table->string('other_file_path')->nullable()->after('submit_file_path')->comment('File tài liệu giáo viên tải thêm nếu có');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('other_file_path');
            $table->string('result_file_path')->nullable()->after('submit_file_path')->comment('File feedback của giảng viên');
        });
    }
};
