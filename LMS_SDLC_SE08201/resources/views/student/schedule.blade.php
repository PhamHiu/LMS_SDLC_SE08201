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
            {{ $weekStart->format('d/m/Y') }} – {{ $weekEnd->format('d/m/Y') }}
            @if ($isCurrentWeek)
                <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 align-middle">Tuần này</span>
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('student.schedule.index', ['week' => $prevWeek]) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">← Tuần trước</a>
        <a href="{{ route('student.schedule.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Tuần này</a>
        <a href="{{ route('student.schedule.index', ['week' => $nextWeek]) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Tuần sau →</a>
    </div>
</div>

{{-- Lưới 7 ngày (Thứ 2 → Chủ nhật) --}}
<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
    @for ($i = 1; $i <= 7; $i++)
        @php
            $date = $weekStart->copy()->addDays($i - 1);
            $sessions = $sessionsByDay[$i] ?? collect();
            $isToday = $date->isToday();
        @endphp
        <div class="flex flex-col rounded-2xl border bg-white shadow-sm {{ $isToday ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-slate-200' }}">
            <div class="rounded-t-2xl border-b border-slate-100 px-3 py-2 text-center {{ $isToday ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-600' }}">
                <p class="text-xs font-semibold">{{ $dayLabels[$i] }}</p>
                <p class="text-lg font-extrabold leading-tight">{{ $date->format('d/m') }}</p>
            </div>
            <div class="flex-1 space-y-2 p-2">
                @forelse ($sessions as $s)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                        <p class="text-xs font-bold text-indigo-700">{{ $s->slot?->label ?? ('Ca '.$s->slot?->slot_number) }}</p>
                        @if ($s->slot)
                            <p class="text-[11px] text-slate-400">{{ \Illuminate\Support\Str::of($s->slot->start_time)->substr(0, 5) }} – {{ \Illuminate\Support\Str::of($s->slot->end_time)->substr(0, 5) }}</p>
                        @endif
                        <p class="mt-1 truncate text-sm font-semibold text-slate-700">{{ $s->classRoom?->class_name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $s->classRoom?->subject?->subject_name }}</p>
                        <p class="mt-1 truncate text-[11px] text-slate-400">🏫 {{ $s->room?->room_name ?? '—' }}</p>
                        <p class="truncate text-[11px] text-slate-400">👨‍🏫 {{ $s->classRoom?->teacher?->full_name ?? '—' }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-slate-300">Nghỉ</p>
                @endforelse
            </div>
        </div>
    @endfor
</div>
@endsection
