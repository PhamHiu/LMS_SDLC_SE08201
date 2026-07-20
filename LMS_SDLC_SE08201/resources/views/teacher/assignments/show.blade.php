@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.classes.show', $assignment->classRoom) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors" title="Quay lại">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                </a>
                <h2 class="text-xl font-extrabold text-slate-800">{{ $assignment->title }}</h2>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $assignment->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $assignment->typeLabel() }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Lớp {{ $assignment->classRoom?->class_name }} · Hạn: {{ $assignment->due_date->format('H:i d/m/Y') }} · Thang điểm {{ $assignment->max_score }}</p>
            <p class="mt-1 text-sm text-slate-500">📥 {{ $submissionCount }}/{{ $studentCount }} sinh viên đã nộp</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- ( Đăng thêm ) Nút sửa bài tập -->
            <a href="{{ route('teacher.assignments.edit', $assignment) }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 border border-slate-300 hover:bg-slate-50">Sửa bài tập</a>
            <a href="{{ route('teacher.grading.index', $assignment) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Xem bài nộp / Chấm điểm</a>
        </div>
    </div>
    @if ($assignment->description)
        <div class="mt-4 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{{ $assignment->description }}</div>
    @endif
    <!-- ( Đăng thêm ) Hiển thị nút tải tài liệu đính kèm nếu có -->
    @if ($assignment->attachment_path)
        <div class="mt-4 flex items-center gap-2">
            <span class="text-sm font-medium text-slate-700">Tài liệu đính kèm:</span>
            <a href="{{ route('teacher.assignments.download-attachment', $assignment) }}" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Tải xuống
            </a>
        </div>
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
@include('components.toast')
@endsection
