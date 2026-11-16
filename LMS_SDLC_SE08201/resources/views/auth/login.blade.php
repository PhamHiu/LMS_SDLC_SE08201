@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-slate-900">Đăng nhập</h2>
    <p class="text-xs text-slate-500 mt-1">Dùng email và mật khẩu được nhà trường cấp.</p>
</div>

<form method="POST" action="{{ route('login.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tài khoản Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="name@lms.vn"
               class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/30 focus:bg-white outline-none transition duration-200">
        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-sm font-medium text-slate-700">Mật khẩu</label>
        </div>
        <input type="password" name="password" required placeholder="••••••••"
               class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/30 focus:bg-white outline-none transition duration-200">
        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-between text-xs text-slate-500 py-1">
        <label class="flex items-center gap-2 cursor-pointer hover:text-slate-700 transition">
            <input type="checkbox" name="remember" value="1" class="rounded bg-slate-50 border-slate-200 text-[#1e4f8f] focus:ring-[#1e4f8f]">
            <span>Duy trì đăng nhập</span>
        </label>
    </div>

    <button type="submit" class="w-full rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] text-white py-2.5 font-medium text-sm cursor-pointer">
        Đăng nhập
    </button>
</form>

<div class="mt-6 pt-5 border-t border-slate-200 text-center text-xs text-slate-500">
    Chưa có tài khoản? 
    <a href="{{ route('register') }}" class="font-bold text-[#1e4f8f] hover:underline transition">Đăng ký thành viên</a>
</div>

<div class="mt-6 rounded-lg bg-white border border-slate-200 p-3.5 text-[11px] text-slate-500 space-y-1">
    <div class="font-bold text-[#1e4f8f] flex items-center gap-1">
        Tài khoản demo (mật khẩu: <code>password</code>)
    </div>
    <div class="flex flex-col gap-0.5 text-slate-600 pt-1">
        <div>• <strong>Admin:</strong> admin@lms.vn</div>
        <div>• <strong>Giảng viên:</strong> teacher1@lms.vn</div>
        <div>• <strong>Sinh viên:</strong> student1@lms.vn</div>
    </div>
</div>
@endsection
