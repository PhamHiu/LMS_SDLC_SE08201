<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN HỆ 2: QUẢN LÝ LỚP HỌC & ĐIỂM DANH
 * Bảng: classes, enrollments, sessions, attendances
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id('class_id');
            $table->foreignId('user_id')->comment('Giảng viên đứng lớp')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects', 'subject_id')->cascadeOnDelete();
            $table->foreignId('major_id')->constrained('majors', 'major_id')->cascadeOnDelete();
            $table->string('class_name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_sessions')->default(0);
            $table->tinyInteger('status')->default(0)->comment('0: Chưa mở, 1: Đang học, 2: Đã đóng');
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->foreignId('user_id')->comment('Sinh viên')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->unique(['user_id', 'class_id']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->date('session_date');
            $table->string('room', 100)->nullable();
            $table->string('shift', 50)->nullable();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->foreignId('user_id')->comment('Sinh viên')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('sessions', 'session_id')->cascadeOnDelete();
            $table->timestamp('attendance_time')->nullable();
            $table->enum('status', ['P', 'A', 'L'])->default('P')->comment('P: Có mặt, A: Vắng, L: Muộn');
            $table->string('note')->nullable();
            $table->unique(['user_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('classes');
    }
};
