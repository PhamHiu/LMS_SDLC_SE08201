@extends('layouts.app')

@section('title', 'Quản lý môn học')

@section('content')
<form method="GET" id="searchForm" class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <input type="hidden" name="major_id" id="major_id_input" value="{{ request('major_id') }}">

    <!-- Nhóm 1: Thanh tìm kiếm & Bộ lọc (order 1 trên Mobile) -->
    <div class="flex items-center justify-between w-full md:w-auto gap-3 order-1 md:order-none">
        <!-- Search bar -->
        <div class="flex-1 md:flex-none relative w-full md:w-64 lg:w-80">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã môn, tên, C.ngành, lớp..."
                   class="w-full rounded-lg border border-slate-300 pl-4 pr-10 py-2 text-sm focus:border-indigo-500 outline-none"
                   oninput="clearTimeout(this.delay); this.delay = setTimeout(() => document.getElementById('searchForm').submit(), 500)">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>

        <!-- Bộ lọc Desktop -->
        <div class="hidden md:flex items-center gap-2">
            <select class="rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 outline-none" onchange="document.getElementById('major_id_input').value=this.value; document.getElementById('searchForm').submit()">
                <option value="">-- Tất cả chuyên ngành --</option>
                @foreach($majors as $major)
                    <option value="{{ $major->major_id }}" @selected(request('major_id') == $major->major_id)>
                        {{ $major->major_name }}
                    </option>
                @endforeach
            </select>
            <a href="{{ route('admin.subjects.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap">Làm mới</a>
        </div>

        <!-- Nút filter Mobile -->
        <button type="button" onclick="document.getElementById('mobileFilter').classList.toggle('hidden')" class="md:hidden shrink-0 rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        </button>
    </div>

    <!-- Nhóm 2: Dropdown bộ lọc Mobile (order 2 trên Mobile) -->
    <div id="mobileFilter" class="hidden md:hidden w-full flex-col gap-2 order-2 md:order-none">
        <select class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 outline-none" onchange="document.getElementById('major_id_input').value=this.value; document.getElementById('searchForm').submit()">
            <option value="">-- Tất cả chuyên ngành --</option>
            @foreach($majors as $major)
                <option value="{{ $major->major_id }}" @selected(request('major_id') == $major->major_id)>
                    {{ $major->major_name }}
                </option>
            @endforeach
        </select>
        <a href="{{ route('admin.subjects.index') }}" class="w-full text-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Làm mới</a>
    </div>

    <!-- Nhóm 3: Nút Thêm (order 3 trên Mobile) -->
    <div class="flex justify-end shrink-0 order-3 md:order-none">
        <a href="{{ route('admin.subjects.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 whitespace-nowrap">+ Thêm môn học</a>
    </div>
</form>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Mã môn</th>
                <th class="px-5 py-3">Tên môn học</th>
                <th class="px-5 py-3">Thuộc chuyên ngành</th>
                <th class="px-5 py-3 text-center">Lớp</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($subjects as $subject)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><span class="rounded bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700">{{ $subject->subject_code }}</span></td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-slate-700">{{ $subject->subject_name }}</p>
                        <p class="text-xs text-slate-400 line-clamp-1">{{ $subject->description }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($subject->majors as $mj)
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-600">{{ $mj->major_code }}</span>
                            @empty
                                <span class="text-xs text-slate-400">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $subject->classes_count }}</td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.subjects.edit', $subject) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('Xóa môn {{ $subject->subject_name }}?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Chưa có môn học nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $subjects->links() }}</div>
@endsection
