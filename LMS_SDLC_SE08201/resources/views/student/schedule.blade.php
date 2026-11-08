@extends('layouts.app')

@section('title', 'Thời khóa biểu')

@section('content')
@php
    $dayLabels = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
    $currentWeekStart = \Illuminate\Support\Carbon::parse($selectedWeekStart);
    $prevWeekStr = $currentWeekStart->copy()->subWeek()->format('Y-m-d');
    $nextWeekStr = $currentWeekStart->copy()->addWeek()->format('Y-m-d');
    $thisWeekStr = \Illuminate\Support\Carbon::now()->startOfWeek()->format('Y-m-d');
@endphp

<div class="space-y-6">
    {{-- ===== THANH ĐIỀU HƯỚNG TUẦN HỌC & BỘ LỌC NĂM ===== --}}
    <div class="glass-card p-5 border border-white/10 shadow-2xl rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 border border-indigo-400/30 text-indigo-400 shadow-[0_0_15px_rgba(99,102,241,0.3)]">
                    <svg class="w-5 h-5 filter drop-shadow-[0_0_6px_rgba(129,140,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-wide">Thời khóa biểu sinh viên</h1>
                    <p class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                        <span>Tuần: <strong class="text-slate-200">{{ \Illuminate\Support\Carbon::parse($selectedWeekStart)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($selectedWeekStart)->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->format('d/m/Y') }}</strong></span>
                        @if ($isCurrentWeek)
                            <span class="rounded-full bg-indigo-500/20 border border-indigo-400/40 px-2.5 py-0.5 text-[11px] font-bold text-indigo-300 shadow-[0_0_10px_rgba(99,102,241,0.4)]">
                                Tuần hiện tại
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('student.schedule.index') }}" class="flex flex-wrap items-center gap-2" id="schedule-filter-form">
                <select name="year" onchange="document.getElementById('schedule-filter-form').submit()" class="rounded-xl border border-white/10 bg-slate-800/90 px-3.5 py-2 text-xs font-semibold text-slate-200 shadow-inner focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                    @endforeach
                </select>
                
                <select name="week_start" onchange="document.getElementById('schedule-filter-form').submit()" class="rounded-xl border border-white/10 bg-slate-800/90 px-3.5 py-2 text-xs font-semibold text-slate-200 shadow-inner focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                    @foreach($weeksOfYear as $w)
                        <option value="{{ $w['week_start'] }}" {{ $selectedWeekStart == $w['week_start'] ? 'selected' : '' }}>
                            {{ $w['label'] }}
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('student.schedule.index') }}" class="rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 px-3.5 py-2 text-xs font-semibold text-slate-200 transition-all shadow-sm">
                    Làm mới
                </a>
            </form>

            <a href="{{ route('student.attendance.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-400/30 bg-indigo-600/20 px-3.5 py-2 text-xs font-bold text-indigo-300 hover:bg-indigo-600/30 hover:border-indigo-400/50 transition-all shadow-sm shadow-indigo-500/20">
                <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Xem lịch sử chuyên cần →</span>
            </a>
        </div>
    </div>

    {{-- ===== THANH CHÚ THÍCH TRẠNG THÁI ĐIỂM DANH (ATTENDANCE LEGEND) ===== --}}
    <div class="glass-card p-3.5 border border-white/10 shadow-lg rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Trạng thái điểm danh:</span>
            </span>

            {{-- Có mặt --}}
            <div class="flex items-center gap-1.5 font-bold text-emerald-300">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-md bg-emerald-500/20 border border-emerald-400/40 text-[10px] text-emerald-300 shadow-[0_0_8px_rgba(52,211,153,0.6)]">✓</span>
                <span>Có mặt (Đúng giờ)</span>
            </div>

            {{-- Đi muộn --}}
            <div class="flex items-center gap-1.5 font-bold text-amber-300">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-md bg-amber-500/20 border border-amber-400/40 text-[10px] text-amber-300 shadow-[0_0_8px_rgba(251,191,36,0.6)]">⏰</span>
                <span>Đi muộn</span>
            </div>

            {{-- Vắng mặt --}}
            <div class="flex items-center gap-1.5 font-bold text-rose-300">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-md bg-rose-500/20 border border-rose-400/40 text-[10px] text-rose-300 shadow-[0_0_8px_rgba(244,63,94,0.6)]">✗</span>
                <span>Vắng mặt (Không điểm danh)</span>
            </div>

            {{-- Ca học hôm nay --}}
            <div class="flex items-center gap-1.5 font-bold text-indigo-300">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
                </span>
                <span>Buổi học hôm nay</span>
            </div>

            {{-- Sắp diễn ra / Chưa điểm danh --}}
            <div class="flex items-center gap-1.5 font-medium text-slate-400">
                <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                <span>Sắp diễn ra / Chưa điểm danh</span>
            </div>
        </div>

        <div class="text-[11px] text-slate-400 hidden lg:block">
            * Bấm vào từng buổi học để mở chi tiết chuyên cần lớp đó.
        </div>
    </div>

    {{-- ===== MA TRẬN THỜI KHÓA BIỂU DẠNG LƯỚI (TIMETABLE GRID) ===== --}}
    <div class="glass-card border border-white/10 shadow-2xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="w-28 border-r border-white/10 bg-slate-900/90 p-3.5 text-center align-middle">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-300">Ca học</span>
                        </th>
                        @for ($i = 1; $i <= 7; $i++)
                            @php
                                $date = \Illuminate\Support\Carbon::parse($selectedWeekStart)->addDays($i - 1);
                                $isToday = $date->isToday();
                            @endphp
                            <th class="p-3 text-center transition-all {{ $isToday ? 'bg-indigo-600/30 border-b-2 border-b-indigo-400 shadow-[inset_0_0_15px_rgba(99,102,241,0.2)]' : 'bg-slate-900/70' }} {{ $i < 7 ? 'border-r border-white/10' : '' }}">
                                <div class="flex flex-col items-center justify-center">
                                    <p class="text-sm font-extrabold {{ $isToday ? 'text-indigo-300' : 'text-slate-200' }}">
                                        {{ $dayLabels[$i] }}
                                    </p>
                                    <p class="text-xs font-mono {{ $isToday ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                                        {{ $date->format('d/m') }}
                                    </p>
                                    @if ($isToday)
                                        <span class="mt-1 rounded-full bg-indigo-500/30 border border-indigo-400/40 px-2 py-0.2 text-[9.5px] font-bold text-indigo-200">
                                            Hôm nay
                                        </span>
                                    @endif
                                </div>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach ($slots as $slot)
                        <tr class="hover:bg-white/[0.01] transition-colors">
                            {{-- Cột hiển thị Ca học bên trái --}}
                            <td class="border-r border-white/10 bg-slate-900/80 p-3 text-center align-middle">
                                <p class="text-xs font-black text-slate-200">{{ $slot->label ?? ('Ca '.$slot->slot_number) }}</p>
                                <p class="text-[10.5px] font-mono text-indigo-400 font-semibold mt-0.5">
                                    {{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }} – {{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }}
                                </p>
                            </td>

                            {{-- 7 Cột tương ứng 7 ngày trong tuần --}}
                            @for ($i = 1; $i <= 7; $i++)
                                @php
                                    $date = \Illuminate\Support\Carbon::parse($selectedWeekStart)->addDays($i - 1);
                                    $sessions = $sessionGrid[$i] ?? collect();
                                    $s = $sessions->firstWhere('slot_id', $slot->slot_id);
                                    $isToday = $date->isToday();
                                @endphp
                                
                                <td class="p-2 align-top {{ $i < 7 ? 'border-r border-white/10' : '' }} {{ $isToday ? 'bg-indigo-950/10' : '' }}">
                                    @if ($s)
                                        @php
                                            $attendance = $s->attendances->first();
                                            $status = $attendance?->status; // P, L, A hoặc null
                                            $sessionDate = $s->session_date;
                                            $isSessToday = $sessionDate->isToday();
                                            $isSessPast = $sessionDate->isPast() && !$isSessToday;
                                            $isSessFuture = $sessionDate->isFuture();

                                            // Phong cách card theo trạng thái điểm danh
                                            if ($status === 'P') {
                                                // Có mặt
                                                $cardBorder = 'border-emerald-500/40 hover:border-emerald-400';
                                                $cardBg = 'bg-gradient-to-br from-emerald-950/50 via-slate-900/85 to-slate-900/95';
                                                $glowShadow = 'shadow-[0_4px_16px_rgba(16,185,129,0.15)]';
                                                $accentTop = 'border-t-2 border-t-emerald-400';
                                            } elseif ($status === 'L') {
                                                // Đi muộn
                                                $cardBorder = 'border-amber-500/40 hover:border-amber-400';
                                                $cardBg = 'bg-gradient-to-br from-amber-950/50 via-slate-900/85 to-slate-900/95';
                                                $glowShadow = 'shadow-[0_4px_16px_rgba(245,158,11,0.15)]';
                                                $accentTop = 'border-t-2 border-t-amber-400';
                                            } elseif ($status === 'A') {
                                                // Vắng mặt (Không điểm danh)
                                                $cardBorder = 'border-rose-500/40 hover:border-rose-400';
                                                $cardBg = 'bg-gradient-to-br from-rose-950/50 via-slate-900/85 to-slate-900/95';
                                                $glowShadow = 'shadow-[0_4px_16px_rgba(244,63,94,0.15)]';
                                                $accentTop = 'border-t-2 border-t-rose-400';
                                            } else {
                                                if ($isSessToday) {
                                                    // Buổi học hôm nay đang chờ điểm danh
                                                    $cardBorder = 'border-indigo-500/50 hover:border-indigo-400';
                                                    $cardBg = 'bg-gradient-to-br from-indigo-950/60 via-slate-900/85 to-slate-900/95 ring-1 ring-indigo-400/30';
                                                    $glowShadow = 'shadow-[0_0_20px_rgba(99,102,241,0.25)]';
                                                    $accentTop = 'border-t-2 border-t-indigo-400';
                                                } else {
                                                    // Buổi tương lai hoặc quá khứ chưa điểm danh
                                                    $cardBorder = 'border-white/10 hover:border-white/20';
                                                    $cardBg = 'bg-slate-900/75';
                                                    $glowShadow = 'shadow-md';
                                                    $accentTop = 'border-t-2 border-t-slate-600/40';
                                                }
                                            }
                                        @endphp

                                        {{-- Ô Buổi học với hiệu ứng nảy 3D lò xo (Spring pop-out) khi rê chuột --}}
                                        <div onclick="window.location='{{ route('student.attendance.index', ['class_id' => $s->class_id]) }}'"
                                             title="Bấm để xem lịch sử điểm danh môn này"
                                             class="group relative flex h-full min-h-[145px] flex-col justify-between rounded-xl border {{ $cardBorder }} {{ $cardBg }} {{ $glowShadow }} {{ $accentTop }} p-3 transition-all duration-300 hover:-translate-y-2 hover:scale-[1.03] hover:shadow-2xl cursor-pointer">
                                            
                                            {{-- Phần trên: Thông tin Lớp học & Môn học --}}
                                            <div>
                                                <div class="flex items-start justify-between gap-1.5">
                                                    <p class="line-clamp-2 text-xs font-black text-white group-hover:text-indigo-300 transition duration-200" title="{{ $s->classRoom?->class_name }}">
                                                        {{ $s->classRoom?->class_name }}
                                                    </p>
                                                    <span class="opacity-0 group-hover:opacity-100 text-indigo-400 text-xs transition duration-200">↗</span>
                                                </div>

                                                <p class="mt-1 line-clamp-2 text-[11px] font-semibold text-slate-300 leading-tight" title="{{ $s->classRoom?->subject?->subject_name }}">
                                                    {{ $s->classRoom?->subject?->subject_name }}
                                                </p>
                                            </div>

                                            {{-- Phần giữa: Phòng học & Giảng viên với Icon SVG siêu thực --}}
                                            <div class="mt-2 space-y-1 text-[10.5px]">
                                                {{-- Phòng học --}}
                                                <div class="flex items-center gap-1.5 text-slate-300 truncate">
                                                    <svg class="h-3.5 w-3.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    <span class="truncate">Phòng: <strong class="text-white font-bold">{{ $s->room?->room_name ?? '—' }}</strong></span>
                                                </div>

                                                {{-- Giảng viên nếu có --}}
                                                @if($s->classRoom?->teacher)
                                                    <div class="flex items-center gap-1.5 text-slate-400 truncate">
                                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <span class="truncate">{{ $s->classRoom->teacher->full_name }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- ===== PHẦN DƯỚI: HIỂN THỊ RÕ RÀNG TRẠNG THÁI ĐIỂM DANH THEO MỖI HỌC SINH ===== --}}
                                            <div class="mt-2.5 pt-2 border-t border-white/10">
                                                @if ($status === 'P')
                                                    {{-- HỌC SINH CÓ MẶT (PRESENT) --}}
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-emerald-500/20 border border-emerald-400/40 px-2 py-1 text-emerald-300 shadow-sm">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="h-3.5 w-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            <span class="text-[11px] font-black tracking-wide">Có mặt</span>
                                                        </div>
                                                        @if($attendance?->attendance_time)
                                                            <span class="text-[9.5px] font-mono text-emerald-300/90 font-bold">
                                                                {{ \Illuminate\Support\Carbon::parse($attendance->attendance_time)->format('H:i') }}
                                                            </span>
                                                        @else
                                                            <span class="text-[9px] text-emerald-300/80 font-semibold">Đúng giờ</span>
                                                        @endif
                                                    </div>

                                                @elseif ($status === 'L')
                                                    {{-- HỌC SINH ĐI MUỘN (LATE) --}}
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-amber-500/20 border border-amber-400/40 px-2 py-1 text-amber-300 shadow-sm">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="h-3.5 w-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <span class="text-[11px] font-black tracking-wide">Đi muộn</span>
                                                        </div>
                                                        @if($attendance?->attendance_time)
                                                            <span class="text-[9.5px] font-mono text-amber-300/90 font-bold">
                                                                {{ \Illuminate\Support\Carbon::parse($attendance->attendance_time)->format('H:i') }}
                                                            </span>
                                                        @else
                                                            <span class="text-[9px] text-amber-300/80 font-semibold">Vào muộn</span>
                                                        @endif
                                                    </div>

                                                @elseif ($status === 'A')
                                                    {{-- HỌC SINH VẮNG MẶT / KHÔNG ĐIỂM DANH (ABSENT) --}}
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-rose-500/20 border border-rose-400/40 px-2 py-1 text-rose-300 shadow-sm">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="h-3.5 w-3.5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            <span class="text-[11px] font-black tracking-wide">Vắng mặt</span>
                                                        </div>
                                                        <span class="text-[9px] text-rose-300/80 font-bold">Không điểm danh</span>
                                                    </div>

                                                @else
                                                    {{-- CHƯA CÓ BẢN GHI ĐIỂM DANH --}}
                                                    @if ($isSessToday)
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-indigo-500/20 border border-indigo-400/40 px-2 py-1 text-indigo-300 shadow-sm">
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="relative flex h-2 w-2">
                                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                                                                </span>
                                                                <span class="text-[11px] font-extrabold">Hôm nay</span>
                                                            </div>
                                                            <span class="text-[9px] text-indigo-300/80 font-medium">Chờ điểm danh</span>
                                                        </div>
                                                    @elseif ($isSessPast)
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-white/5 border border-white/10 px-2 py-1 text-slate-400 shadow-sm">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <span class="text-[10.5px] font-medium">Chưa điểm danh</span>
                                                            </div>
                                                            <span class="text-[9px] text-slate-500">Đã qua</span>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-white/5 border border-white/10 px-2 py-1 text-slate-400 shadow-sm">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="h-3.5 w-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                <span class="text-[10.5px] font-medium">Sắp diễn ra</span>
                                                            </div>
                                                            <span class="text-[9px] text-slate-500">Chưa bắt đầu</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        {{-- Không có ca học --}}
                                        <div class="flex h-full min-h-[145px] items-center justify-center rounded-xl border border-dashed border-white/5 bg-white/[0.01]">
                                            <span class="text-xs text-slate-600">—</span>
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                    
                    @if($slots->isEmpty())
                        <tr>
                            <td colspan="8" class="p-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-sky-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Không có dữ liệu ca học trong hệ thống</span>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== CÁC NÚT ĐIỀU HƯỚNG TUẦN (PREV / CURRENT / NEXT) ===== --}}
    <div class="flex flex-wrap items-center justify-center gap-4">
        <a href="{{ route('student.schedule.index', ['week_start' => $prevWeekStr, 'year' => $selectedYear]) }}" 
           class="rounded-xl border border-white/10 bg-slate-900/80 px-5 py-2.5 text-xs font-bold text-slate-200 shadow-md transition-all hover:bg-white/10 hover:border-white/20 hover:-translate-x-1">
            ← Tuần trước
        </a>
        <a href="{{ route('student.schedule.index', ['week_start' => $thisWeekStr, 'year' => \Illuminate\Support\Carbon::now()->year]) }}" 
           class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-2.5 text-xs font-black text-white shadow-lg shadow-indigo-500/30 transition-all hover:from-indigo-500 hover:to-purple-500 hover:scale-105">
            Tuần hiện tại
        </a>
        <a href="{{ route('student.schedule.index', ['week_start' => $nextWeekStr, 'year' => $selectedYear]) }}" 
           class="rounded-xl border border-white/10 bg-slate-900/80 px-5 py-2.5 text-xs font-bold text-slate-200 shadow-md transition-all hover:bg-white/10 hover:border-white/20 hover:translate-x-1">
            Tuần sau →
        </a>
    </div>
</div>
@endsection
