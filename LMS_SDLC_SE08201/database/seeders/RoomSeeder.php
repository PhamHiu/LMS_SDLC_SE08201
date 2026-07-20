<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['room_name' => 'P201', 'capacity' => 40, 'location' => 'Tòa A'],
            ['room_name' => 'P301', 'capacity' => 40, 'location' => 'Tòa A'],
            ['room_name' => 'P401', 'capacity' => 40, 'location' => 'Tòa A'],
            ['room_name' => 'Lab-A', 'capacity' => 30, 'location' => 'Tòa B'],
            ['room_name' => 'Lab-B', 'capacity' => 30, 'location' => 'Tòa B'],
            ['room_name' => 'Hội trường 1', 'capacity' => 100, 'location' => 'Tòa C'],
        ];

        foreach ($rooms as $room) {
            DB::table('rooms')->updateOrInsert(
                ['room_name' => $room['room_name']],
                ['capacity' => $room['capacity'], 'location' => $room['location']]
            );
        }
    }
}
