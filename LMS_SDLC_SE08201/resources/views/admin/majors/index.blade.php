@extends('layouts.app')

@section('title', 'Quản lý chuyên ngành')

@section('content')
<form method="GET" id="searchForm" class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <!-- Nhóm 1: Thanh tìm kiếm -->
    <div class="flex items-center justify-between w-full md:w-auto gap-3 order-1 md:order-none">
        <!-- Search bar -->
        <div class="flex-1 md:flex-none relative w-full md:w-64 lg:w-80">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm chuyên ngành..."
                   class="w-full rounded-lg border border-slate-300 pl-4 pr-10 py-2 text-sm focus:border-indigo-500 outline-none"
                   oninput="clearTimeout(this.delay); this.delay = setTimeout(() => document.getElementById('searchForm').submit(), 500)">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        
        <!-- Nút Làm mới -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Mobile: Chỉ icon, padding nhỏ -->
            <a href="{{ route('admin.majors.index') }}" class="md:hidden rounded-lg border border-slate-300 bg-white p-2 text-slate-600 hover:bg-slate-50" title="Làm mới">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </a>
            <!-- Desktop: Icon + Text -->
            <a href="{{ route('admin.majors.index') }}" class="hidden md:flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap" title="Làm mới">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Làm mới
            </a>
        </div>
    </div>

    <!-- Nút Thêm -->
    <div class="flex justify-end shrink-0 order-3 md:order-none">
        <a href="{{ route('admin.majors.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 whitespace-nowrap">+ Thêm chuyên ngành</a>
    </div>
</form>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Mã</th>
                <th class="px-5 py-3">Tên chuyên ngành</th>
                <th class="px-5 py-3 text-center">Môn học</th>
                <th class="px-5 py-3 text-center">Người dùng</th>
                <th class="px-5 py-3 text-center">Lớp</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($majors as $major)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><span class="rounded bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">{{ $major->major_code }}</span></td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-slate-700">{{ $major->major_name }}</p>
                        <p class="text-xs text-slate-400 line-clamp-1">{{ $major->description }}</p>
                    </td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $major->subjects_count }}</td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $major->users_count }}</td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $major->classes_count }}</td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.majors.edit', $major) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.majors.destroy', $major) }}" onsubmit="return confirm('Xóa chuyên ngành {{ $major->major_name }}?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Chưa có chuyên ngành nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $majors->links() }}</div>
@endsection
