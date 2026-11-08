@extends('layouts.app')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black tracking-tight !text-white flex items-center gap-2.5">
            <svg class="w-6 h-6 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Quản lý người dùng</span>
        </h1>
        <p class="text-xs !text-slate-300 mt-1">Danh sách sinh viên, giảng viên và quản trị viên trong hệ thống</p>
    </div>
</div>

<form method="GET" id="searchForm" class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <input type="hidden" name="role" id="role_input" value="{{ $role }}">

    <div class="flex items-center justify-between w-full md:w-auto gap-3 order-1 md:order-none">
        <!-- Search bar -->
        <div class="flex-1 md:flex-none relative w-full md:w-64 lg:w-80">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm mã, tên hoặc email..."
                   class="w-full rounded-xl border border-white/15 bg-[#0a0d17]/90 pl-4 pr-10 py-2.5 text-sm !text-white placeholder-slate-400 focus:border-[#1558C9] focus:ring-2 focus:ring-[#1558C9]/30 outline-none"
                   oninput="clearTimeout(this.delay); this.delay = setTimeout(() => document.getElementById('searchForm').submit(), 500)">
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#D4FF24] pointer-events-none filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>

        <!-- Bộ lọc Desktop -->
        <div class="hidden md:flex items-center gap-2">
            <select class="rounded-xl border border-white/15 bg-[#0a0d17]/90 px-4 py-2.5 text-sm !text-white outline-none focus:border-[#1558C9]" onchange="document.getElementById('role_input').value=this.value; document.getElementById('searchForm').submit()">
                <option value="" class="bg-[#030407] text-slate-300">Tất cả vai trò</option>
                <option value="ADMIN" class="bg-[#030407] text-white" @selected($role === 'ADMIN')>Quản trị viên</option>
                <option value="TEACHER" class="bg-[#030407] text-white" @selected($role === 'TEACHER')>Giảng viên</option>
                <option value="STUDENT" class="bg-[#030407] text-white" @selected($role === 'STUDENT')>Sinh viên</option>
            </select>
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-white/15 bg-white/10 hover:bg-white/20 hover:border-[#D4FF24]/40 px-4 py-2.5 text-sm font-semibold !text-white transition whitespace-nowrap" title="Làm mới">
                <svg class="w-4 h-4 text-[#D4FF24] inline-block mr-1 filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Làm mới
            </a>
        </div>

        <!-- Các nút Mobile (Bên phải thanh tìm kiếm) -->
        <div class="flex md:hidden items-center gap-2 shrink-0">
            <!-- Nút Làm mới Mobile -->
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-white/15 bg-white/10 p-2.5 !text-white hover:bg-white/20" title="Làm mới">
                <svg class="w-5 h-5 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </a>

            <!-- Nút filter Mobile Toggle -->
            <button type="button" onclick="document.getElementById('mobileFilter').classList.toggle('hidden')" class="rounded-xl border border-white/15 bg-white/10 p-2.5 !text-white hover:bg-white/20">
                <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            </button>
        </div>
    </div>

    <!-- Dropdown bộ lọc Mobile -->
    <div id="mobileFilter" class="hidden md:hidden w-full flex-col gap-2 order-2 md:order-none">
        <select class="w-full rounded-xl border border-white/15 bg-[#0a0d17]/90 px-4 py-2.5 text-sm !text-white outline-none focus:border-[#1558C9]" onchange="document.getElementById('role_input').value=this.value; document.getElementById('searchForm').submit()">
            <option value="" class="bg-[#030407] text-slate-300">Tất cả vai trò</option>
            <option value="ADMIN" class="bg-[#030407] text-white" @selected($role === 'ADMIN')>Quản trị viên</option>
            <option value="TEACHER" class="bg-[#030407] text-white" @selected($role === 'TEACHER')>Giảng viên</option>
            <option value="STUDENT" class="bg-[#030407] text-white" @selected($role === 'STUDENT')>Sinh viên</option>
        </select>
    </div>

    <!-- Nút Thêm -->
    <div class="flex justify-end shrink-0 order-3 md:order-none">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#1558C9] hover:bg-[#1558C9]/85 px-4 py-2.5 text-sm font-bold !text-white shadow-lg shadow-[#1558C9]/30 transition whitespace-nowrap">
            <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Thêm tài khoản</span>
        </a>
    </div>
</form>

<div class="overflow-x-auto rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl shadow-xl glass-card">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs font-bold uppercase tracking-wider !text-slate-200 border-b border-white/10">
            <tr>
                <th class="px-5 py-3.5">Người dùng</th>
                <th class="px-5 py-3.5">Vai trò</th>
                <th class="px-5 py-3.5">Chuyên ngành</th>
                <th class="px-5 py-3.5">SĐT</th>
                <th class="px-5 py-3.5 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse ($users as $user)
                <tr class="hover:bg-white/5 transition duration-150">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1558C9]/30 border border-[#1558C9]/50 text-sm font-black text-[#D4FF24] shadow-sm shadow-[#1558C9]/30">{{ mb_substr($user->full_name, 0, 1) }}</span>
                            <div>
                                <p class="font-bold !text-white">{{ $user->full_name }}</p>
                                <p class="text-xs !text-slate-300">
                                    @if($user->student_code) <span class="font-semibold text-sky-400">{{ $user->student_code }}</span> • @endif
                                    {{ $user->email }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold border
                            {{ $user->role === 'ADMIN' ? 'bg-rose-500/20 border-rose-400/30 text-rose-300' : ($user->role === 'TEACHER' ? 'bg-amber-500/20 border-amber-400/30 text-amber-300' : 'bg-emerald-500/20 border-emerald-400/30 text-emerald-300') }}">
                            {{ $user->roleLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 !text-slate-200">{{ $user->major?->major_code ?? '—' }}</td>
                    <td class="px-5 py-3.5 !text-slate-200">{{ $user->phone ?? '—' }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition">
                                <svg class="w-3.5 h-3.5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Sửa</span>
                            </a>
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Reset mật khẩu của {{ $user->full_name }}?')">
                                @csrf
                                <button class="inline-flex items-center gap-1 rounded-lg bg-amber-600 hover:bg-amber-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition">
                                    <span>Reset MK</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Xóa tài khoản {{ $user->full_name }}? Hành động không thể hoàn tác.')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-1 rounded-lg bg-rose-600 hover:bg-rose-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-white inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Xóa</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center !text-slate-400">Không tìm thấy người dùng nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
