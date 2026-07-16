@extends('layouts.app')

@section('title', 'Dashboard giảng viên')

@section('content')
{{-- Thống kê --}}
<div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow">
        <p class="text-sm text-indigo-100">Lớp phụ trách</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $classes->count() }}</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow">
        <p class="text-sm text-emerald-100">Tổng sinh viên</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $totalStudents }}</p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow">
        <p class="text-sm text-amber-100">Bài chờ chấm</p>
        <p class="mt-1 text-3xl font-extrabold">{{ $pendingGrading }}</p>
        <a href="{{ route('teacher.assignments.index') }}" class="mt-1 inline-block text-xs text-amber-100 underline">Chấm ngay →</a>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Lịch dạy tuần này --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-800">🗓️ Lịch dạy tuần này</h2></div>
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
                    @if ($s->session_date->isToday())
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hôm nay</span>
                    @endif
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Tuần này không có lịch dạy.</li>
            @endforelse
        </ul>
    </div>

    {{-- Tiến độ lớp --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-800">📈 Tiến độ các lớp</h2></div>
        <ul class="divide-y divide-slate-100">
            @forelse ($classes as $c)
                @php($pct = $c->total_sessions > 0 ? min(100, round($c->done_sessions_count / $c->total_sessions * 100)) : 0)
                <li class="px-5 py-3">
                    <div class="flex items-center justify-between text-sm">
                        <a href="{{ route('teacher.classes.show', $c) }}" class="font-semibold text-indigo-600 hover:underline">{{ $c->class_name }}</a>
                        <span class="text-xs text-slate-400">{{ $c->done_sessions_count }}/{{ $c->total_sessions }} buổi · {{ $c->enrollments_count }} SV</span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500" style="width: {{ $pct }}%"></div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Bạn chưa được phân công lớp nào.</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- Thông báo --}}
<div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-800">🔔 Thông báo mới</h2></div>
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
            <li class="px-5 py-6 text-center text-sm text-slate-400">Không có thông báo mới.</li>
        @endforelse
    </ul>
</div>
@endsection
