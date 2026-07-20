<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id('slot_id');
            $table->tinyInteger('slot_number');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label', 50);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');
            $table->string('room_name', 50)->unique();
            $table->integer('capacity')->nullable();
            $table->string('location', 100)->nullable();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('slot_id')->nullable()->after('session_date')->constrained('slots', 'slot_id')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->after('slot_id')->constrained('rooms', 'room_id')->nullOnDelete();
            $table->dropColumn(['room', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slots_rooms_update_sessions');
    }
};
