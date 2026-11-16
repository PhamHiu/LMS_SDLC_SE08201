@extends('layouts.app')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight !text-slate-900 flex items-center gap-2.5">
            <svg class="w-6 h-6 text-[#1e4f8f] inline-block filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Quản lý người dùng</span>
        </h1>
        <p class="text-xs !text-slate-600 mt-1">Danh sách sinh viên, giảng viên và quản trị viên trong hệ thống</p>
    </div>
</div>

<form method="GET" id="searchForm" class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <input type="hidden" name="role" id="role_input" value="{{ $role }}">

    <div class="flex items-center justify-between w-full md:w-auto gap-3 order-1 md:order-none">
        <!-- Search bar -->
        <div class="flex-1 md:flex-none relative w-full md:w-64 lg:w-80">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm mã, tên hoặc email..."
                   class="w-full rounded-md border border-slate-200 bg-white pl-4 pr-10 py-2.5 text-sm !text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/30 outline-none"
                   oninput="clearTimeout(this.delay); this.delay = setTimeout(() => document.getElementById('searchForm').submit(), 500)">
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#1e4f8f] pointer-events-none filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>

        <!-- Bộ lọc Desktop -->
        <div class="hidden md:flex items-center gap-2">
            <select class="rounded-md border border-slate-200 bg-white px-4 py-2.5 text-sm !text-slate-900 outline-none focus:border-[#1e4f8f]" onchange="document.getElementById('role_input').value=this.value; document.getElementById('searchForm').submit()">
                <option value="" class="bg-white text-slate-600">Tất cả vai trò</option>
                <option value="ADMIN" class="bg-white text-slate-900" @selected($role === 'ADMIN')>Quản trị viên</option>
                <option value="TEACHER" class="bg-white text-slate-900" @selected($role === 'TEACHER')>Giảng viên</option>
                <option value="STUDENT" class="bg-white text-slate-900" @selected($role === 'STUDENT')>Sinh viên</option>
            </select>
            <a href="{{ route('admin.users.index') }}" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-[#1e4f8f] px-4 py-2.5 text-sm font-semibold !text-slate-900 transition whitespace-nowrap" title="Làm mới">
                <svg class="w-4 h-4 text-[#1e4f8f] inline-block mr-1 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Làm mới
            </a>
        </div>

        <!-- Các nút Mobile (Bên phải thanh tìm kiếm) -->
        <div class="flex md:hidden items-center gap-2 shrink-0">
            <!-- Nút Làm mới Mobile -->
            <a href="{{ route('admin.users.index') }}" class="rounded-md border border-slate-200 bg-slate-50 p-2.5 !text-slate-900 hover:bg-slate-100" title="Làm mới">
                <svg class="w-5 h-5 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </a>

            <!-- Nút filter Mobile Toggle -->
            <button type="button" onclick="document.getElementById('mobileFilter').classList.toggle('hidden')" class="rounded-md border border-slate-200 bg-slate-50 p-2.5 !text-slate-900 hover:bg-slate-100">
                <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            </button>
        </div>
    </div>

    <!-- Dropdown bộ lọc Mobile -->
    <div id="mobileFilter" class="hidden md:hidden w-full flex-col gap-2 order-2 md:order-none">
        <select class="w-full rounded-md border border-slate-200 bg-white px-4 py-2.5 text-sm !text-slate-900 outline-none focus:border-[#1e4f8f]" onchange="document.getElementById('role_input').value=this.value; document.getElementById('searchForm').submit()">
            <option value="" class="bg-white text-slate-600">Tất cả vai trò</option>
            <option value="ADMIN" class="bg-white text-slate-900" @selected($role === 'ADMIN')>Quản trị viên</option>
            <option value="TEACHER" class="bg-white text-slate-900" @selected($role === 'TEACHER')>Giảng viên</option>
            <option value="STUDENT" class="bg-white text-slate-900" @selected($role === 'STUDENT')>Sinh viên</option>
        </select>
    </div>

    <!-- Nút Thêm -->
    <div class="flex justify-end shrink-0 order-3 md:order-none">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-1.5 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2.5 text-sm font-bold !text-white shadow-sm transition whitespace-nowrap">
            <svg class="w-4 h-4 text-white inline-block filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Thêm tài khoản</span>
        </a>
    </div>
</form>

<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm glass-card">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider !text-slate-700 border-b border-slate-200">
            <tr>
                <th class="px-5 py-3.5">Người dùng</th>
                <th class="px-5 py-3.5">Vai trò</th>
                <th class="px-5 py-3.5">Chuyên ngành</th>
                <th class="px-5 py-3.5">SĐT</th>
                <th class="px-5 py-3.5 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($users as $user)
                <tr class="hover:bg-slate-100 transition duration-150">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 text-sm font-bold text-[#1e4f8f] shadow-sm">{{ mb_substr($user->full_name, 0, 1) }}</span>
                            <div>
                                <p class="font-bold !text-slate-900">{{ $user->full_name }}</p>
                                <p class="text-xs !text-slate-600">
                                    @if($user->student_code) <span class="font-semibold text-sky-600">{{ $user->student_code }}</span> • @endif
                                    {{ $user->email }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold border
 {{ $user->role === 'ADMIN' ? 'bg-rose-50 border-rose-200 text-rose-700' : ($user->role === 'TEACHER' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700') }}">
                            {{ $user->roleLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 !text-slate-700">{{ $user->major?->major_code ?? '—' }}</td>
                    <td class="px-5 py-3.5 !text-slate-700">{{ $user->phone ?? '—' }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition">
                                <svg class="w-3.5 h-3.5 text-white inline-block filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
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
                <tr><td colspan="5" class="px-5 py-10 text-center !text-slate-500">Không tìm thấy người dùng nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
