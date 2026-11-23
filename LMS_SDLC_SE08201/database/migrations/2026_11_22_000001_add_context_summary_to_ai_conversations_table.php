<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_conversations', 'context_summary')) {
                $table->longText('context_summary')->nullable()->after('model_used');
            }
            if (! Schema::hasColumn('ai_conversations', 'summarized_up_to_message_id')) {
                $table->unsignedBigInteger('summarized_up_to_message_id')->nullable()->after('context_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('ai_conversations', 'summarized_up_to_message_id')) {
                $table->dropColumn('summarized_up_to_message_id');
            }
            if (Schema::hasColumn('ai_conversations', 'context_summary')) {
                $table->dropColumn('context_summary');
            }
        });
    }
};
