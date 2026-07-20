<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Major;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => $this->userService->paginate($request->query('role'), $request->query('search')),
            'role' => $request->query('role'),
            'search' => $request->query('search'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => null,
            'majors' => Major::orderBy('major_name')->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'Tạo tài khoản thành công.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'majors' => Major::orderBy('major_name')->get(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->user_id === $request->user()->user_id) {
            return back()->with('error', 'Không thể tự xóa tài khoản của chính mình.');
        }

        $this->userService->delete($user);

        return back()->with('success', 'Đã xóa tài khoản.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $newPassword = $this->userService->resetPassword($user);

        return back()->with('success', "Đã reset mật khẩu của {$user->full_name} về: {$newPassword}");
    }
}
