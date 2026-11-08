@extends('layouts.app')

@section('title', 'Dashboard giảng viên')

@section('content')
{{-- Header Greeting --}}
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-2.5">
            <span>Chào mừng Thầy/Cô, {{ auth()->user()->full_name }}</span>
            <span class="rounded-full bg-indigo-500/20 border border-indigo-400/30 px-3 py-0.5 text-xs font-black text-indigo-300 shadow-[0_0_10px_rgba(99,102,241,0.3)]">TEACHER PORTAL</span>
        </h1>
        <p class="text-xs text-slate-400 mt-1">Quản lý lớp học, điểm danh, chấm bài tập và theo dõi tiến độ đào tạo</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('teacher.assignments.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-600/30 hover:from-indigo-500 hover:to-purple-500 transition duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tạo bài tập mới</span>
        </a>
    </div>
</div>

{{-- ===== KPI CARDS (4 cards) - 3D POP-OUT & DIRECT NAVIGATION ===== --}}
<div class="grid gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
    {{-- Card 1: Lớp phụ trách --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-blue-700 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-indigo-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('teacher.classes.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Lớp phụ trách</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-sky-300 filter drop-shadow-[0_0_8px_rgba(56,189,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-black tracking-tight text-white">{{ $classes->count() }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-indigo-100">
            <span class="font-medium group-hover:text-white">Danh sách lớp dạy</span>
            <span class="inline-flex items-center gap-1 font-bold text-sky-300 group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>

    {{-- Card 2: Tổng sinh viên --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-700 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-emerald-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('teacher.classes.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-200">Tổng sinh viên</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-emerald-300 filter drop-shadow-[0_0_8px_rgba(110,231,183,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-black tracking-tight text-white">{{ $totalStudents }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-emerald-100">
            <span class="font-medium group-hover:text-white">Học sinh theo lớp</span>
            <span class="inline-flex items-center gap-1 font-bold text-emerald-200 group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>

    {{-- Card 3: Bài chờ chấm --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-orange-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('teacher.assignments.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-100">Bài chờ chấm</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-amber-300 filter drop-shadow-[0_0_8px_rgba(252,211,77,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-black tracking-tight text-white">{{ $pendingGrading }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-amber-100">
            <span class="font-medium group-hover:text-white">Vào chấm bài ngay</span>
            <span class="inline-flex items-center gap-1 font-bold text-amber-200 group-hover:translate-x-1.5 transition duration-200">Chấm ngay →</span>
        </div>
    </div>

    {{-- Card 4: Chuyên cần TB --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-cyan-600 via-teal-600 to-blue-700 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-cyan-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('teacher.attendance.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-cyan-200">Chuyên cần TB</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-cyan-300 filter drop-shadow-[0_0_8px_rgba(103,232,249,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-black tracking-tight text-white">{{ $attendance_avg !== null ? $attendance_avg.'%' : '—' }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-cyan-100">
            <span class="font-medium group-hover:text-white">Xem điểm danh các lớp</span>
            <span class="inline-flex items-center gap-1 font-bold text-cyan-200 group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>
</div>

{{-- ===== ROW 2: 2 Biểu đồ ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Bar Chart: Phân bố điểm --}}
    <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-white flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 border border-indigo-400/30 text-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.3)]">
                    <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(129,140,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <span>Phân bố điểm số sinh viên</span>
            </h2>
            <a href="{{ route('teacher.analytics.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition duration-200">
                <span>Xem thống kê</span>
                <span class="group-hover:translate-x-1 transition duration-200">→</span>
            </a>
        </div>
        <div class="relative w-full" style="height:260px">
            <canvas id="scoreDistChart"></canvas>
        </div>
    </div>

    {{-- Doughnut: Tỷ lệ nộp bài --}}
    <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-white flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/20 border border-emerald-400/30 text-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.3)]">
                    <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(52,211,153,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
                <span>Tỷ lệ nộp bài tập</span>
            </h2>
            <a href="{{ route('teacher.assignments.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition duration-200">
                <span>Chấm bài</span>
                <span class="group-hover:translate-x-1 transition duration-200">→</span>
            </a>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <div class="w-full max-w-[200px] mx-auto sm:mx-0 sm:w-48 shrink-0 aspect-square relative">
                <canvas id="submissionChart"></canvas>
            </div>
            <div class="flex-1 w-full space-y-3">
                <div class="p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-400/20 hover:scale-[1.02] transition duration-200">
                    <p class="text-xs text-slate-300">Tỷ lệ đã nộp bài</p>
                    <p class="text-2xl font-black text-emerald-400">{{ $submission_rate !== null ? $submission_rate.'%' : '—' }}</p>
                </div>
                <div class="p-3.5 rounded-xl bg-amber-500/15 border border-amber-400/20 hover:scale-[1.02] transition duration-200">
                    <p class="text-xs text-slate-300">Số bài chờ chấm</p>
                    <p class="text-2xl font-black text-amber-400">{{ $pendingGrading }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 3: Lịch dạy + Tiến độ lớp ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    {{-- Lịch dạy tuần này --}}
    <div class="glass-card border border-white/10 shadow-xl overflow-hidden flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/20 border border-sky-400/30 text-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(56,189,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span>Lịch dạy tuần này</span>
                </h2>
                <a href="{{ route('teacher.classes.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-sky-400 hover:text-sky-300 transition duration-200">
                    <span>Xem lớp dạy</span>
                    <span class="group-hover:translate-x-1 transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-white/5 max-h-[460px] overflow-y-auto pr-1 pb-2 scroll-smooth">
                @forelse ($weekSessions as $s)
                    <li class="flex items-center gap-4 px-6 py-3.5 hover:bg-white/5 hover:translate-x-1.5 transition-all duration-200 cursor-pointer"
                        onclick="window.location='{{ route('teacher.attendance.index') }}'">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl {{ $s->session_date->isToday() ? 'bg-indigo-600 text-white shadow-[0_0_15px_rgba(99,102,241,0.6)]' : 'bg-white/10 text-indigo-300 border border-white/10' }}">
                            <span class="text-[10px] uppercase font-bold tracking-wider">{{ $s->session_date->isoFormat('dd') }}</span>
                            <span class="text-sm font-black">{{ $s->session_date->format('d') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-100">{{ $s->classRoom?->class_name }} — {{ $s->classRoom?->subject?->subject_name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                @if ($s->slot)
                                    {{ $s->slot->label }} ({{ \Carbon\Carbon::parse($s->slot->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->slot->end_time)->format('H:i') }})
                                @endif
                                @if ($s->room) · {{ $s->room->room_name }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($s->attendances && $s->attendances->count() > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 px-2.5 py-1 text-xs font-bold text-emerald-300 shadow-sm">
                                    <svg class="h-3 w-3 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Đã điểm danh ({{ $s->attendances->count() }} SV)</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/20 border border-amber-400/30 px-2.5 py-1 text-xs font-bold text-amber-300 shadow-sm">
                                    <svg class="h-3 w-3 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Chưa điểm danh</span>
                                </span>
                            @endif
                            @if ($s->session_date->isToday())
                                <span class="rounded-full bg-indigo-500/20 border border-indigo-400/30 px-2.5 py-1 text-xs font-semibold text-indigo-300">Hôm nay</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-sm text-slate-400 flex flex-col items-center justify-center gap-2">
                        <svg class="w-8 h-8 text-sky-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Tuần này không có lịch dạy.</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Tiến độ các lớp --}}
    <div class="glass-card border border-white/10 shadow-xl overflow-hidden flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 border border-indigo-400/30 text-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(129,140,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <span>Tiến độ giảng dạy các lớp</span>
                </h2>
                <a href="{{ route('teacher.classes.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition duration-200">
                    <span>Tất cả lớp</span>
                    <span class="group-hover:translate-x-1 transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-white/5 max-h-[460px] overflow-y-auto pr-1 pb-2 scroll-smooth">
                @forelse ($classes as $c)
                    @php
                    $pct = $c->total_sessions > 0 ? min(100, round($c->done_sessions_count / $c->total_sessions * 100)) : 0;
                    @endphp
                    <li class="group px-6 py-4 hover:bg-white/5 hover:translate-x-2 rounded-xl transition-all duration-300 cursor-pointer"
                        onclick="window.location='{{ route('teacher.classes.show', $c) }}'">
                        <div class="flex items-center justify-between text-sm">
                            <a href="{{ route('teacher.classes.show', $c) }}" class="font-bold text-slate-100 group-hover:text-indigo-300 transition duration-200 flex items-center gap-1.5">
                                <span>{{ $c->class_name }}</span>
                                <span class="opacity-0 group-hover:opacity-100 text-indigo-400 text-xs transition duration-200">↗</span>
                            </a>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-white/10 text-slate-200 border border-white/10">{{ $c->done_sessions_count }}/{{ $c->total_sessions }} buổi · {{ $c->enrollments_count }} SV</span>
                        </div>
                        <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-slate-800/80 border border-white/5">
                            <div class="h-full rounded-full transition-all duration-700 shadow-sm {{ $pct >= 80 ? 'bg-gradient-to-r from-emerald-400 to-teal-500 shadow-emerald-500/50' : ($pct >= 50 ? 'bg-gradient-to-r from-amber-400 to-orange-500 shadow-amber-500/50' : 'bg-gradient-to-r from-indigo-500 to-purple-500') }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-slate-400 group-hover:text-slate-300">Hoàn thành khung đào tạo</span>
                            <span class="font-black {{ $pct >= 80 ? 'text-emerald-400' : ($pct >= 50 ? 'text-amber-400' : 'text-indigo-300') }}">{{ $pct }}%</span>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-sm text-slate-400 flex flex-col items-center justify-center gap-2">
                        <svg class="w-8 h-8 text-indigo-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Bạn chưa được phân công lớp nào.</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

{{-- ===== ROW 4: Thảo luận + Thông báo ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    {{-- Thảo luận gần đây --}}
    <div class="glass-card border border-white/10 shadow-xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
            <h2 class="font-bold text-white flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/20 border border-cyan-400/30 text-cyan-400 shadow-[0_0_10px_rgba(6,182,212,0.3)]">
                    <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(6,182,212,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <span>Thảo luận hỏi bài gần đây</span>
            </h2>
            <a href="{{ route('teacher.assignments.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-cyan-400 hover:text-cyan-300 transition duration-200">
                <span>Bài tập</span>
                <span class="group-hover:translate-x-1 transition duration-200">→</span>
            </a>
        </div>
        <ul class="divide-y divide-white/5 max-h-72 overflow-y-auto pr-1">
            @forelse ($recent_discussions as $d)
                <li class="px-6 py-3.5 hover:bg-white/5 hover:translate-x-1.5 transition-all duration-200 cursor-pointer">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-500/20 border border-cyan-400/30 text-sm font-black text-cyan-300">{{ mb_substr($d->author?->full_name ?? '?', 0, 1) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-100 line-clamp-2">{{ $d->comment }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $d->author?->full_name }} · {{ $d->assignment?->title }} · {{ $d->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-slate-400">Chưa có thảo luận mới từ học sinh.</li>
            @endforelse
        </ul>
    </div>

    {{-- Thông báo --}}
    <div class="glass-card border border-white/10 shadow-xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
            <h2 class="font-bold text-white flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500/20 border border-orange-400/30 text-orange-400 shadow-[0_0_10px_rgba(249,115,22,0.3)]">
                    <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(249,115,22,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <span>Thông báo giảng viên</span>
            </h2>
            <a href="{{ route('teacher.notifications.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-orange-400 hover:text-orange-300 transition duration-200">
                <span>Tất cả</span>
                <span class="group-hover:translate-x-1 transition duration-200">→</span>
            </a>
        </div>
        <ul class="divide-y divide-white/5 max-h-72 overflow-y-auto pr-1">
            @forelse ($notifications as $n)
                <li class="px-6 py-3.5 hover:bg-white/5 hover:translate-x-1.5 transition-all duration-200 cursor-pointer"
                    onclick="window.location='{{ route('teacher.notifications.index') }}'">
                    <p class="text-sm font-bold text-slate-100 flex items-center gap-1.5">
                        <span>{{ $n->title }}</span>
                        @if ($n->isSystemWide())
                            <span class="ml-1 rounded-md bg-indigo-500/20 border border-indigo-400/30 px-2 py-0.5 text-[10px] font-semibold text-indigo-300">Toàn trường</span>
                        @else
                            <span class="ml-1 rounded-md bg-emerald-500/20 border border-emerald-400/30 px-2 py-0.5 text-[10px] font-semibold text-emerald-300">{{ $n->classRoom?->class_name }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-slate-400">Không có thông báo mới.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Bar Chart: Phân bố điểm ===
    const scoreDist = @json($score_distribution);
    new Chart(document.getElementById('scoreDistChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(scoreDist),
            datasets: [{
                label: 'Số lượt',
                data: Object.values(scoreDist),
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e', '#6366f1'],
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#0f172a',
                    borderColor: 'rgba(99, 102, 241, 0.4)',
                    borderWidth: 1,
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    padding: 10, 
                    cornerRadius: 8 
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(255, 255, 255, 0.08)' }, 
                    ticks: { stepSize: 1, color: '#94a3b8' } 
                },
                x: { 
                    grid: { display: false },
                    ticks: { color: '#cbd5e1' }
                }
            }
        }
    });

    // === Doughnut: Tỷ lệ nộp bài ===
    const subRate = {{ $submission_rate ?? 0 }};
    new Chart(document.getElementById('submissionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Đã nộp', 'Chưa nộp'],
            datasets: [{
                data: [subRate, Math.max(0, 100 - subRate)],
                backgroundColor: ['#10b981', 'rgba(255, 255, 255, 0.1)'],
                borderWidth: 2,
                borderColor: '#0f172a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { padding: 15, usePointStyle: true, pointStyle: 'circle', color: '#cbd5e1' } 
                },
                tooltip: { 
                    backgroundColor: '#0f172a', 
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 10, 
                    cornerRadius: 8 
                }
            },
            cutout: '70%'
        }
    });
});
</script>
@endpush