@extends('layouts.app')

@section('title', $user ? 'Sửa tài khoản' : 'Thêm tài khoản')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-bold text-slate-800">{{ $user ? 'Cập nhật: '.$user->full_name : 'Tạo tài khoản mới' }}</h2>

    <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-4">
        @csrf
        @if ($user) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Họ và tên *</label>
                <input type="text" name="full_name" value="{{ old('full_name', $user?->full_name) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu {{ $user ? '(bỏ trống nếu giữ nguyên)' : '*' }}</label>
                <input type="password" name="password" {{ $user ? '' : 'required' }}
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vai trò *</label>
                <select name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    @foreach (['STUDENT' => 'Sinh viên', 'TEACHER' => 'Giảng viên', 'ADMIN' => 'Quản trị viên'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user?->role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Chuyên ngành</label>
                <select name="major_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Không —</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->major_id }}" @selected(old('major_id', $user?->major_id) == $major->major_id)>{{ $major->major_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ngày sinh</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user?->date_of_birth?->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Giới tính</label>
                <select name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">—</option>
                    <option value="1" @selected(old('gender', (string) $user?->gender) === '1')>Nam</option>
                    <option value="0" @selected(old('gender', (string) $user?->gender) === '0')>Nữ</option>
                    <option value="2" @selected(old('gender', (string) $user?->gender) === '2')>Khác</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="{{ old('phone', $user?->phone) }}"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Địa chỉ</label>
                <input type="text" name="address" value="{{ old('address', $user?->address) }}"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $user ? 'Cập nhật' : 'Tạo tài khoản' }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>
@endsection
