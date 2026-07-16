@extends('layouts.app')

@section('title', 'Thời khóa biểu')

@section('content')
@forelse ($timetable as $month => $sessions)
    <div class="mb-6">
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Tháng {{ \Illuminate\Support\Carbon::parse($month.'-01')->format('m/Y') }}</h2>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($sessions as $s)
                <div class="flex items-center gap-4 rounded-2xl border bg-white p-4 shadow-sm {{ $s->session_date->isToday() ? 'border-indigo-400 ring-2 ring-indigo-100' : ($s->session_date->isPast() ? 'border-slate-200 opacity-60' : 'border-slate-200') }}">
                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl {{ $s->session_date->isToday() ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700' }}">
                        <span class="text-[11px] font-medium">{{ $s->session_date->isoFormat('dd') }}</span>
                        <span class="text-lg font-extrabold leading-none">{{ $s->session_date->format('d') }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-700">{{ $s->classRoom?->class_name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $s->classRoom?->subject?->subject_name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $s->room }} · {{ $s->shift }} · GV {{ $s->classRoom?->teacher?->full_name }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">
        Bạn chưa được xếp vào lớp học nào. Liên hệ phòng đào tạo để được xếp lớp.
    </div>
@endforelse
@endsection
