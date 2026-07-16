@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    {{-- Cập nhật thông tin --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
        <h2 class="text-base font-bold text-slate-800 mb-4">Thông tin cá nhân</h2>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-4">
                @if ($user->avatar)
                    <img src="{{ asset('storage/'.$user->avatar) }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-indigo-200" alt="avatar">
                @else
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-2xl font-bold text-indigo-600">{{ mb_substr($user->full_name, 0, 1) }}</span>
                @endif
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ảnh đại diện</label>
                    <input type="file" name="avatar" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Họ và tên</label>
                <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ngày sinh</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Giới tính</label>
                    <select name="gender" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm bg-white focus:border-indigo-500 outline-none">
                        <option value="">—</option>
                        <option value="1" @selected(old('gender', $user->gender) === 1 || old('gender') === '1')>Nam</option>
                        <option value="0" @selected(old('gender', $user->gender) === 0 || old('gender') === '0')>Nữ</option>
                        <option value="2" @selected(old('gender', $user->gender) === 2 || old('gender') === '2')>Khác</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Địa chỉ</label>
                <input type="text" name="address" value="{{ old('address', $user->address) }}"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">
                <p>Email: <b class="text-slate-700">{{ $user->email }}</b> · Vai trò: <b class="text-slate-700">{{ $user->roleLabel() }}</b>
                @if ($user->major) · Ngành: <b class="text-slate-700">{{ $user->major->major_name }}</b> @endif</p>
            </div>
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lưu thay đổi</button>
        </form>
    </div>

    {{-- Đổi mật khẩu --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200 h-fit">
        <h2 class="text-base font-bold text-slate-800 mb-4">Đổi mật khẩu</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu hiện tại</label>
                <input type="password" name="current_password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu mới</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nhập lại mật khẩu mới</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <button class="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">Đổi mật khẩu</button>
        </form>
    </div>
</div>
@endsection
