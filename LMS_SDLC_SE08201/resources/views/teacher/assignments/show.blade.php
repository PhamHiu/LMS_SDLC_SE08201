@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="mb-4">
    <a href="{{ route('teacher.assignments.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
        ← Quay lại danh sách bài tập
    </a>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-extrabold text-slate-800">{{ $assignment->title }}</h2>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $assignment->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $assignment->typeLabel() }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Lớp {{ $assignment->classRoom?->class_name }} · Hạn: {{ $assignment->due_date->format('H:i d/m/Y') }} · Thang điểm {{ $assignment->max_score }}</p>
            <p class="mt-1 text-sm text-slate-500">📥 {{ $submissionCount }}/{{ $studentCount }} sinh viên đã nộp</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.assignments.edit', $assignment) }}" class="rounded-lg bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-200">✏ Sửa</a>
            
            <form method="POST" action="{{ route('teacher.assignments.destroy', $assignment) }}" onsubmit="return confirm('Bạn có chắc muốn xóa bài tập này? Tất cả bài nộp của học sinh sẽ bị xóa theo và không thể khôi phục!')" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-200 transition-colors">🗑️ Xóa bài</button>
            </form>
            
            <a href="{{ route('teacher.grading.index', $assignment) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Xem bài nộp / Chấm điểm</a>
        </div>
    </div>
    @if ($assignment->attachment_path)
        <div class="mt-4 flex items-center gap-2">
            <span class="text-sm font-medium text-slate-700">📎 File đính kèm:</span>
            <a href="{{ asset(Storage::url($assignment->attachment_path)) }}" class="rounded bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100" target="_blank">⬇ Tải xuống / Xem file</a>
        </div>
    @endif
    @if ($assignment->description)
        <div class="mt-4 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{{ $assignment->description }}</div>
    @endif
</div>

@if ($assignment->isQuiz())
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">❓ Bộ câu hỏi ({{ $assignment->questions->count() }})</h3></div>
        <div class="space-y-4 p-5">
            @foreach ($assignment->questions as $i => $q)
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-700">Câu {{ $i + 1 }}: {{ $q->content }}</p>
                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2 text-sm">
                        @foreach ($q->options() as $key => $option)
                            <p class="rounded-lg px-3 py-1.5 {{ $key === $q->correct_answer ? 'bg-emerald-50 font-semibold text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-50 text-slate-600' }}">
                                {{ $key }}. {{ $option }} {{ $key === $q->correct_answer ? '✓' : '' }}
                            </p>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mt-6">
    @include('shared.discussions', ['storeRoute' => route('teacher.discussions.store', $assignment)])
</div>
@endsection
