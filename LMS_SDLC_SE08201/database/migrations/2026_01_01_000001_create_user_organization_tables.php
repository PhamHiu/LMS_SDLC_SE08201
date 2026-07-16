<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN HỆ 1: QUẢN LÝ NGƯỜI DÙNG & TỔ CHỨC ĐÀO TẠO
 * Bảng: majors, subjects, majors_subjects, users
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('majors', function (Blueprint $table) {
            $table->id('major_id');
            $table->string('major_name');
            $table->string('major_code', 20)->unique();
            $table->text('description')->nullable();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id('subject_id');
            $table->string('subject_code', 20)->unique();
            $table->string('subject_name');
            $table->text('description')->nullable();
        });

        Schema::create('majors_subjects', function (Blueprint $table) {
            $table->id('major_subject_id');
            $table->foreignId('major_id')->constrained('majors', 'major_id')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects', 'subject_id')->cascadeOnDelete();
            $table->unique(['major_id', 'subject_id']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->foreignId('major_id')->nullable()->constrained('majors', 'major_id')->nullOnDelete();
            $table->enum('role', ['STUDENT', 'TEACHER', 'ADMIN'])->default('STUDENT');
            $table->string('email')->unique();
            $table->string('hashpassword');
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->tinyInteger('gender')->nullable()->comment('0: Nữ, 1: Nam, 2: Khác');
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('majors_subjects');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('majors');
    }
};
