@extends('layouts.guest')

@section('title', 'Đăng ký')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">Đăng ký tài khoản</h2>
<p class="text-sm text-slate-500 mb-6">Tài khoản mới mặc định là <b>Sinh viên</b>.</p>

<form method="POST" action="{{ route('register.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Họ và tên</label>
        <input type="text" name="full_name" value="{{ old('full_name') }}" required autofocus
               placeholder="Nguyễn Văn A"
               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('full_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required placeholder="ban@lms.vn"
               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Chuyên ngành</label>
        <select name="major_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none bg-white">
            <option value="">— Chọn chuyên ngành —</option>
            @foreach ($majors as $major)
                <option value="{{ $major->major_id }}" @selected(old('major_id') == $major->major_id)>{{ $major->major_name }} ({{ $major->major_code }})</option>
            @endforeach
        </select>
        @error('major_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nhập lại mật khẩu</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        </div>
    </div>
    <button class="w-full rounded-lg bg-indigo-600 py-2.5 font-semibold text-white transition hover:bg-indigo-700">
        Tạo tài khoản
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-500">
    Đã có tài khoản?
    <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:underline">Đăng nhập</a>
</p>
@endsection
