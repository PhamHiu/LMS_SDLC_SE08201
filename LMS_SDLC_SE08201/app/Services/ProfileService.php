<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Cập nhật hồ sơ cá nhân (kèm avatar nếu có).
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        if ($avatar) {
            // Xóa avatar cũ nếu tồn tại
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $avatar->store('avatars', 'public');
        }

        $user->update($data);

        return $user;
    }

    /**
     * Đổi mật khẩu — yêu cầu xác thực mật khẩu hiện tại.
     */
    public function updatePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->hashpassword)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $user->update(['hashpassword' => Hash::make($new)]);
    }
}
