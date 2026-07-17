<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use App\Services\NotificationService;

Artisan::command('gemini:test', function () {
    $apiKey = env('GEMINI_API_KEY');
    if (empty($apiKey)) {
        $this->error('LỖI: Chưa cấu hình GEMINI_API_KEY trong file .env');
        $this->info('-> Hệ thống hiện đang chạy ở chế độ DỰ PHÒNG CỤC BỘ (Offline Fallback).');
        return;
    }

    $this->info('Đang kết nối thử nghiệm tới API Gemini của Google...');
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';

    try {
        $startTime = microtime(true);
        $response = Http::timeout(10)->withHeaders([
            'x-goog-api-key' => $apiKey
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Hãy phản hồi duy nhất một từ: KET_NOI_THANH_CONG']
                    ]
                ]
            ]
        ]);
        $duration = round(microtime(true) - $startTime, 2);

        if ($response->successful()) {
            $result = $response->json();
            $text = trim($result['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $this->info("KẾT NỐI THÀNH CÔNG! Thời gian phản hồi: {$duration} giây.");
            $this->info("Phản hồi từ AI: \"{$text}\"");
        } else {
            $this->error("LỖI KẾT NỐI (HTTP {$response->status()}):");
            $this->line($response->body());
        }
    } catch (\Exception $e) {
        $this->error('LỖI KHI GỌI API: ' . $e->getMessage());
    }
})->purpose('Kiểm tra kết nối tới Google Gemini API');

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
