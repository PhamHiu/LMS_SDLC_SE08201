@extends('layouts.app')

@section('title', 'Quản lý lớp học')

@section('content')
<form method="GET" id="searchForm" class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <input type="hidden" name="status" id="status_input" value="{{ $status }}">

    <div class="flex items-center justify-between w-full md:w-auto gap-3 order-1 md:order-none">
        <!-- Search bar -->
        <div class="flex-1 md:flex-none relative w-full md:w-64 lg:w-80">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm tên lớp..."
                   class="w-full rounded-lg border border-slate-300 pl-4 pr-10 py-2 text-sm focus:border-indigo-500 outline-none"
                   oninput="clearTimeout(this.delay); this.delay = setTimeout(() => document.getElementById('searchForm').submit(), 500)">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>

        <!-- Bộ lọc Desktop -->
        <div class="hidden md:flex items-center gap-2">
            <select class="rounded-lg border border-slate-300 px-4 py-2 text-sm bg-white outline-none focus:border-indigo-500" onchange="document.getElementById('status_input').value=this.value; document.getElementById('searchForm').submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="0" @selected($status === '0')>Chưa mở</option>
                <option value="1" @selected($status === '1')>Đang học</option>
                <option value="2" @selected($status === '2')>Đã đóng</option>
            </select>
            <a href="{{ route('admin.classes.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap" title="Làm mới">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Làm mới
            </a>
        </div>

        <!-- Các nút Mobile (Bên phải thanh tìm kiếm) -->
        <div class="flex md:hidden items-center gap-2 shrink-0">
            <!-- Nút Làm mới Mobile -->
            <a href="{{ route('admin.classes.index') }}" class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 hover:bg-slate-50" title="Làm mới">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </a>

            <!-- Nút filter Mobile Toggle -->
            <button type="button" onclick="document.getElementById('mobileFilter').classList.toggle('hidden')" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            </button>
        </div>
    </div>

    <!-- Dropdown bộ lọc Mobile -->
    <div id="mobileFilter" class="hidden md:hidden w-full flex-col gap-2 order-2 md:order-none">
        <select class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm bg-white outline-none focus:border-indigo-500" onchange="document.getElementById('status_input').value=this.value; document.getElementById('searchForm').submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="0" @selected($status === '0')>Chưa mở</option>
            <option value="1" @selected($status === '1')>Đang học</option>
            <option value="2" @selected($status === '2')>Đã đóng</option>
        </select>
    </div>

    <!-- Nút Thêm -->
    <div class="flex justify-end shrink-0 order-3 md:order-none">
        <a href="{{ route('admin.classes.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 whitespace-nowrap">+ Tạo lớp học</a>
    </div>
</form>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Lớp</th>
                <th class="px-5 py-3">Môn học</th>
                <th class="px-5 py-3">Giảng viên</th>
                <th class="px-5 py-3 text-center">Sĩ số</th>
                <th class="px-5 py-3">Thời gian</th>
                <th class="px-5 py-3">Trạng thái</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($classes as $class)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('admin.classes.show', $class) }}" class="font-bold text-indigo-600 hover:underline">{{ $class->class_name }}</a>
                        <p class="text-xs text-slate-400">{{ $class->major?->major_code }}</p>
                    </td>
                    <td class="px-5 py-3 text-slate-600">{{ $class->subject?->subject_name }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $class->teacher?->full_name }}</td>
                    <td class="px-5 py-3 text-center"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $class->enrollments_count }}</span></td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $class->start_date?->format('d/m/Y') }} → {{ $class->end_date?->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $class->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($class->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $class->statusLabel() }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.classes.show', $class) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Quản lý</a>
                            <a href="{{ route('admin.classes.edit', $class) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.classes.destroy', $class) }}" onsubmit="return confirm('Xóa lớp {{ $class->class_name }}? Toàn bộ dữ liệu liên quan sẽ bị xóa.')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Chưa có lớp học nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $classes->links() }}</div>
@endsection
