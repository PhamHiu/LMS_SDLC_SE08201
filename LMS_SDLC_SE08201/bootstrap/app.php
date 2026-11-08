<?php

use App\Http\Middleware\CheckRole;
use App\Services\AuthService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias middleware phân quyền theo role (ADMIN / TEACHER / STUDENT)
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Khách chưa đăng nhập -> chuyển về trang login
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Người dùng đã đăng nhập nếu vào lại /login hoặc /register -> chuyển thẳng về Dashboard đúng Role
        $middleware->redirectUsersTo(fn (Request $request) => app(AuthService::class)->homeRouteFor($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Xử lý mượt lỗi 419 (CSRF Token Mismatch / Page Expired) không làm kẹt người dùng
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang (F5) và thử lại.',
                ], 419);
            }

            // Nếu đang bấm Đăng xuất mà session đã hết hạn -> đưa thẳng về trang login
            if ($request->is('logout')) {
                return redirect()->route('login')->with('success', 'Bạn đã đăng xuất.');
            }

            // Nếu tài khoản thực chất đã đăng nhập thành công (vd: bấm đúp nút đăng nhập) -> vào thẳng Dashboard
            if ($request->user()) {
                if ($request->is('login', 'register')) {
                    return redirect(app(AuthService::class)->homeRouteFor($request->user()));
                }

                return back()
                    ->withInput($request->except('password', 'password_confirmation', 'current_password', '_token'))
                    ->with('error', 'Phiên biểu mẫu vừa được làm mới, vui lòng thực hiện lại thao tác.');
            }

            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            $targetRoute = $request->is('register') ? 'register' : 'login';

            return redirect()
                ->route($targetRoute)
                ->withInput($request->only('email', 'full_name'))
                ->with('error', 'Phiên trang đã được làm mới tự động, vui lòng nhấn thực hiện lại.');
        });
    })->create();
