<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlotSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('slots')->insert([
            ['slot_number' => 1, 'start_time' => '07:15:00', 'end_time' => '09:15:00', 'label' => 'Ca 1 (07:15 – 09:15)'],
            ['slot_number' => 2, 'start_time' => '09:25:00', 'end_time' => '11:25:00', 'label' => 'Ca 2 (09:25 – 11:25)'],
            ['slot_number' => 3, 'start_time' => '12:00:00', 'end_time' => '14:00:00', 'label' => 'Ca 3 (12:00 – 14:00)'],
            ['slot_number' => 4, 'start_time' => '14:10:00', 'end_time' => '16:10:00', 'label' => 'Ca 4 (14:10 – 16:10)'],
            ['slot_number' => 5, 'start_time' => '16:20:00', 'end_time' => '18:20:00', 'label' => 'Ca 5 (16:20 – 18:20)'],
            ['slot_number' => 6, 'start_time' => '18:30:00', 'end_time' => '20:30:00', 'label' => 'Ca 6 (18:30 – 20:30)'],
        ]);
    }
}
