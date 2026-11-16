@extends('layouts.app')

@section('title', 'Chấm bài — '.$assignment->title)

@section('content')
<div class="mb-5 glass-card-3d p-6 relative overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="tool-btn !p-2.5" title="Quay lại">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">Chấm bài: {{ $assignment->title }}</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase {{ $assignment->isQuiz() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-[#1e4f8f]/10 text-[#1e4f8f] border border-[#1e4f8f]/30' }}">{{ $assignment->typeLabel() }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-600">Lớp {{ $assignment->classRoom?->class_name }} · Thang điểm <span class="font-bold text-[#1e4f8f]">{{ $assignment->max_score }}</span></p>
                <div class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                    <svg class="w-4 h-4 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><strong>{{ count($submissions) }}/{{ count($submissions) + count($notSubmitted) }}</strong> sinh viên đã nộp</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="space-y-4">
    @forelse ($submissions as $sub)
        <div class="glass-card-3d p-5 relative overflow-hidden border border-slate-200 hover:border-[#1e4f8f] transition">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#1e4f8f] font-bold text-white text-base shadow-sm border border-[#1e4f8f]/30">{{ mb_substr($sub->student?->full_name ?? '?', 0, 1) }}</span>
                    <div>
                        <p class="font-semibold text-slate-900 text-base">{{ $sub->student?->full_name }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">Nộp lúc {{ $sub->submitted_at?->format('H:i d/m/Y') }}
                            @if ($sub->submitted_at && $sub->submitted_at->gt($assignment->due_date))
                                <span class="font-bold text-rose-600">(nộp muộn)</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    @if ($sub->isGraded())
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1.5 text-sm font-bold text-emerald-700">{{ $sub->score }}/{{ $assignment->max_score }}</span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Chờ chấm</span>
                    @endif
                    @if ($sub->submit_file_path)
                        <a href="{{ route('teacher.grading.download', $sub) }}" class="tool-btn !bg-slate-50 hover:!bg-slate-100 !text-slate-600">
                            <svg class="w-3.5 h-3.5 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Tải bài</span>
                        </a>
                    @endif
                </div>
            </div>

            @if ($assignment->isQuiz())
                <p class="mt-3 rounded-md bg-blue-50 border border-blue-200 px-4 py-2 text-xs font-semibold text-blue-700">Bài Quiz — hệ thống đã tự động chấm điểm.</p>
            @else
                <div class="mt-4 pt-4 border-t border-slate-200 flex justify-end">
                    <a href="{{ route('teacher.grading.show', $sub) }}" class="inline-flex items-center gap-2 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition transform">
                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>{{ $sub->isGraded() ? 'Chấm lại / Sửa điểm & Nét vẽ' : 'Chấm điểm & Viết nhận xét' }}</span>
                    </a>
                </div>
            @endif
        </div>
    @empty
        <div class="glass-card-3d p-12 text-center text-sm text-slate-500">Chưa có sinh viên nào nộp bài.</div>
    @endforelse
</div>

@if ($notSubmitted->isNotEmpty())
    <div class="mt-6 glass-card-3d">
        <div class="border-b border-slate-200 px-5 py-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="font-semibold text-slate-900">Chưa nộp bài ({{ $notSubmitted->count() }})</h3>
        </div>
        <div class="flex flex-wrap gap-2 p-5">
            @foreach ($notSubmitted as $sv)
                <span class="rounded-md bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $sv->full_name }}</span>
            @endforeach
        </div>
    </div>
@endif
@include('components.toast')
@endsection
