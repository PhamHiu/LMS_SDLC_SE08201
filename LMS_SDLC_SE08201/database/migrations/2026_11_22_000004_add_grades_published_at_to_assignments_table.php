<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assignments') && ! Schema::hasColumn('assignments', 'grades_published_at')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->timestamp('grades_published_at')->nullable()->after('attachment_path');
            });

            // Đánh dấu các bài tự luận cũ đã có điểm sẵn trong dữ liệu demo là đã công bố
            $gradedEssayIds = DB::table('submissions')
                ->whereNotNull('score')
                ->pluck('assignment_id')
                ->unique()
                ->all();

            if (! empty($gradedEssayIds)) {
                DB::table('assignments')
                    ->where('type', 'essay')
                    ->whereIn('assignment_id', $gradedEssayIds)
                    ->update(['grades_published_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'grades_published_at')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropColumn('grades_published_at');
            });
        }
    }
};
