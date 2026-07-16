<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

class ServeAndCheckDbCommand extends BaseServeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và cập nhật DB trước khi Serve the application on the PHP development server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Đang kiểm tra trạng thái Database...');

        try {
            // Khởi tạo bảng migrations nếu chưa có
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                $this->info('Lần đầu chạy: Khởi tạo bảng migrations...');
                Artisan::call('migrate:install');
            }

            // Lấy danh sách các file migration và các file đã chạy
            $paths = array_merge([database_path('migrations')], $migrator->paths());
            $files = $migrator->getMigrationFiles($paths);
            $ran = $migrator->getRepository()->getRan();
            
            // Tìm các file chưa chạy
            $pending = array_diff(array_keys($files), $ran);

            if (count($pending) > 0) {
                $this->warn('Phát hiện cấu trúc Database thay đổi. Đang tiến hành xóa trắng và khôi phục (migrate:fresh)...');
                
                Artisan::call('migrate:fresh', [
                    '--seed' => true,
                    '--force' => true
                ]);
                $this->line(Artisan::output());

                $this->info('Khôi phục cấu trúc DB và dữ liệu mẫu thành công!');
            } else {
                $this->info('✅ Database đã đồng bộ và khớp hoàn toàn. Bỏ qua bước cập nhật.');
            }
        } catch (\Exception $e) {
            $this->error('Lỗi khi kiểm tra DB: ' . $e->getMessage());
            $this->warn('Vẫn tiếp tục khởi động server...');
        }

        $this->info('');
        $this->info('Khởi động hệ thống...');
        
        // Gọi hàm handle() gốc của ServeCommand để bật server
        return parent::handle();
    }
}
