@extends('layouts.app')

@section('title', 'Thời khóa biểu')

@section('content')
@php
    $dayLabels = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
@endphp

{{-- Thanh điều hướng tuần --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div>
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Tuần học</h2>
        <p class="text-lg font-extrabold text-slate-800">
            {{ \Illuminate\Support\Carbon::parse($selectedWeekStart)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($selectedWeekStart)->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->format('d/m/Y') }}
            @if ($isCurrentWeek)
                <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 align-middle">Tuần này</span>
            @endif
        </p>
    </div>
    <form method="GET" action="{{ route('student.schedule.index') }}" class="flex flex-wrap items-center gap-2" id="schedule-filter-form">
        <select name="year" onchange="document.getElementById('schedule-filter-form').submit()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
            @endforeach
        </select>
        
        <select name="week_start" onchange="document.getElementById('schedule-filter-form').submit()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @foreach($weeksOfYear as $w)
                <option value="{{ $w['week_start'] }}" {{ $selectedWeekStart == $w['week_start'] ? 'selected' : '' }}>
                    {{ $w['label'] }}
                </option>
            @endforeach
        </select>

        <a href="{{ route('student.schedule.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">Làm mới</a>
    </form>
</div>

{{-- Bảng TKB lưới --}}
<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full min-w-[900px] table-fixed border-collapse text-left">
        <thead>
            <tr>
                <th class="w-24 border-b border-r border-slate-200 bg-slate-50 p-3 text-center align-middle">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ca học</span>
                </th>
                @for ($i = 1; $i <= 7; $i++)
                    @php
                        $date = \Illuminate\Support\Carbon::parse($selectedWeekStart)->addDays($i - 1);
                        $isToday = $date->isToday();
                    @endphp
                    <th class="border-b border-slate-200 p-3 text-center {{ $isToday ? 'bg-indigo-50/50' : 'bg-slate-50' }} {{ $i < 7 ? 'border-r' : '' }}">
                        <p class="text-sm font-semibold {{ $isToday ? 'text-indigo-700' : 'text-slate-600' }}">{{ $dayLabels[$i] }}</p>
                        <p class="text-xs {{ $isToday ? 'text-indigo-500 font-medium' : 'text-slate-400' }}">{{ $date->format('d/m') }}</p>
                    </th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach ($slots as $slot)
                <tr>
                    <td class="border-b border-r border-slate-200 bg-slate-50/50 p-3 text-center align-middle">
                        <p class="text-sm font-bold text-slate-700">{{ $slot->label ?? ('Ca '.$slot->slot_number) }}</p>
                        <p class="text-[11px] font-medium text-slate-400">{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }} – {{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }}</p>
                    </td>
                    @for ($i = 1; $i <= 7; $i++)
                        @php
                            $date = \Illuminate\Support\Carbon::parse($selectedWeekStart)->addDays($i - 1);
                            $sessions = $sessionGrid[$i] ?? collect();
                            // Tìm buổi học thuộc ca này
                            $s = $sessions->firstWhere('slot_id', $slot->slot_id);
                        @endphp
                        
                        <td class="border-b border-slate-200 p-2 align-top {{ $i < 7 ? 'border-r' : '' }}">
                            @if ($s)
                                @php
                                    $attendance = $s->attendances->first();
                                    $status = $attendance?->status;
                                    
                                    $bgColor = 'bg-slate-50/60';
                                    $borderColor = 'border-slate-100';
                                    $ring = '';
                                    
                                    if ($status === 'P' || $status === 'L') {
                                        $bgColor = 'bg-emerald-100';
                                        $borderColor = 'border-emerald-400';
                                    } elseif ($status === 'A') {
                                        $bgColor = 'bg-rose-100';
                                        $borderColor = 'border-rose-400';
                                    } else {
                                        if ($s->session_date->isToday()) {
                                            $bgColor = 'bg-purple-100';
                                            $borderColor = 'border-purple-400';
                                            $ring = 'ring-2 ring-purple-300';
                                        } elseif ($s->session_date->isFuture()) {
                                            // Tương lai cũng có thể cho màu tím nhạt hơn hoặc mặc định
                                            $bgColor = 'bg-slate-100';
                                            $borderColor = 'border-slate-300';
                                        }
                                    }
                                @endphp
                                <div class="flex h-full flex-col justify-between rounded-xl border {{ $borderColor }} {{ $bgColor }} {{ $ring }} p-2.5 transition-all hover:shadow-sm">
                                    <div>
                                        <p class="line-clamp-2 text-xs font-bold text-slate-800" title="{{ $s->classRoom?->class_name }}">{{ $s->classRoom?->class_name }}</p>
                                        <p class="mt-0.5 truncate text-[11px] font-medium text-slate-600" title="{{ $s->classRoom?->subject?->subject_name }}">{{ $s->classRoom?->subject?->subject_name }}</p>
                                    </div>
                                    <div class="mt-2 text-[10px] font-medium text-slate-500">
                                        <p class="truncate">🏫 {{ $s->room?->room_name ?? '—' }}</p>
                                    </div>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endforeach
            
            @if($slots->isEmpty())
                <tr>
                    <td colspan="8" class="p-8 text-center text-slate-400">Không có dữ liệu ca học</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Các nút xem nhanh --}}
@php
    $currentWeekStart = \Illuminate\Support\Carbon::parse($selectedWeekStart);
    $prevWeekStr = $currentWeekStart->copy()->subWeek()->format('Y-m-d');
    $nextWeekStr = $currentWeekStart->copy()->addWeek()->format('Y-m-d');
    $thisWeekStr = \Illuminate\Support\Carbon::now()->startOfWeek()->format('Y-m-d');
@endphp
<div class="mt-6 flex flex-wrap items-center justify-center gap-4">
    <a href="{{ route('student.schedule.index', ['week_start' => $prevWeekStr, 'year' => $selectedYear]) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:shadow">⬅ Tuần trước</a>
    <a href="{{ route('student.schedule.index', ['week_start' => $thisWeekStr, 'year' => \Illuminate\Support\Carbon::now()->year]) }}" class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm shadow-indigo-200 transition-all hover:bg-indigo-700 hover:shadow-md">Hiện tại</a>
    <a href="{{ route('student.schedule.index', ['week_start' => $nextWeekStr, 'year' => $selectedYear]) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:shadow">Tuần sau ➡</a>
</div>
@endsection
