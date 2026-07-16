<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    /** Trang hồ sơ cá nhân (dùng chung cho cả 3 role) */
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profileService->updateProfile(
            $request->user(),
            $request->safe()->except('avatar'),
            $request->file('avatar'),
        );

        return back()->with('success', 'Cập nhật hồ sơ thành công.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->profileService->updatePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('password'),
        );

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }
}
