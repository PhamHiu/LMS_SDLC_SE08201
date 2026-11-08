<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /** Hiển thị form đăng nhập (chống cache token CSRF cũ gây lỗi 419) */
    public function showLoginForm(): Response
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /** Xử lý đăng nhập: Route → Middleware(guest) → FormRequest → Controller → Service */
    public function login(LoginRequest $request): RedirectResponse
    {
        $ok = $this->authService->attemptLogin(
            $request->input('email'),
            $request->input('password'),
            $request->boolean('remember'),
        );

        if (! $ok) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        $user = $request->user();
        $homeUrl = $this->authService->homeRouteFor($user);

        // Lấy và xóa intended URL cũ để không bị chuyển nhầm sang trang của Role khác
        // Lưu ý: Auth::attempt() đã tự động migrate session mới, chỉ làm mới thêm CSRF token
        $intended = $request->session()->pull('url.intended');
        $request->session()->regenerateToken();

        if ($intended) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';

            if ($user->isAdmin() && str_starts_with($path, '/admin')) {
                return redirect($intended);
            }
            if ($user->isTeacher() && str_starts_with($path, '/teacher')) {
                return redirect($intended);
            }
            if ($user->isStudent() && str_starts_with($path, '/student')) {
                return redirect($intended);
            }
        }

        return redirect($homeUrl);
    }

    /** Đăng xuất an toàn, hủy session */
    public function logout(Request $request): RedirectResponse
    {
        // Xóa hoàn toàn intended URL khi đăng xuất để tài khoản sau không bị dính URL cũ
        $request->session()->forget('url.intended');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Bạn đã đăng xuất.');
    }
}
