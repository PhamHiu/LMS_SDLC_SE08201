@extends('layouts.app')

@section('title', 'Bài tập & Quiz')

@section('content')
<div class="mb-5 flex gap-2">
    <a href="{{ route('student.assignments.index') }}" class="rounded-full px-4 py-2 text-sm font-medium {{ ! $type ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">Tất cả</a>
    <a href="{{ route('student.assignments.index', ['type' => 'essay']) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $type === 'essay' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">Tự luận</a>
    <a href="{{ route('student.assignments.index', ['type' => 'quiz']) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $type === 'quiz' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">Trắc nghiệm</a>
</div>

<div class="space-y-3">
    @forelse ($assignments as $a)
        @php($sub = $a->my_submission)
        <a href="{{ route('student.assignments.show', $a) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $a->isQuiz() ? '🧩' : '📝' }}</span>
                        <p class="font-bold text-slate-800">{{ $a->title }}</p>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $a->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $a->typeLabel() }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">{{ $a->classRoom?->class_name }} — {{ $a->classRoom?->subject?->subject_name }} · Hạn: {{ $a->due_date->format('H:i d/m/Y') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($sub && $sub->score !== null)
                        <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-bold text-emerald-700">{{ $sub->score }}/{{ $a->max_score }}</span>
                    @elseif ($sub && $sub->submitted_at)
                        <span class="rounded-full bg-sky-100 px-3 py-1.5 text-xs font-medium text-sky-700">Đã nộp — chờ chấm</span>
                    @elseif ($a->isOverdue())
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500">Đã hết hạn</span>
                    @else
                        <span class="text-xs font-medium text-slate-400">Chưa nộp</span>
                        <span class="deadline-countdown rounded-full bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-700"
                              data-deadline="{{ $a->due_date->toIso8601String() }}">{{ $a->due_date->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">Không có bài tập nào.</div>
    @endforelse
</div>

<div class="mt-4">{{ $assignments->links() }}</div>

@include('shared.deadline-countdown')
@endsection
