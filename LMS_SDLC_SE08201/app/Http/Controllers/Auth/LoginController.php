<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /** Hiển thị form đăng nhập */
    public function showLoginForm(): View
    {
        return view('auth.login');
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

        $request->session()->regenerate();

        return redirect()->intended($this->authService->homeRouteFor($request->user()));
    }

    /** Đăng xuất an toàn, hủy session */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Bạn đã đăng xuất.');
    }
}
