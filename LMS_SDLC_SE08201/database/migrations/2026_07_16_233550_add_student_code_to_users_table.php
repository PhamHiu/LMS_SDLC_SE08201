<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_code', 20)->nullable()->unique()->after('user_id');
        });

        // Tự động sinh mã cho các user đã có sẵn trong DB
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $year = $user->created_at ? date('y', strtotime($user->created_at)) : date('y');
            $paddedId = str_pad($user->user_id, 5, '0', STR_PAD_LEFT);
            $code = 'HL' . $year . $paddedId;
            DB::table('users')->where('user_id', $user->user_id)->update(['student_code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('student_code');
        });
    }
};
