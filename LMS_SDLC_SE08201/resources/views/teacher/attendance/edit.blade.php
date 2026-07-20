@extends('layouts.app')

@section('title', 'Điểm danh — '.$class->class_name)

@section('content')
<div class="w-full">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-800">Điểm danh buổi {{ $session->session_date->format('d/m/Y') }}</h2>
                <p class="text-sm text-slate-500">{{ $class->class_name }} · {{ $session->room }} · {{ $session->shift }}</p>
            </div>
            <a href="{{ route('teacher.classes.show', $class) }}" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200">← Về lớp</a>
        </div>

        <form method="POST" action="{{ route('teacher.attendance.update', [$class, $session]) }}">
            @csrf
            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                @forelse ($students as $sv)
                    @php($current = $existing[$sv->user_id]->status ?? 'P')
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-700">{{ $sv->full_name }}</p>
                            <p class="text-xs text-slate-400">{{ $sv->email }}</p>
                        </div>
                        <div class="flex gap-1">
                            @foreach (['P' => ['Có mặt', 'peer-checked:bg-emerald-600'], 'L' => ['Muộn', 'peer-checked:bg-amber-500'], 'A' => ['Vắng', 'peer-checked:bg-rose-600']] as $val => [$label, $color])
                                <label class="cursor-pointer">
                                    <input type="radio" name="attendances[{{ $sv->user_id }}][status]" value="{{ $val }}" class="peer sr-only" @checked($current === $val)>
                                    <span class="inline-block rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-500 transition {{ $color }} peer-checked:text-white peer-checked:border-transparent">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <input type="text" name="attendances[{{ $sv->user_id }}][note]"
                               value="{{ $existing[$sv->user_id]->note ?? '' }}" placeholder="Ghi chú..."
                               class="w-full sm:w-40 rounded-lg border border-slate-200 px-3 py-1.5 text-xs focus:border-indigo-500 outline-none">
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-400">Lớp chưa có sinh viên để điểm danh.</p>
                @endforelse
            </div>

            @if ($students->isNotEmpty())
                <button class="mt-5 w-full rounded-lg bg-indigo-600 py-3 text-sm font-semibold text-white hover:bg-indigo-700">💾 Lưu điểm danh</button>
            @endif
        </form>
    </div>
</div>
@endsection
