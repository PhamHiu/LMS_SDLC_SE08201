<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN HỆ 3: HỌC LIỆU & BÀI TẬP
 * Bảng: materials, assignments, questions
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id('material_id');
            $table->foreignId('subject_id')->constrained('subjects', 'subject_id')->cascadeOnDelete();
            $table->foreignId('user_id')->comment('Người đăng tải')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->text('description')->nullable();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['essay', 'quiz'])->comment('essay: Tự luận, quiz: Trắc nghiệm');
            $table->text('description')->nullable();
            $table->dateTime('due_date');
            $table->float('max_score')->default(10);
            $table->boolean('show_answers')->default(false)->comment('Cho phép SV xem đáp án sau khi nộp (quiz)');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->foreignId('assignment_id')->constrained('assignments', 'assignment_id')->cascadeOnDelete();
            $table->text('content');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->char('correct_answer', 1)->comment('A, B, C hoặc D');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('materials');
    }
};
