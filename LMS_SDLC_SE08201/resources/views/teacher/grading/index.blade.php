@extends('layouts.app')

@section('title', 'Chấm bài — '.$assignment->title)

@section('content')
<div class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors" title="Quay lại">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-extrabold text-slate-800">Chấm bài: {{ $assignment->title }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $assignment->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $assignment->typeLabel() }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Lớp {{ $assignment->classRoom?->class_name }} · Thang điểm {{ $assignment->max_score }}</p>
                <p class="mt-1 text-sm text-slate-500">📥 {{ count($submissions) }}/{{ count($submissions) + count($notSubmitted) }} sinh viên đã nộp</p>
            </div>
        </div>
    </div>
</div>

<div class="space-y-4">
    @forelse ($submissions as $sub)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-600">{{ mb_substr($sub->student?->full_name ?? '?', 0, 1) }}</span>
                    <div>
                        <p class="font-semibold text-slate-700">{{ $sub->student?->full_name }}</p>
                        <p class="text-xs text-slate-400">Nộp lúc {{ $sub->submitted_at?->format('H:i d/m/Y') }}
                            @if ($sub->submitted_at && $sub->submitted_at->gt($assignment->due_date))
                                <span class="font-medium text-rose-500">(nộp muộn)</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($sub->isGraded())
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-700">{{ $sub->score }}/{{ $assignment->max_score }}</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">Chờ chấm</span>
                    @endif
                    @if ($sub->submit_file_path)
                        <a href="{{ route('teacher.grading.download', $sub) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">⬇ Tải bài làm</a>
                    @endif
                </div>
            </div>

            @if ($assignment->isQuiz())
                <p class="mt-3 rounded-lg bg-purple-50 px-4 py-2 text-sm text-purple-700">Bài Quiz — hệ thống đã tự động chấm điểm.</p>
            @else
                {{-- Nút chuyển sang giao diện chấm bài nâng cao --}}
                <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">
                    <a href="{{ route('teacher.grading.show', $sub) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        {{ $sub->isGraded() ? 'Chấm lại / Sửa điểm' : 'Chấm điểm chi tiết' }}
                    </a>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-400">Chưa có sinh viên nào nộp bài.</div>
    @endforelse
</div>

@if ($notSubmitted->isNotEmpty())
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">⏳ Chưa nộp bài ({{ $notSubmitted->count() }})</h3></div>
        <div class="flex flex-wrap gap-2 p-5">
            @foreach ($notSubmitted as $sv)
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">{{ $sv->full_name }}</span>
            @endforeach
        </div>
    </div>
@endif
@include('components.toast')
@endsection
