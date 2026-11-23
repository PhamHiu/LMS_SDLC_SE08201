<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('policy_rules')) {
            Schema::create('policy_rules', function (Blueprint $table) {
                $table->id('policy_id');
                $table->string('rule_code', 80)->index();
                $table->string('title', 255);
                $table->string('source_document', 255);
                $table->string('clause_ref', 120)->nullable();
                $table->text('excerpt');
                $table->string('scope_type', 20)->default('GLOBAL')->index(); // GLOBAL, MAJOR, SUBJECT, CLASS
                $table->unsignedBigInteger('scope_id')->nullable()->index();
                $table->string('term_code', 40)->nullable()->index(); // e.g. FALL_2025, SPRING_2026
                $table->date('effective_from')->index();
                $table->date('effective_to')->nullable()->index();
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, APPROVED, PUBLISHED, ARCHIVED
                $table->integer('priority')->default(100);
                // Typed calculation fields (avoid hardcoding thresholds in prompts)
                $table->string('rule_type', 40)->default('ATTENDANCE_THRESHOLD'); // ATTENDANCE_THRESHOLD, GENERAL_POLICY, UNSUPPORTED_CALCULATION
                $table->decimal('threshold_value', 8, 2)->nullable();
                $table->string('operator', 10)->nullable(); // GT, GTE, LT, LTE
                $table->string('unit', 20)->nullable(); // PERCENT, SESSIONS
                $table->string('basis', 40)->nullable(); // TOTAL_CLASS_SESSIONS, MARKED_SESSIONS
                $table->boolean('requires_engineering_support')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('policy_rule_audits')) {
            Schema::create('policy_rule_audits', function (Blueprint $table) {
                $table->id('audit_id');
                $table->unsignedBigInteger('policy_id')->index();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('action', 40); // CREATED, DRAFT_UPDATED, SIMULATED, APPROVED, PUBLISHED, ARCHIVED
                $table->json('changes_json')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('policy_id')
                    ->references('policy_id')
                    ->on('policy_rules')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_rule_audits');
        Schema::dropIfExists('policy_rules');
    }
};
