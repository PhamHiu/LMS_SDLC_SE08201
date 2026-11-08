@extends('layouts.guest')

@section('title', 'Đăng ký tài khoản')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-black text-white tracking-tight">Tạo Tài Khoản Mới</h2>
    <p class="text-xs text-slate-400 mt-1">Gia nhập cộng đồng sinh viên công nghệ thông minh!</p>
</div>

<form method="POST" action="{{ route('register.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Họ và tên</label>
        <input type="text" name="full_name" value="{{ old('full_name') }}" required autofocus
               placeholder="Nguyễn Văn A"
               class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:bg-slate-900 outline-none transition duration-200">
        @error('full_name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
    </div>

    @if(isset($majors) && $majors->isNotEmpty())
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Chuyên ngành</label>
        <select name="major_id" class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:bg-slate-900 outline-none transition duration-200">
            <option value="">— Chọn chuyên ngành (Tùy chọn) —</option>
            @foreach($majors as $major)
                <option value="{{ $major->major_id }}" {{ old('major_id') == $major->major_id ? 'selected' : '' }}>{{ $major->major_name }}</option>
            @endforeach
        </select>
        @error('major_id') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
    </div>
    @endif

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Email học tập</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               placeholder="name@lms.vn"
               class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:bg-slate-900 outline-none transition duration-200">
        @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Mật khẩu</label>
        <input type="password" name="password" required placeholder="Tối thiểu 6 ký tự"
               class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:bg-slate-900 outline-none transition duration-200">
        @error('password') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Xác nhận mật khẩu</label>
        <input type="password" name="password_confirmation" required placeholder="Nhập lại mật khẩu"
               class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:bg-slate-900 outline-none transition duration-200">
    </div>

    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-400 via-teal-400 to-emerald-500 hover:from-emerald-300 hover:to-teal-300 py-3.5 font-black text-sm tracking-wide text-slate-950 transition-all duration-300 shadow-lg shadow-emerald-500/25 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
        ĐĂNG KÝ NGAY →
    </button>
</form>

<div class="mt-6 pt-5 border-t border-white/10 text-center text-xs text-slate-400">
    Đã có tài khoản? 
    <a href="{{ route('login') }}" class="font-bold text-emerald-400 hover:text-emerald-300 transition">Đăng nhập tại đây</a>
</div>
@endsection
