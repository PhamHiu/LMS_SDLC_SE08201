@extends('layouts.app')

@section('title', 'Dashboard sinh viên')

@section('content')
{{-- ===== KPI CARDS (4 cards) ===== --}}
<div class="grid gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
    {{-- Card 1: Lớp đang học --}}
    <div class="group relative rounded-lg bg-white p-5 shadow-sm border border-slate-200 transition-all duration-300 transform cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('student.schedule.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-slate-50 rounded-full hidden transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Lớp đang học</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-md bg-slate-50 border border-slate-200 text-slate-900 shadow-sm transition duration-300">
                    <svg class="w-6 h-6 text-sky-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-bold tracking-tight text-slate-900">{{ $enrolledCount }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-blue-700">
            <span class="font-medium group-hover:text-[#163c6d]">Xem thời khóa biểu</span>
            <span class="inline-flex items-center gap-1 font-bold text-sky-700 transition duration-200">
                Chi tiết →
            </span>
        </div>
    </div>

    {{-- Card 2: Điểm trung bình --}}
    <div class="group relative rounded-lg bg-white p-5 shadow-sm border border-slate-200 transition-all duration-300 transform cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('student.grades.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-slate-50 rounded-full hidden transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Điểm trung bình</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-md bg-slate-50 border border-slate-200 text-slate-900 shadow-sm transition duration-300">
                    <svg class="w-6 h-6 text-emerald-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-bold tracking-tight text-slate-900">{{ $avgScore ?: '—' }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-emerald-700">
            <span class="font-medium group-hover:text-[#163c6d]">Xem bảng điểm</span>
            <span class="inline-flex items-center gap-1 font-bold text-emerald-700 transition duration-200">
                Chi tiết →
            </span>
        </div>
    </div>

    {{-- Card 3: Chuyên cần --}}
    <div class="group relative rounded-lg bg-white p-5 shadow-sm border border-slate-200 transition-all duration-300 transform cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('student.attendance.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-slate-50 rounded-full hidden transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Chuyên cần</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-md bg-slate-50 border border-slate-200 text-slate-900 shadow-sm transition duration-300">
                    <svg class="w-6 h-6 text-amber-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-bold tracking-tight text-slate-900">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-amber-700">
            <span class="font-medium group-hover:text-[#163c6d]">Lịch sử điểm danh</span>
            <span class="inline-flex items-center gap-1 font-bold text-amber-700 transition duration-200">
                Chi tiết →
            </span>
        </div>
    </div>

    {{-- Card 4: Bài tập chưa nộp --}}
    <div class="group relative rounded-lg bg-white p-5 shadow-sm border border-slate-200 transition-all duration-300 transform cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('student.assignments.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-slate-50 rounded-full hidden transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-rose-700">Bài tập chưa nộp</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-md bg-slate-50 border border-slate-200 text-slate-900 shadow-sm transition duration-300">
                    <svg class="w-6 h-6 text-pink-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>
            <p class="mt-2 text-4xl font-bold tracking-tight text-slate-900">{{ $pending_count }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-rose-700">
            <span class="font-medium group-hover:text-[#163c6d]">Xem hạn nộp bài</span>
            <span class="inline-flex items-center gap-1 font-bold text-rose-700 transition duration-200">
                Chi tiết →
            </span>
        </div>
    </div>
</div>

{{-- ===== ROW 2: 2 Biểu đồ ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Line Chart: Trend điểm số --}}
    <div class="w-full glass-card p-6 border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-900 flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 border border-blue-200 text-blue-600">
                    <svg class="w-4 h-4 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                </div>
                <span>Lịch sử điểm số</span>
            </h2>
            <a href="{{ route('student.grades.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 transition duration-200">
                <span>Xem chi tiết</span>
                <span class="transition duration-200">→</span>
            </a>
        </div>
        <div class="relative w-full" style="height:260px">
            <canvas id="scoreTrendChart"></canvas>
        </div>
    </div>

    {{-- Doughnut: Chuyên cần cá nhân --}}
    <div class="w-full glass-card p-6 border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-900 flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600">
                    <svg class="w-4 h-4 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
                <span>Chuyên cần cá nhân</span>
            </h2>
            <a href="{{ route('student.attendance.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition duration-200">
                <span>Điểm danh</span>
                <span class="transition duration-200">→</span>
            </a>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <div class="w-full max-w-[200px] mx-auto sm:mx-0 sm:w-48 shrink-0 aspect-square relative">
                <canvas id="attendanceChart"></canvas>
            </div>
            <div class="flex-1 w-full space-y-3">
                <div class="flex items-center gap-3 p-3 rounded-md bg-emerald-50 border border-emerald-200 transition duration-200">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white text-sm font-semibold shadow-sm">P</span>
                    <div>
                        <p class="text-xs text-slate-600">Có mặt</p>
                        <p class="text-xl font-bold text-emerald-600">{{ $attendance_breakdown['present'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-md bg-rose-50 border border-rose-200 transition duration-200">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500 text-white text-sm font-semibold shadow-sm">A</span>
                    <div>
                        <p class="text-xs text-slate-600">Vắng mặt</p>
                        <p class="text-xl font-bold text-rose-600">{{ $attendance_breakdown['absent'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-md bg-amber-50 border border-amber-200 transition duration-200">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-600 text-white text-sm font-semibold shadow-sm">L</span>
                    <div>
                        <p class="text-xs text-slate-600">Đi muộn</p>
                        <p class="text-xl font-bold text-amber-600">{{ $attendance_breakdown['late'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 3: Bài tập sắp hạn + Lịch học ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Bài tập sắp đến hạn --}}
    <div class="glass-card border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="font-bold text-slate-900 flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 border border-rose-200 text-rose-600">
                        <svg class="w-4 h-4 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span>Bài tập sắp đến hạn</span>
                </h2>
                <a href="{{ route('student.assignments.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 transition duration-200">
                    <span>Xem tất cả</span>
                    <span class="transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-slate-200 max-h-[380px] overflow-y-auto pr-1">
                @forelse ($upcomingAssignments as $a)
                    <li class="flex items-center justify-between gap-3 px-6 py-3.5 hover:bg-slate-100 transition-all duration-200 cursor-pointer"
                        onclick="window.location='{{ route('student.assignments.show', $a) }}'">
                        <div class="min-w-0">
                            <a href="{{ route('student.assignments.show', $a) }}" class="block truncate text-sm font-bold text-slate-800 hover:text-rose-700 transition">{{ $a->title }}</a>
                            <p class="text-xs text-slate-500">{{ $a->classRoom?->class_name }} · {{ $a->typeLabel() }}</p>
                        </div>
                        @php
                        $hoursLeft = now()->diffInHours($a->due_date, false);
                        @endphp
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold border {{ $hoursLeft < 24 ? 'bg-rose-50 border-rose-200 text-rose-700 ' : ($hoursLeft < 48 ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-slate-50 border-slate-200 text-slate-600') }}">
                            {{ $a->due_date->diffForHumans() }}
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500 flex flex-col items-center justify-center gap-2">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Không có bài tập nào chờ nộp.</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Lịch học tuần này --}}
    <div class="glass-card border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="font-bold text-slate-900 flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 border border-sky-200 text-sky-600">
                        <svg class="w-4 h-4 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span>Lịch học tuần này</span>
                </h2>
                <a href="{{ route('student.schedule.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-sky-600 hover:text-sky-700 transition duration-200">
                    <span>Xem TKB</span>
                    <span class="transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-slate-200 max-h-[380px] overflow-y-auto pr-1">
                @forelse ($weekSessions as $s)
                    <li class="flex items-center gap-4 px-6 py-3.5 hover:bg-slate-100 transition-all duration-200 cursor-pointer"
                        onclick="window.location='{{ route('student.schedule.index') }}'">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-md {{ $s->session_date->isToday() ? 'bg-[#1e4f8f] text-white ' : 'bg-slate-50 text-blue-700 border border-slate-200' }}">
                            <span class="text-[10px] uppercase font-bold tracking-wider">{{ $s->session_date->isoFormat('dd') }}</span>
                            <span class="text-sm font-bold">{{ $s->session_date->format('d') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $s->classRoom?->class_name }} — {{ $s->classRoom?->subject?->subject_name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                @if ($s->slot) {{ $s->slot->label }} @endif
                                @if ($s->room) · {{ $s->room->room_name }} @endif
                            </p>
                        </div>
                        @php
                            $att = $s->attendances?->first();
                            $attStatus = $att?->status;
                        @endphp
                        <div class="flex items-center gap-2">
                            @if ($attStatus === 'P')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-bold text-emerald-700 shadow-sm">
                                    <svg class="h-3 w-3 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Có mặt</span>
                                </span>
                            @elseif ($attStatus === 'L')
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-bold text-amber-700 shadow-sm">
                                    <svg class="h-3 w-3 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Đi muộn</span>
                                </span>
                            @elseif ($attStatus === 'A')
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2.5 py-1 text-xs font-bold text-rose-700 shadow-sm">
                                    <svg class="h-3 w-3 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Vắng mặt</span>
                                </span>
                            @else
                                @if ($s->session_date->isToday())
                                    <span class="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-semibold text-blue-700">Hôm nay</span>
                                @else
                                    <span class="rounded-full bg-slate-50 border border-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-500">Sắp diễn ra</span>
                                @endif
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500 flex flex-col items-center justify-center gap-2">
                        <svg class="w-8 h-8 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Tuần này không có lịch học.</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

{{-- ===== ROW 4: Tiến trình bài tập + Kết quả & Thông báo ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    {{-- Tiến trình hoàn thành bài tập (Hiển thị đầy đủ mọi lớp, không bao giờ bị cắt chữ) --}}
    <div class="glass-card border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 border border-blue-200 text-blue-600">
                    <svg class="w-5 h-5 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900 text-base tracking-wide">Tiến trình hoàn thành bài tập</h2>
                    <p class="text-[11px] text-slate-500">Theo từng môn đang học</p>
                </div>
            </div>
            <a href="{{ route('student.assignments.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 transition duration-200">
                <span>Xem bài tập</span>
                <span class="transition duration-200">→</span>
            </a>
        </div>
        <ul class="divide-y divide-slate-200 max-h-[560px] overflow-y-auto pr-1 pb-3 scroll-smooth">
            @forelse ($assignment_progress as $ap)
                <li class="group px-6 py-4 hover:bg-slate-100 rounded-md transition-all duration-300 cursor-pointer"
                    onclick="window.location='{{ route('student.assignments.index') }}'">
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-bold text-slate-800 group-hover:text-blue-700 transition duration-200 flex items-center gap-1.5">
                                <span>{{ $ap['class_name'] }}</span>
                                <span class="opacity-0 group-hover:opacity-100 text-blue-600 text-xs transition duration-200">↗</span>
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $ap['subject_name'] }}</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-slate-200">{{ $ap['done'] }}/{{ $ap['total'] }} bài</span>
                    </div>
                    <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-slate-50 border border-slate-200">
                        <div class="h-full rounded-full transition-all duration-700 shadow-sm {{ $ap['percent'] >= 80 ? ' bg-emerald-600 ' : ($ap['percent'] >= 50 ? ' bg-amber-600 ' : ' bg-rose-400 ') }}" style="width: {{ $ap['percent'] }}%"></div>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-500 group-hover:text-slate-600">Hoàn thành</span>
                        <span class="font-bold {{ $ap['percent'] >= 80 ? 'text-emerald-600' : ($ap['percent'] >= 50 ? 'text-amber-600' : 'text-rose-600') }}">{{ $ap['percent'] }}%</span>
                    </div>
                </li>
            @empty
                <li class="px-6 py-12 text-center text-sm text-slate-500 flex flex-col items-center justify-center gap-2">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Chưa có dữ liệu tiến trình.</span>
                </li>
            @endforelse
        </ul>
        <div class="border-t border-slate-200 p-3 bg-white/[0.02] flex items-center justify-between text-xs text-slate-500">
            <span>Tổng cộng {{ count($assignment_progress) }} môn học</span>
            <a href="{{ route('student.assignments.index') }}" class="font-semibold text-blue-600 hover:text-blue-700 transition">Xem tất cả bài tập →</a>
        </div>
    </div>

    {{-- Kết quả + Thông báo --}}
    <div class="space-y-6">
        {{-- Kết quả gần đây --}}
        <div class="glass-card border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="font-bold text-slate-900 flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 border border-amber-200 text-amber-600">
                        <svg class="w-4 h-4 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <span>Kết quả gần đây</span>
                </h2>
                <a href="{{ route('student.grades.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-700 transition duration-200">
                    <span>Bảng điểm</span>
                    <span class="transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-slate-200">
                @forelse ($recentGrades as $g)
                    <li class="flex items-center justify-between gap-3 px-6 py-3.5 hover:bg-slate-100 transition-all duration-200 cursor-pointer"
                        onclick="window.location='{{ route('student.grades.index') }}'">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $g->assignment?->title }}</p>
                            <p class="text-xs text-slate-500">{{ $g->assignment?->classRoom?->class_name }}</p>
                        </div>
                        @php
                        $scorePercent = $g->assignment?->max_score > 0 ? $g->score / $g->assignment->max_score * 100 : 0;
                        @endphp
                        <span class="rounded-full px-3 py-1 text-xs font-bold border {{ $scorePercent >= 80 ? 'bg-emerald-50 border-emerald-200 text-emerald-700 ' : ($scorePercent >= 50 ? 'bg-amber-50 border-amber-200 text-amber-700 ' : 'bg-rose-50 border-rose-200 text-rose-700 ') }}">
                            {{ $g->score }}/{{ $g->assignment?->max_score }}
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-6 text-center text-sm text-slate-500">Chưa có kết quả nào.</li>
                @endforelse
            </ul>
        </div>

        {{-- Thông báo --}}
        <div class="glass-card border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="font-bold text-slate-900 flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 border border-sky-200 text-sky-600">
                        <svg class="w-4 h-4 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <span>Thông báo</span>
                </h2>
                <a href="{{ route('student.notifications.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-sky-600 hover:text-sky-700 transition duration-200">
                    <span>Tất cả</span>
                    <span class="transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-slate-200 max-h-56 overflow-y-auto pr-1">
                @forelse ($notifications as $n)
                    <li class="px-6 py-3.5 hover:bg-slate-100 transition-all duration-200 cursor-pointer"
                        onclick="window.location='{{ route('student.notifications.index') }}'">
                        <p class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <span>{{ $n->title }}</span>
                            @if ($n->isSystemWide())
                                <span class="ml-1 rounded-md bg-blue-50 border border-blue-200 px-2 py-0.5 text-[10px] font-semibold text-blue-700">Toàn trường</span>
                            @else
                                <span class="ml-1 rounded-md bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">{{ $n->classRoom?->class_name }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-500 mt-1">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-6 py-6 text-center text-sm text-slate-500">Không có thông báo mới.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@include('shared.deadline-countdown')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Line Chart: Trend điểm số ===
    const scoreData = @json($score_history);
    new Chart(document.getElementById('scoreTrendChart'), {
        type: 'line',
        data: {
            labels: scoreData.map(d => d.date),
            datasets: [{
                label: 'Điểm',
                data: scoreData.map(d => d.score),
                borderColor: '#1e4f8f',
                backgroundColor: 'rgba(30, 79, 143, 0.15)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: scoreData.map(d => {
                    const pct = d.max_score > 0 ? d.score / d.max_score * 100 : 0;
                    return pct >= 80 ? '#10b981' : pct >= 50 ? '#f59e0b' : '#ef4444';
                }),
                pointBorderColor: '#ffffff',
                pointBorderWidth: 1.5,
                pointRadius: 6,
                pointHoverRadius: 8,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    borderColor: 'rgba(30, 79, 143, 0.4)',
                    borderWidth: 1,
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        title: (items) => scoreData[items[0].dataIndex]?.title || '',
                        label: (item) => {
                            const d = scoreData[item.dataIndex];
                            return ` Điểm: ${d.score}/${d.max_score}`;
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: 10, 
                    grid: { color: 'rgba(15, 23, 42, 0.08)' },
                    ticks: { color: '#6b7280' }
                },
                x: { 
                    grid: { display: false }, 
                    ticks: { maxRotation: 45, color: '#6b7280' } 
                }
            }
        }
    });

    // === Doughnut: Chuyên cần ===
    const att = @json($attendance_breakdown);
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Có mặt', 'Vắng', 'Muộn'],
            datasets: [{
                data: [att.present, att.absent, att.late],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 2,
                borderColor: '#0f172a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#0f172a',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    padding: 10, 
                    cornerRadius: 8 
                }
            },
            cutout: '68%'
        }
    });
});
</script>
@endpush