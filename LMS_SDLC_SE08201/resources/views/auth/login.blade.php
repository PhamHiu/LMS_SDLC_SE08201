@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-black text-white tracking-tight">Đăng Nhập</h2>
    <p class="text-xs text-slate-400 mt-1">Chào mừng bạn trở lại với không gian học tập thông minh!</p>
</div>

<form method="POST" action="{{ route('login.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Tài khoản Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="name@lms.vn"
               class="w-full rounded-xl bg-[#030407]/80 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#D4FF24] focus:ring-2 focus:ring-[#D4FF24]/20 focus:bg-[#030407] outline-none transition duration-200">
        @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Mật khẩu</label>
        </div>
        <input type="password" name="password" required placeholder="••••••••"
               class="w-full rounded-xl bg-[#030407]/80 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#D4FF24] focus:ring-2 focus:ring-[#D4FF24]/20 focus:bg-[#030407] outline-none transition duration-200">
        @error('password') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-between text-xs text-slate-400 py-1">
        <label class="flex items-center gap-2 cursor-pointer hover:text-slate-200 transition">
            <input type="checkbox" name="remember" value="1" class="rounded bg-slate-800 border-white/20 text-[#1558C9] focus:ring-[#D4FF24]">
            <span>Duy trì đăng nhập</span>
        </label>
    </div>

    <button type="submit" class="w-full rounded-xl bg-[#D4FF24] hover:bg-[#c3ee15] text-[#030407] py-3.5 font-black text-sm tracking-wide transition-all duration-300 shadow-lg shadow-[#D4FF24]/25 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
        TRUY CẬP HỆ THỐNG →
    </button>
</form>

<div class="mt-6 pt-5 border-t border-white/10 text-center text-xs text-slate-400">
    Chưa có tài khoản? 
    <a href="{{ route('register') }}" class="font-bold text-[#D4FF24] hover:underline transition">Đăng ký thành viên</a>
</div>

<div class="mt-6 rounded-2xl bg-[#030407]/70 border border-white/10 p-3.5 text-[11px] text-slate-400 space-y-1 backdrop-blur-sm">
    <div class="font-bold text-[#D4FF24] flex items-center gap-1">
        <span>⚡</span> Tài khoản Demo kiểm thử (pass: <code>password</code>)
    </div>
    <div class="flex flex-col gap-0.5 text-slate-300 pt-1">
        <div>• <strong>Admin:</strong> admin@lms.vn</div>
        <div>• <strong>Giảng viên:</strong> teacher1@lms.vn</div>
        <div>• <strong>Sinh viên:</strong> student1@lms.vn</div>
    </div>
</div>
@endsection
