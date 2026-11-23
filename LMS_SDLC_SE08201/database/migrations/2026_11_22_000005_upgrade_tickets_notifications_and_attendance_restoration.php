<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications') && ! Schema::hasColumn('notifications', 'recipient_user_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->unsignedBigInteger('recipient_user_id')->nullable()->after('user_id');
                $table->index('recipient_user_id');
            });
        }

        if (Schema::hasTable('btec_support_tickets')) {
            Schema::table('btec_support_tickets', function (Blueprint $table) {
                if (! Schema::hasColumn('btec_support_tickets', 'class_id')) {
                    $table->unsignedBigInteger('class_id')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'assigned_teacher_id')) {
                    $table->unsignedBigInteger('assigned_teacher_id')->nullable()->after('class_id');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'forwarded_by')) {
                    $table->unsignedBigInteger('forwarded_by')->nullable()->after('assigned_teacher_id');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'forwarded_at')) {
                    $table->timestamp('forwarded_at')->nullable()->after('forwarded_by');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('resolution_note');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'teacher_reply')) {
                    $table->text('teacher_reply')->nullable()->after('admin_note');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'teacher_replied_at')) {
                    $table->timestamp('teacher_replied_at')->nullable()->after('teacher_reply');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'resolved_by')) {
                    $table->unsignedBigInteger('resolved_by')->nullable()->after('teacher_replied_at');
                }
                if (! Schema::hasColumn('btec_support_tickets', 'resolved_at')) {
                    $table->timestamp('resolved_at')->nullable()->after('resolved_by');
                }
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('attendances', 'is_restored')) {
                    $table->boolean('is_restored')->default(false)->after('note');
                }
                if (! Schema::hasColumn('attendances', 'restored_at')) {
                    $table->timestamp('restored_at')->nullable()->after('is_restored');
                }
                if (! Schema::hasColumn('attendances', 'restored_by')) {
                    $table->unsignedBigInteger('restored_by')->nullable()->after('restored_at');
                }
                if (! Schema::hasColumn('attendances', 'restore_reason')) {
                    $table->string('restore_reason', 255)->nullable()->after('restored_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $cols = array_values(array_filter(
                    ['is_restored', 'restored_at', 'restored_by', 'restore_reason'],
                    fn ($col) => Schema::hasColumn('attendances', $col)
                ));
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('btec_support_tickets')) {
            Schema::table('btec_support_tickets', function (Blueprint $table) {
                $cols = array_values(array_filter(
                    [
                        'class_id',
                        'assigned_teacher_id',
                        'forwarded_by',
                        'forwarded_at',
                        'admin_note',
                        'teacher_reply',
                        'teacher_replied_at',
                        'resolved_by',
                        'resolved_at',
                    ],
                    fn ($col) => Schema::hasColumn('btec_support_tickets', $col)
                ));
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'recipient_user_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('recipient_user_id');
            });
        }
    }
};
