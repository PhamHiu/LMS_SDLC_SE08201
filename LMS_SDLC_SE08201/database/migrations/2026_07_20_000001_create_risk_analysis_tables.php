<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MÔ-ĐUN AI DROPOUT RISK ANALYSIS & INTERVENTION TRACKING
 * Lưu trữ lịch sử phân tích nguy cơ bỏ học và các hành động can thiệp sư phạm
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng lưu trữ phiên đánh giá nguy cơ (không ghi đè để theo dõi xu hướng qua thời gian)
        Schema::create('risk_predictions', function (Blueprint $table) {
            $table->id('prediction_id');
            $table->foreignId('user_id')->comment('Học sinh được đánh giá')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('risk_level', 20)->comment('SAFE, MEDIUM, HIGH, VERY_HIGH');
            $table->float('risk_score', 5, 2)->default(0)->comment('Điểm rủi ro chuẩn hóa 0 - 100');
            $table->string('analysis_method', 50)->default('RULE_ENGINE')->comment('RULE_ENGINE hoặc MACHINE_LEARNING');
            $table->string('model_version', 50)->default('v1.0-rules');
            $table->text('summary_explanation')->nullable()->comment('Tóm tắt lý do đánh giá bằng ngôn ngữ tự nhiên');
            $table->text('recommendations')->nullable()->comment('Khuyến nghị sư phạm hỗ trợ');
            $table->string('data_quality_status', 50)->default('COMPLETE')->comment('COMPLETE, PARTIAL, INSUFFICIENT');
            $table->timestamp('analyzed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'analyzed_at']);
            $table->index('risk_level');
        });

        // 2. Bảng lưu chi tiết các yếu tố rủi ro thành phần (Contributing Factors theo môn học)
        Schema::create('risk_factors', function (Blueprint $table) {
            $table->id('factor_id');
            $table->foreignId('prediction_id')->constrained('risk_predictions', 'prediction_id')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects', 'subject_id')->nullOnDelete();
            $table->string('factor_type', 50)->comment('ATTENDANCE, GRADE, RETAKE, TREND');
            $table->string('title');
            $table->text('description');
            $table->string('severity', 20)->default('INFO')->comment('INFO, WARNING, CRITICAL');
            $table->float('impact_weight', 5, 2)->default(0);
            $table->timestamps();
        });

        // 3. Bảng ghi nhận can thiệp sư phạm của giáo viên (Teacher Intervention Tracking)
        Schema::create('risk_interventions', function (Blueprint $table) {
            $table->id('intervention_id');
            $table->foreignId('user_id')->comment('Học sinh được can thiệp')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('teacher_id')->comment('Giáo viên thực hiện')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('prediction_id')->nullable()->constrained('risk_predictions', 'prediction_id')->nullOnDelete();
            $table->string('action_type', 50)->comment('ACADEMIC_TUTORING, ATTENDANCE_REMINDER, COUNSELING, OTHER');
            $table->text('notes')->comment('Nội dung hỗ trợ');
            $table->string('status', 30)->default('PENDING')->comment('PENDING, IN_PROGRESS, RESOLVED');
            $table->text('outcome_evaluation')->nullable()->comment('Đánh giá tiến bộ sau can thiệp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_interventions');
        Schema::dropIfExists('risk_factors');
        Schema::dropIfExists('risk_predictions');
    }
};

