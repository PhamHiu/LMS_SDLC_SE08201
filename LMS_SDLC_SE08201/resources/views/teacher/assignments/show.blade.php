@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
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
        <a href="{{ route('teacher.grading.index', $assignment) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Xem bài nộp / Chấm điểm</a>
    </div>
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
