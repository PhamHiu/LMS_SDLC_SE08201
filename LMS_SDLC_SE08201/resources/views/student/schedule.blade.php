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
    <div class="glass-card p-5 border border-slate-200 shadow-sm rounded-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-blue-50 border border-blue-200 text-blue-600">
                    <svg class="w-5 h-5 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-wide">Thời khóa biểu sinh viên</h1>
                    <p class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                        <span>Tuần: <strong class="text-slate-700">{{ \Illuminate\Support\Carbon::parse($selectedWeekStart)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($selectedWeekStart)->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->format('d/m/Y') }}</strong></span>
                        @if ($isCurrentWeek)
                            <span class="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-[11px] font-bold text-blue-700">
                                Tuần hiện tại
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('student.schedule.index') }}" class="flex flex-wrap items-center gap-2" id="schedule-filter-form">
                <select name="year" onchange="document.getElementById('schedule-filter-form').submit()" class="rounded-md border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-1 focus:ring-[#1e4f8f] cursor-pointer">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                    @endforeach
                </select>
                
                <select name="week_start" onchange="document.getElementById('schedule-filter-form').submit()" class="rounded-md border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-1 focus:ring-[#1e4f8f] cursor-pointer">
                    @foreach($weeksOfYear as $w)
                        <option value="{{ $w['week_start'] }}" {{ $selectedWeekStart == $w['week_start'] ? 'selected' : '' }}>
                            {{ $w['label'] }}
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('student.schedule.index') }}" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3.5 py-2 text-xs font-semibold text-slate-700 transition-all shadow-sm">
                    Làm mới
                </a>
            </form>

            <a href="{{ route('student.attendance.index') }}" class="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100 hover:border-blue-200 transition-all shadow-sm">
                <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Xem lịch sử chuyên cần →</span>
            </a>
        </div>
    </div>

    {{-- ===== THANH CHÚ THÍCH TRẠNG THÁI ĐIỂM DANH (ATTENDANCE LEGEND) ===== --}}
    <div class="glass-card p-3.5 border border-slate-200 shadow-sm rounded-lg flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Trạng thái điểm danh:</span>
            </span>

            {{-- Có mặt --}}
            <div class="flex items-center gap-1.5 font-bold text-emerald-700">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 text-[10px] text-emerald-700">✓</span>
                <span>Có mặt (Đúng giờ)</span>
            </div>

            {{-- Đi muộn --}}
            <div class="flex items-center gap-1.5 font-bold text-amber-700">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-md bg-amber-50 border border-amber-200 text-[10px] text-amber-700">⏰</span>
                <span>Đi muộn</span>
            </div>

            {{-- Vắng mặt --}}
            <div class="flex items-center gap-1.5 font-bold text-rose-700">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-md bg-rose-50 border border-rose-200 text-[10px] text-rose-700">✗</span>
                <span>Vắng mặt (Không điểm danh)</span>
            </div>

            {{-- Ca học hôm nay --}}
            <div class="flex items-center gap-1.5 font-bold text-blue-700">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#1e4f8f]"></span>
                </span>
                <span>Buổi học hôm nay</span>
            </div>

            {{-- Sắp diễn ra / Chưa điểm danh --}}
            <div class="flex items-center gap-1.5 font-medium text-slate-500">
                <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                <span>Sắp diễn ra / Chưa điểm danh</span>
            </div>
        </div>

        <div class="text-[11px] text-slate-500 hidden lg:block">
            * Bấm vào từng buổi học để mở chi tiết chuyên cần lớp đó.
        </div>
    </div>

    {{-- ===== MA TRẬN THỜI KHÓA BIỂU DẠNG LƯỚI (TIMETABLE GRID) ===== --}}
    <div class="glass-card border border-slate-200 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="w-28 border-r border-slate-200 bg-slate-50 p-3.5 text-center align-middle">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Ca học</span>
                        </th>
                        @for ($i = 1; $i <= 7; $i++)
                            @php
                                $date = \Illuminate\Support\Carbon::parse($selectedWeekStart)->addDays($i - 1);
                                $isToday = $date->isToday();
                            @endphp
                            <th class="p-3 text-center transition-all {{ $isToday ? 'bg-blue-50 border-b-2 border-b-indigo-400 ' : 'bg-slate-50' }} {{ $i < 7 ? 'border-r border-slate-200' : '' }}">
                                <div class="flex flex-col items-center justify-center">
                                    <p class="text-sm font-semibold {{ $isToday ? 'text-blue-700' : 'text-slate-700' }}">
                                        {{ $dayLabels[$i] }}
                                    </p>
                                    <p class="text-xs font-mono {{ $isToday ? 'text-blue-600 font-bold' : 'text-slate-500' }}">
                                        {{ $date->format('d/m') }}
                                    </p>
                                    @if ($isToday)
                                        <span class="mt-1 rounded-full bg-blue-50 border border-blue-200 px-2 py-0.2 text-[9.5px] font-bold text-blue-700">
                                            Hôm nay
                                        </span>
                                    @endif
                                </div>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($slots as $slot)
                        <tr class="hover:bg-white/[0.01] transition-colors">
                            {{-- Cột hiển thị Ca học bên trái --}}
                            <td class="border-r border-slate-200 bg-slate-50 p-3 text-center align-middle">
                                <p class="text-xs font-bold text-slate-700">{{ $slot->label ?? ('Ca '.$slot->slot_number) }}</p>
                                <p class="text-[10.5px] font-mono text-blue-600 font-semibold mt-0.5">
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
                                
                                <td class="p-2 align-top {{ $i < 7 ? 'border-r border-slate-200' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}">
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
                                                $cardBorder = 'border-emerald-200 hover:border-emerald-400';
                                                $cardBg = 'bg-emerald-50';
                                                $glowShadow = '';
                                                $accentTop = 'border-t-2 border-t-emerald-400';
                                            } elseif ($status === 'L') {
                                                // Đi muộn
                                                $cardBorder = 'border-amber-200 hover:border-amber-400';
                                                $cardBg = 'bg-amber-50';
                                                $glowShadow = '';
                                                $accentTop = 'border-t-2 border-t-amber-400';
                                            } elseif ($status === 'A') {
                                                // Vắng mặt (Không điểm danh)
                                                $cardBorder = 'border-rose-200 hover:border-rose-400';
                                                $cardBg = 'bg-rose-50';
                                                $glowShadow = '';
                                                $accentTop = 'border-t-2 border-t-rose-400';
                                            } else {
                                                if ($isSessToday) {
                                                    // Buổi học hôm nay đang chờ điểm danh
                                                    $cardBorder = 'border-blue-200 hover:border-blue-400';
                                                    $cardBg = 'bg-blue-50 ring-1 ring-blue-400/30';
                                                    $glowShadow = '';
                                                    $accentTop = 'border-t-2 border-t-indigo-400';
                                                } else {
                                                    // Buổi tương lai hoặc quá khứ chưa điểm danh
                                                    $cardBorder = 'border-slate-200 hover:border-slate-200';
                                                    $cardBg = 'bg-slate-50';
                                                    $glowShadow = 'shadow-sm';
                                                    $accentTop = 'border-t-2 border-t-slate-600/40';
                                                }
                                            }
                                        @endphp

                                        {{-- Ô Buổi học với hiệu ứng nảy 3D lò xo (Spring pop-out) khi rê chuột --}}
                                        <div onclick="window.location='{{ route('student.attendance.index', ['class_id' => $s->class_id]) }}'"
                                             title="Bấm để xem lịch sử điểm danh môn này"
                                             class="group relative flex h-full min-h-[145px] flex-col justify-between rounded-md border {{ $cardBorder }} {{ $cardBg }} {{ $glowShadow }} {{ $accentTop }} p-3 transition-all duration-300 cursor-pointer">
                                            
                                            {{-- Phần trên: Thông tin Lớp học & Môn học --}}
                                            <div>
                                                <div class="flex items-start justify-between gap-1.5">
                                                    <p class="line-clamp-2 text-xs font-bold text-slate-900 group-hover:text-blue-700 transition duration-200" title="{{ $s->classRoom?->class_name }}">
                                                        {{ $s->classRoom?->class_name }}
                                                    </p>
                                                    <span class="opacity-0 group-hover:opacity-100 text-blue-600 text-xs transition duration-200">↗</span>
                                                </div>

                                                <p class="mt-1 line-clamp-2 text-[11px] font-semibold text-slate-600 leading-tight" title="{{ $s->classRoom?->subject?->subject_name }}">
                                                    {{ $s->classRoom?->subject?->subject_name }}
                                                </p>
                                            </div>

                                            {{-- Phần giữa: Phòng học & Giảng viên với Icon SVG siêu thực --}}
                                            <div class="mt-2 space-y-1 text-[10.5px]">
                                                {{-- Phòng học --}}
                                                <div class="flex items-center gap-1.5 text-slate-600 truncate">
                                                    <svg class="h-3.5 w-3.5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    <span class="truncate">Phòng: <strong class="text-slate-900 font-bold">{{ $s->room?->room_name ?? '—' }}</strong></span>
                                                </div>

                                                {{-- Giảng viên nếu có --}}
                                                @if($s->classRoom?->teacher)
                                                    <div class="flex items-center gap-1.5 text-slate-500 truncate">
                                                        <svg class="h-3.5 w-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <span class="truncate">{{ $s->classRoom->teacher->full_name }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- ===== PHẦN DƯỚI: HIỂN THỊ RÕ RÀNG TRẠNG THÁI ĐIỂM DANH THEO MỖI HỌC SINH ===== --}}
                                            <div class="mt-2.5 pt-2 border-t border-slate-200">
                                                @if ($status === 'P')
                                                    {{-- HỌC SINH CÓ MẶT (PRESENT) --}}
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-emerald-50 border border-emerald-200 px-2 py-1 text-emerald-700 shadow-sm">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            <span class="text-[11px] font-bold tracking-wide">Có mặt</span>
                                                        </div>
                                                        @if($attendance?->attendance_time)
                                                            <span class="text-[9.5px] font-mono text-emerald-700 font-bold">
                                                                {{ \Illuminate\Support\Carbon::parse($attendance->attendance_time)->format('H:i') }}
                                                            </span>
                                                        @else
                                                            <span class="text-[9px] text-emerald-700 font-semibold">Đúng giờ</span>
                                                        @endif
                                                    </div>

                                                @elseif ($status === 'L')
                                                    {{-- HỌC SINH ĐI MUỘN (LATE) --}}
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-amber-50 border border-amber-200 px-2 py-1 text-amber-700 shadow-sm">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="h-3.5 w-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <span class="text-[11px] font-bold tracking-wide">Đi muộn</span>
                                                        </div>
                                                        @if($attendance?->attendance_time)
                                                            <span class="text-[9.5px] font-mono text-amber-700 font-bold">
                                                                {{ \Illuminate\Support\Carbon::parse($attendance->attendance_time)->format('H:i') }}
                                                            </span>
                                                        @else
                                                            <span class="text-[9px] text-amber-700 font-semibold">Vào muộn</span>
                                                        @endif
                                                    </div>

                                                @elseif ($status === 'A')
                                                    {{-- HỌC SINH VẮNG MẶT / KHÔNG ĐIỂM DANH (ABSENT) --}}
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-rose-50 border border-rose-200 px-2 py-1 text-rose-700 shadow-sm">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="h-3.5 w-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            <span class="text-[11px] font-bold tracking-wide">Vắng mặt</span>
                                                        </div>
                                                        <span class="text-[9px] text-rose-700 font-bold">Không điểm danh</span>
                                                    </div>

                                                @else
                                                    {{-- CHƯA CÓ BẢN GHI ĐIỂM DANH --}}
                                                    @if ($isSessToday)
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-blue-50 border border-blue-200 px-2 py-1 text-blue-700 shadow-sm">
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="relative flex h-2 w-2">
                                                                    <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#1e4f8f]"></span>
                                                                </span>
                                                                <span class="text-[11px] font-semibold">Hôm nay</span>
                                                            </div>
                                                            <span class="text-[9px] text-blue-700 font-medium">Chờ điểm danh</span>
                                                        </div>
                                                    @elseif ($isSessPast)
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-slate-50 border border-slate-200 px-2 py-1 text-slate-500 shadow-sm">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="h-3.5 w-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <span class="text-[10.5px] font-medium">Chưa điểm danh</span>
                                                            </div>
                                                            <span class="text-[9px] text-slate-500">Đã qua</span>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-slate-50 border border-slate-200 px-2 py-1 text-slate-500 shadow-sm">
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
                                        <div class="flex h-full min-h-[145px] items-center justify-center rounded-md border border-dashed border-slate-200 bg-white/[0.01]">
                                            <span class="text-xs text-slate-600">—</span>
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                    
                    @if($slots->isEmpty())
                        <tr>
                            <td colspan="8" class="p-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
           class="rounded-md border border-slate-200 bg-slate-50 px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-100 hover:border-slate-200">
            ← Tuần trước
        </a>
        <a href="{{ route('student.schedule.index', ['week_start' => $thisWeekStr, 'year' => \Illuminate\Support\Carbon::now()->year]) }}" 
           class="rounded-md bg-[#1e4f8f] px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-[#163c6d]">
            Tuần hiện tại
        </a>
        <a href="{{ route('student.schedule.index', ['week_start' => $nextWeekStr, 'year' => $selectedYear]) }}" 
           class="rounded-md border border-slate-200 bg-slate-50 px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-100 hover:border-slate-200">
            Tuần sau →
        </a>
    </div>
</div>
@endsection
