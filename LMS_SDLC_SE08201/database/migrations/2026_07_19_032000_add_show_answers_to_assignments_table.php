<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ( Đăng thêm ) Thêm cột show_answers
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'show_answers')) {
                $table->boolean('show_answers')->default(false)->after('max_score')->comment('Cho phép SV xem đáp án sau khi nộp (quiz)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'show_answers')) {
                $table->dropColumn('show_answers');
            }
        });
    }
};
