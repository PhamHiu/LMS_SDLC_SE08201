@extends('layouts.app')

@section('title', 'Dashboard quản trị')

@section('content')
{{-- Thống kê tổng quan --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow">
        <p class="text-sm text-indigo-100">Tổng người dùng</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $total_users }}</p>
        <p class="mt-2 text-xs text-indigo-200">{{ $total_students }} sinh viên · {{ $total_teachers }} giảng viên</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow">
        <p class="text-sm text-emerald-100">Lớp học</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $total_classes }}</p>
        <p class="mt-2 text-xs text-emerald-200">{{ $active_classes }} lớp đang hoạt động</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow">
        <p class="text-sm text-amber-100">Môn học</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $total_subjects }}</p>
        <p class="mt-2 text-xs text-amber-100">Trong chương trình đào tạo</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-fuchsia-500 to-purple-700 p-5 text-white shadow">
        <p class="text-sm text-fuchsia-100">Chuyên ngành</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $total_majors }}</p>
        <p class="mt-2 text-xs text-fuchsia-100">Khoa / ngành đào tạo</p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Người dùng mới --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">Người dùng mới nhất</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Xem tất cả →</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($recent_users as $ru)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">{{ mb_substr($ru->full_name, 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $ru->full_name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $ru->email }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $ru->roleLabel() }}</span>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-slate-400">Chưa có dữ liệu</li>
            @endforelse
        </ul>
    </div>

    {{-- Lớp mới --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">Lớp học gần đây</h2>
            <a href="{{ route('admin.classes.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Xem tất cả →</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($recent_classes as $rc)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $rc->class_name }} — {{ $rc->subject?->subject_name }}</p>
                        <p class="truncate text-xs text-slate-400">GV: {{ $rc->teacher?->full_name }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $rc->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($rc->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $rc->statusLabel() }}</span>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-slate-400">Chưa có dữ liệu</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- Thông báo gần đây --}}
<div class="mt-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-bold text-slate-800">Hoạt động thông báo gần đây</h2>
        <a href="{{ route('admin.notifications.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Quản lý →</a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($recent_notifications as $n)
            <li class="px-5 py-3">
                <p class="text-sm font-semibold text-slate-700">{{ $n->title }}
                    @if ($n->isSystemWide()) <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] font-medium text-indigo-600">Toàn hệ thống</span> @endif
                </p>
                <p class="text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
            </li>
        @empty
            <li class="px-5 py-6 text-center text-sm text-slate-400">Chưa có thông báo</li>
        @endforelse
    </ul>
</div>
@endsection
