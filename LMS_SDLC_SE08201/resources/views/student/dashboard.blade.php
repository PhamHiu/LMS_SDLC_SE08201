@extends('layouts.app')

@section('title', 'Dashboard sinh viên')

@section('content')
{{-- Thống kê --}}
<div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow">
        <p class="text-sm text-indigo-100">Lớp đang học</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $enrolledCount }}</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow">
        <p class="text-sm text-emerald-100">Điểm trung bình</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $avgScore !== null ? $avgScore : '—' }}</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow">
        <p class="text-sm text-amber-100">Tỷ lệ chuyên cần</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Bài tập sắp đến hạn --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">⏰ Bài tập sắp đến hạn</h2>
            <a href="{{ route('student.assignments.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Tất cả →</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($upcomingAssignments as $a)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <a href="{{ route('student.assignments.show', $a) }}" class="block truncate text-sm font-semibold text-indigo-600 hover:underline">{{ $a->title }}</a>
                        <p class="text-xs text-slate-400">{{ $a->classRoom?->class_name }} · {{ $a->typeLabel() }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $a->due_date->diffInHours(now()) < 48 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $a->due_date->diffForHumans() }}
                    </span>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">🎉 Không có bài tập nào chờ nộp.</li>
            @endforelse
        </ul>
    </div>

    {{-- Lịch học tuần này --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">🗓️ Lịch học tuần này</h2>
            <a href="{{ route('student.schedule.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">TKB →</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($weekSessions as $s)
                <li class="flex items-center gap-4 px-5 py-3">
                    <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl {{ $s->session_date->isToday() ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700' }}">
                        <span class="text-xs font-medium">{{ $s->session_date->isoFormat('dd') }}</span>
                        <span class="text-sm font-extrabold">{{ $s->session_date->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $s->classRoom?->class_name }} — {{ $s->classRoom?->subject?->subject_name }}</p>
                        <p class="text-xs text-slate-400">{{ $s->room }} · {{ $s->shift }}</p>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Tuần này không có lịch học.</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Kết quả gần đây --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">🏆 Kết quả gần đây</h2>
            <a href="{{ route('student.grades.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Bảng điểm →</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($recentGrades as $g)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $g->assignment?->title }}</p>
                        <p class="text-xs text-slate-400">{{ $g->assignment?->classRoom?->class_name }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-700">{{ $g->score }}/{{ $g->assignment?->max_score }}</span>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Chưa có kết quả nào.</li>
            @endforelse
        </ul>
    </div>

    {{-- Thông báo --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">🔔 Thông báo</h2>
            <a href="{{ route('student.notifications.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Tất cả →</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($notifications as $n)
                <li class="px-5 py-3">
                    <p class="text-sm font-semibold text-slate-700">{{ $n->title }}
                        @if ($n->isSystemWide())
                            <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] text-indigo-600">Toàn trường</span>
                        @else
                            <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[11px] text-emerald-600">{{ $n->classRoom?->class_name }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Không có thông báo mới.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
