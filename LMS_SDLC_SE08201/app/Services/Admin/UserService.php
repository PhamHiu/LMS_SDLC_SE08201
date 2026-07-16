<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Danh sách người dùng, lọc theo role + tìm kiếm tên/email.
     */
    public function paginate(?string $role = null, ?string $search = null): LengthAwarePaginator
    {
        return User::with('major')
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%");
            }))
            ->orderByDesc('user_id')
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        $data['hashpassword'] = Hash::make($data['password']);
        unset($data['password']);

        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['hashpassword'] = Hash::make($data['password']);
        }
        unset($data['password']);

        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Admin reset mật khẩu về giá trị mặc định.
     */
    public function resetPassword(User $user, string $newPassword = 'password123'): string
    {
        $user->update(['hashpassword' => Hash::make($newPassword)]);

        return $newPassword;
    }
}
