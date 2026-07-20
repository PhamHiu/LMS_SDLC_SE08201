<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Major;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function showRegisterForm(): View
    {
        return view('auth.register', [
            'majors' => Major::orderBy('major_name')->get(),
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authService->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Đăng ký thành công! Chào mừng bạn đến với LMS.');
    }
}
