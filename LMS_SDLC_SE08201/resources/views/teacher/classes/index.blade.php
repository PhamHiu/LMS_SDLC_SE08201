@extends('layouts.app')

@section('title', 'Lớp của tôi')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($classes as $class)
        <a href="{{ route('teacher.classes.show', $class) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <span class="rounded-xl bg-indigo-50 px-3 py-2 text-lg">🏫</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $class->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($class->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $class->statusLabel() }}</span>
            </div>
            <h3 class="mt-3 text-lg font-extrabold text-slate-800 group-hover:text-indigo-600">{{ $class->class_name }}</h3>
            <p class="text-sm text-slate-500">{{ $class->subject?->subject_code }} — {{ $class->subject?->subject_name }}</p>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                <span>👥 {{ $class->enrollments_count }} sinh viên</span>
                <span>{{ $class->start_date?->format('d/m/Y') }} → {{ $class->end_date?->format('d/m/Y') }}</span>
            </div>
        </a>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">
            Bạn chưa được phân công phụ trách lớp nào.
        </div>
    @endforelse
</div>
@endsection
