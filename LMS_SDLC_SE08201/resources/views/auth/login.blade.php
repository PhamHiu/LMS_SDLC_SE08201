@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">Đăng nhập</h2>
<p class="text-sm text-slate-500 mb-6">Chào mừng quay trở lại! Vui lòng nhập thông tin.</p>

<form method="POST" action="{{ route('login.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="ban@lms.vn"
               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
        <input type="password" name="password" required placeholder="••••••••"
               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        Ghi nhớ đăng nhập
    </label>
    <button class="w-full rounded-lg bg-indigo-600 py-2.5 font-semibold text-white transition hover:bg-indigo-700">
        Đăng nhập
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-500">
    Chưa có tài khoản?
    <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:underline">Đăng ký ngay</a>
</p>

<div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-500">
    <p class="font-semibold text-slate-600 mb-1">Tài khoản demo (mật khẩu: <code>password</code>)</p>
    <p>Admin: admin@lms.vn · GV: teacher1@lms.vn · SV: student1@lms.vn</p>
</div>
@endsection
