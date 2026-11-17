<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mở rộng câu hỏi quiz để hỗ trợ nhiều dạng bài:
 *  - mcq          : trắc nghiệm 4 lựa chọn A/B/C/D (mặc định, tương thích dữ liệu cũ)
 *  - true_false   : đúng/sai (dùng option_a = "Đúng", option_b = "Sai")
 *  - fill_blank   : điền khuyết (chấm theo correct_text, nhiều đáp án cách nhau bằng dấu |)
 *  - code_reading : đọc đoạn code rồi chọn kết quả (MCQ, code nằm trong content)
 * Thêm giải thích đáp án cho từng câu (dùng khi cho phép xem đáp án).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type', 20)->default('mcq')->after('content');
            $table->text('explanation')->nullable()->after('correct_answer');
            $table->string('correct_text', 500)->nullable()->after('explanation')
                ->comment('Đáp án đúng cho dạng điền khuyết; nhiều đáp án cách nhau bằng |');

            // Các lựa chọn không còn bắt buộc (true_false chỉ dùng 2, fill_blank không dùng)
            $table->string('option_a')->nullable()->change();
            $table->string('option_b')->nullable()->change();
            $table->string('option_c')->nullable()->change();
            $table->string('option_d')->nullable()->change();
            $table->char('correct_answer', 1)->nullable()->change();
        });

        Schema::table('submission_details', function (Blueprint $table) {
            $table->string('answer_text', 500)->nullable()->after('selected_option')
                ->comment('Câu trả lời dạng text của sinh viên (dạng điền khuyết)');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_type', 'explanation', 'correct_text']);
        });

        Schema::table('submission_details', function (Blueprint $table) {
            $table->dropColumn('answer_text');
        });
    }
};
