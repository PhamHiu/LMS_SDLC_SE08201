<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('btec_support_conversations')) {
            Schema::table('btec_support_conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('btec_support_conversations', 'context_state')) {
                    $table->json('context_state')->nullable()->after('title');
                }
            });
        }

        if (Schema::hasTable('btec_support_messages')) {
            Schema::table('btec_support_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('btec_support_messages', 'is_private')) {
                    $table->boolean('is_private')->default(false)->after('intent');
                }
                if (! Schema::hasColumn('btec_support_messages', 'private_refs')) {
                    $table->json('private_refs')->nullable()->after('is_private');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('btec_support_messages')) {
            Schema::table('btec_support_messages', function (Blueprint $table) {
                if (Schema::hasColumn('btec_support_messages', 'private_refs')) {
                    $table->dropColumn('private_refs');
                }
                if (Schema::hasColumn('btec_support_messages', 'is_private')) {
                    $table->dropColumn('is_private');
                }
            });
        }

        if (Schema::hasTable('btec_support_conversations')) {
            Schema::table('btec_support_conversations', function (Blueprint $table) {
                if (Schema::hasColumn('btec_support_conversations', 'context_state')) {
                    $table->dropColumn('context_state');
                }
            });
        }
    }
};
