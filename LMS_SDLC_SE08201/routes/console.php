<?php

use App\Services\NotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Lệnh console tùy chỉnh & lịch chạy tự động
|--------------------------------------------------------------------------
*/

// Tạo thông báo nhắc hạn cho các bài tập sắp đến hạn nộp.
// Chống gửi trùng: mỗi bài tập chỉ sinh 1 thông báo (đảm bảo trong NotificationService).
Artisan::command('notifications:assignment-deadlines {--days=3 : Ngưỡng số ngày trước hạn}', function (NotificationService $service) {
    $days = (int) $this->option('days');
    $count = $service->sendAssignmentDeadlineReminders($days);

    $this->info("Đã tạo {$count} thông báo nhắc hạn bài tập (ngưỡng {$days} ngày).");
})->purpose('Tạo thông báo nhắc hạn nộp bài tập (không gửi trùng)');

// Lịch chạy: mỗi ngày lúc 07:00 sáng
Schedule::command('notifications:assignment-deadlines')
    ->dailyAt('07:00')
    ->withoutOverlapping();
