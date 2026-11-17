<?php

namespace App\Console\Commands;

use App\Services\Attendance\RealtimeScheduleService;
use Illuminate\Console\Command;

class SyncRealtimeSchedule extends Command
{
    protected $signature = 'lms:sync-realtime';
    protected $description = 'Đồng bộ toàn bộ lịch học của các lớp theo thời gian thực (hôm nay)';

    public function handle(RealtimeScheduleService $service): int
    {
        $this->info('Đang đồng bộ toàn bộ lịch học theo thời gian thực...');
        $service->alignAllClasses();
        $this->info('Đã đồng bộ thành công! Các buổi học hôm nay và lịch trình đã được cập nhật bám sát thời gian thực.');
        return Command::SUCCESS;
    }
}
