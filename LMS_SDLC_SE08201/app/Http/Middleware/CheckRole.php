<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware phân quyền theo role.
 * Hỗ trợ: ->middleware('role:ADMIN'), 'role:TEACHER,ADMIN', 'role:STUDENT,TEACHER,ADMIN'
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Tách nhỏ các role nếu được truyền dưới dạng chuỗi có dấu phẩy (vd: 'STUDENT,TEACHER,ADMIN')
        $allowedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $role) {
                $trimmed = trim($role);
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }

        // 1. Kiểm tra vai trò được phép truy cập theo đúng danh sách role của route
        if (in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        // 2. Nếu người dùng đăng nhập bằng vai trò khác (vd: Sinh viên vào trang Giảng viên hoặc ngược lại)
        // Thay vì đứng ở màn hình trắng 403 cụt đường, hệ thống tự động điều hướng về Dashboard đúng của vai trò đó kèm thông báo rõ ràng
        $authService = app(AuthService::class);
        $homeRoute = $authService->homeRouteFor($user);

        return redirect($homeRoute)->with('error', 'Tài khoản của bạn (' . $user->roleLabel() . ') không có quyền truy cập trang này. Hệ thống đã tự động chuyển bạn về đúng màn hình làm việc.');
    }
}
