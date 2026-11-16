@extends('layouts.app')

@section('title', 'Quản lý chuyên ngành')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight !text-slate-900 flex items-center gap-2.5">
            <svg class="w-6 h-6 text-[#1e4f8f] inline-block filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span>Quản lý chuyên ngành</span>
        </h1>
        <p class="text-xs !text-slate-600 mt-1">Danh sách khoa và chuyên ngành đào tạo trong hệ thống</p>
    </div>
</div>

<form method="GET" id="searchForm" class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <!-- Nhóm 1: Thanh tìm kiếm -->
    <div class="flex items-center justify-between w-full md:w-auto gap-3 order-1 md:order-none">
        <!-- Search bar -->
        <div class="flex-1 md:flex-none relative w-full md:w-64 lg:w-80">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm chuyên ngành..."
                   class="w-full rounded-md border border-slate-200 bg-white pl-4 pr-10 py-2.5 text-sm !text-slate-900 placeholder-slate-400 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/30 outline-none"
                   oninput="clearTimeout(this.delay); this.delay = setTimeout(() => document.getElementById('searchForm').submit(), 500)">
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#1e4f8f] pointer-events-none filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        
        <!-- Nút Làm mới -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Mobile: Chỉ icon, padding nhỏ -->
            <a href="{{ route('admin.majors.index') }}" class="md:hidden rounded-md border border-slate-200 bg-slate-50 p-2.5 !text-slate-900 hover:bg-slate-100" title="Làm mới">
                <svg class="w-5 h-5 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </a>
            <!-- Desktop: Icon + Text -->
            <a href="{{ route('admin.majors.index') }}" class="hidden md:flex items-center rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-[#1e4f8f] px-4 py-2.5 text-sm font-semibold !text-slate-900 transition whitespace-nowrap" title="Làm mới">
                <svg class="w-4 h-4 text-[#1e4f8f] mr-1.5 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Làm mới
            </a>
        </div>
    </div>

    <!-- Nút Thêm -->
    <div class="flex justify-end shrink-0 order-3 md:order-none">
        <a href="{{ route('admin.majors.create') }}" class="inline-flex items-center gap-1.5 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2.5 text-sm font-bold !text-white shadow-sm transition whitespace-nowrap">
            <svg class="w-4 h-4 text-white inline-block filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Thêm chuyên ngành</span>
        </a>
    </div>
</form>

<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm glass-card">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider !text-slate-700 border-b border-slate-200">
            <tr>
                <th class="px-5 py-3.5">Mã</th>
                <th class="px-5 py-3.5">Tên chuyên ngành</th>
                <th class="px-5 py-3.5 text-center">Môn học</th>
                <th class="px-5 py-3.5 text-center">Người dùng</th>
                <th class="px-5 py-3.5 text-center">Lớp</th>
                <th class="px-5 py-3.5 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($majors as $major)
                <tr class="hover:bg-slate-100 transition duration-150">
                    <td class="px-5 py-3.5">
                        <span class="rounded-lg bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 px-2.5 py-1 text-xs font-bold text-[#1e4f8f]">{{ $major->major_code }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="font-bold !text-slate-900">{{ $major->major_name }}</p>
                        <p class="text-xs !text-slate-500 line-clamp-1 mt-0.5">{{ $major->description }}</p>
                    </td>
                    <td class="px-5 py-3.5 text-center !text-slate-700 font-bold">{{ $major->subjects_count }}</td>
                    <td class="px-5 py-3.5 text-center !text-slate-700 font-bold">{{ $major->users_count }}</td>
                    <td class="px-5 py-3.5 text-center !text-slate-700 font-bold">{{ $major->classes_count }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.majors.edit', $major) }}" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition">
                                <svg class="w-3.5 h-3.5 text-white inline-block filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Sửa</span>
                            </a>
                            <form method="POST" action="{{ route('admin.majors.destroy', $major) }}" onsubmit="return confirm('Xóa chuyên ngành {{ $major->major_name }}?')">
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
                <tr><td colspan="6" class="px-5 py-10 text-center !text-slate-500">Chưa có chuyên ngành nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $majors->links() }}</div>
@endsection
