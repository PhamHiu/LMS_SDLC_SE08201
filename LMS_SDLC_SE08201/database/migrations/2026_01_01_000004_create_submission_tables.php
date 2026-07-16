<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN HỆ 4: NỘP BÀI & CHẤM ĐIỂM
 * Bảng: submissions, submission_details
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id('submission_id');
            $table->foreignId('user_id')->comment('Sinh viên nộp bài')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('assignments', 'assignment_id')->cascadeOnDelete();
            $table->string('submit_file_path')->nullable()->comment('File bài làm (tự luận)');
            $table->string('result_file_path')->nullable()->comment('File feedback của giảng viên');
            $table->text('feedback')->nullable()->comment('Nhận xét của giảng viên');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->float('score')->nullable();
            $table->unique(['user_id', 'assignment_id']);
        });

        Schema::create('submission_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->foreignId('submission_id')->constrained('submissions', 'submission_id')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions', 'question_id')->cascadeOnDelete();
            $table->char('selected_option', 1)->nullable()->comment('A, B, C hoặc D');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_details');
        Schema::dropIfExists('submissions');
    }
};
