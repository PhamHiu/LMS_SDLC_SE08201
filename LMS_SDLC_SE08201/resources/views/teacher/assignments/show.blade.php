@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="glass-card-3d p-6 relative overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.classes.show', $assignment->classRoom) }}" class="tool-btn !p-2" title="Quay lại">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-xl font-extrabold text-white">{{ $assignment->title }}</h2>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-black uppercase {{ $assignment->isQuiz() ? 'bg-purple-500/20 text-purple-300 border border-purple-400/40' : 'bg-[#1558C9]/30 text-[#D4FF24] border border-[#D4FF24]/40' }}">{{ $assignment->typeLabel() }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-300">Lớp {{ $assignment->classRoom?->class_name }} · Hạn nộp: <strong class="text-white">{{ $assignment->due_date->format('H:i d/m/Y') }}</strong> · Thang điểm <span class="font-bold text-[#D4FF24]">{{ $assignment->max_score }}</span></p>
            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-400">
                <svg class="w-4 h-4 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>{{ $submissionCount }}/{{ $studentCount }}</strong> sinh viên đã nộp</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('teacher.assignments.edit', $assignment) }}" class="tool-btn !bg-white/5 hover:!bg-white/10 !text-white">
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Sửa bài tập</span>
            </a>
            <a href="{{ route('teacher.grading.index', $assignment) }}" class="tool-btn !bg-[#1558C9] !text-[#D4FF24] !border-[#D4FF24]/60 shadow-[0_0_12px_rgba(21,88,201,0.5)]">
                <svg class="w-4 h-4 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Xem bài nộp / Chấm điểm</span>
            </a>
        </div>
    </div>
    @if ($assignment->description)
        <div class="mt-4 whitespace-pre-line rounded-xl bg-white/5 border border-white/10 p-4 text-sm text-slate-200 leading-relaxed">{{ $assignment->description }}</div>
    @endif
    @if ($assignment->attachment_path)
        <div class="mt-4 flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tài liệu đính kèm:</span>
            <a href="{{ route('teacher.assignments.download-attachment', $assignment) }}" class="tool-btn !bg-white/5 hover:!bg-white/10 !text-[#D4FF24]">
                <svg class="w-4 h-4 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Tải xuống</span>
            </a>
        </div>
    @endif
</div>

@if ($assignment->isQuiz())
    <div class="mt-6 glass-card-3d">
        <div class="border-b border-white/10 px-5 py-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="font-extrabold text-white">Bộ câu hỏi trắc nghiệm ({{ $assignment->questions->count() }})</h3>
        </div>
        <div class="space-y-4 p-5">
            @foreach ($assignment->questions as $i => $q)
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-sm font-semibold text-white">Câu {{ $i + 1 }}: {{ $q->content }}</p>
                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2 text-sm">
                        @foreach ($q->options() as $key => $option)
                            <p class="rounded-lg px-3 py-1.5 {{ $key === $q->correct_answer ? 'bg-emerald-500/20 font-bold text-emerald-300 border border-emerald-400/40' : 'bg-white/5 text-slate-300' }}">
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
@include('components.toast')
@endsection
