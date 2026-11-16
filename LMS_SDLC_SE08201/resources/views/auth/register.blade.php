@extends('layouts.guest')

@section('title', 'Đăng ký tài khoản')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-slate-900">Tạo tài khoản</h2>
    <p class="text-xs text-slate-500 mt-1">Điền thông tin để tạo tài khoản sinh viên.</p>
</div>

<form method="POST" action="{{ route('register.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Họ và tên</label>
        <input type="text" name="full_name" value="{{ old('full_name') }}" required autofocus
               placeholder="Nguyễn Văn A"
               class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] outline-none transition duration-200">
        @error('full_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    @if(isset($majors) && $majors->isNotEmpty())
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Chuyên ngành</label>
        <select name="major_id" class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-[#1e4f8f] outline-none transition duration-200">
            <option value="">— Chọn chuyên ngành (Tùy chọn) —</option>
            @foreach($majors as $major)
                <option value="{{ $major->major_id }}" {{ old('major_id') == $major->major_id ? 'selected' : '' }}>{{ $major->major_name }}</option>
            @endforeach
        </select>
        @error('major_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email học tập</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               placeholder="name@lms.vn"
               class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] outline-none transition duration-200">
        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mật khẩu</label>
        <input type="password" name="password" required placeholder="Tối thiểu 6 ký tự"
               class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] outline-none transition duration-200">
        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Xác nhận mật khẩu</label>
        <input type="password" name="password_confirmation" required placeholder="Nhập lại mật khẩu"
               class="w-full rounded-md bg-white border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] outline-none transition duration-200">
    </div>

    <button type="submit" class="w-full rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] py-2.5 font-medium text-sm text-white cursor-pointer">
        Đăng ký
    </button>
</form>

<div class="mt-6 pt-5 border-t border-slate-200 text-center text-xs text-slate-500">
    Đã có tài khoản? 
    <a href="{{ route('login') }}" class="font-semibold text-[#1e4f8f] hover:underline">Đăng nhập</a>
</div>
@endsection
