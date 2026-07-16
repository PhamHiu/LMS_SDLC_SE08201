<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Thử đăng nhập với email/mật khẩu.
     */
    public function attemptLogin(string $email, string $password, bool $remember = false): bool
    {
        return Auth::attempt(['email' => $email, 'password' => $password], $remember);
    }

    /**
     * Đăng ký tài khoản mới — mặc định role STUDENT.
     */
    public function register(array $data): User
    {
        return User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'hashpassword' => Hash::make($data['password']),
            'major_id' => $data['major_id'] ?? null,
            'role' => User::ROLE_STUDENT,
        ]);
    }

    /**
     * Đường dẫn dashboard tương ứng với role sau đăng nhập.
     */
    public function homeRouteFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => route('admin.dashboard'),
            User::ROLE_TEACHER => route('teacher.dashboard'),
            default => route('student.dashboard'),
        };
    }
}
