<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rooms')->insert([
            ['room_name' => 'P201', 'capacity' => 40, 'location' => 'Tòa A'],
            ['room_name' => 'P301', 'capacity' => 40, 'location' => 'Tòa A'],
            ['room_name' => 'P401', 'capacity' => 40, 'location' => 'Tòa A'],
            ['room_name' => 'Lab-A', 'capacity' => 30, 'location' => 'Tòa B'],
            ['room_name' => 'Lab-B', 'capacity' => 30, 'location' => 'Tòa B'],
            ['room_name' => 'Hội trường 1', 'capacity' => 100, 'location' => 'Tòa C'],
        ]);
    }
}
