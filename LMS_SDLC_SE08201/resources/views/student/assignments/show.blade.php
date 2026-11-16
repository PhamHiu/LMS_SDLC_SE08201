@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
{{-- Thông tin bài tập --}}
<div class="glass-card-3d p-6 relative overflow-hidden">
    <div class="flex items-center gap-3 flex-wrap">
        <h2 class="text-xl font-semibold text-slate-900 tracking-wide">{{ $assignment->title }}</h2>
        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $assignment->isQuiz() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-[#1e4f8f]/10 text-[#1e4f8f] border border-[#1e4f8f]/30' }}">
            {{ $assignment->typeLabel() }}
        </span>
    </div>
    <p class="mt-1.5 text-sm text-slate-600">{{ $assignment->classRoom?->class_name }} — {{ $assignment->classRoom?->subject?->subject_name }} · Thang điểm <span class="font-bold text-[#1e4f8f]">{{ $assignment->max_score }}</span></p>
    
    <div class="mt-2 flex items-center gap-2 text-sm {{ $assignment->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-600' }}">
        <svg class="w-4 h-4 text-[#1e4f8f] filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Hạn nộp: <strong class="text-slate-900">{{ $assignment->due_date->format('H:i d/m/Y') }}</strong> {{ $assignment->isOverdue() ? '(đã hết hạn)' : '('.$assignment->due_date->diffForHumans().')' }}</span>
    </div>

    @if ($assignment->description)
        <div class="mt-4 whitespace-pre-line rounded-md bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700 leading-relaxed">{{ $assignment->description }}</div>
    @endif
    @if ($assignment->attachment_path)
        <div class="mt-4">
            <a href="{{ asset('storage/'.$assignment->attachment_path) }}" target="_blank" class="inline-flex items-center gap-2 rounded-md bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 hover:border-[#1e4f8f] px-4 py-2.5 text-sm font-bold text-[#1e4f8f] hover:text-[#163c6d] transition-all shadow-sm">
                <svg class="h-5 w-5 text-[#1e4f8f]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                <span>Tải tài liệu đính kèm</span>
            </a>
        </div>
    @endif
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Khu vực nộp bài / kết quả --}}
    <div class="space-y-6">
        @if ($assignment->isQuiz())
            {{-- QUIZ --}}
            @if ($submission)
                <div class="glass-card-3d p-6 relative overflow-hidden border border-emerald-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-md bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-emerald-700">Bạn đã hoàn thành bài Quiz</h3>
                            <p class="text-xs text-slate-500">Nộp lúc {{ $submission->submitted_at?->format('H:i d/m/Y') }} · Hệ thống tự động chấm</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-[#1e4f8f] filter">{{ $submission->score }}</span>
                        <span class="text-lg font-bold text-slate-500">/{{ $assignment->max_score }} điểm</span>
                    </div>
                </div>

                @if ($canViewAnswers)
                    {{-- Xem lại bài làm --}}
                    <div class="glass-card-3d">
                        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-bold text-slate-900">Xem lại bài làm & đáp án</h3></div>
                        <div class="space-y-4 p-5">
                            @foreach ($submission->details as $i => $detail)
                                @php($q = $detail->question)
                                @if ($q)
                                    <div class="rounded-md border p-4 {{ $detail->isCorrect() ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                                        <p class="text-sm font-semibold text-slate-900">Câu {{ $i + 1 }}: {{ $q->content }} {{ $detail->isCorrect() ? '✓' : '✕' }}</p>
                                        <div class="mt-2 space-y-1 text-sm">
                                            @foreach ($q->options() as $key => $option)
                                                <p class="rounded-lg px-3 py-1.5
 {{ $key === $q->correct_answer ? 'bg-emerald-50 font-bold text-emerald-700 border border-emerald-200' : ($key === $detail->selected_option ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'text-slate-500') }}">
                                                    {{ $key }}. {{ $option }}
                                                    {{ $key === $q->correct_answer ? '— Đáp án đúng' : ($key === $detail->selected_option ? '— Bạn chọn' : '') }}
                                                </p>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="glass-card-3d p-5 text-sm text-slate-500 flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Giáo viên chưa cho phép xem đáp án của bài Quiz này. Bạn chỉ có thể xem điểm và trạng thái đã nộp.</span>
                    </div>
                @endif
            @elseif ($assignment->isOverdue())
                <div class="glass-card-3d p-6 text-center">
                    <svg class="w-10 h-10 text-rose-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-rose-700 font-semibold">Bài Quiz đã hết hạn làm bài.</p>
                </div>
            @else
                <div class="glass-card-3d p-8 text-center relative overflow-hidden border border-blue-200">
                    <div class="w-16 h-16 rounded-lg bg-[#1e4f8f] p-0.5 mx-auto mb-3 shadow-sm">
                        <div class="w-full h-full rounded-[14px] bg-white flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Bài Quiz Trắc Nghiệm — Chỉ làm 1 lần</h3>
                    <p class="mt-1 text-sm text-slate-600">Hệ thống sẽ tự động chấm điểm ngay khi bạn nộp bài.</p>
                    <a href="{{ route('student.assignments.quiz.take', $assignment) }}" class="mt-5 inline-flex items-center gap-2 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-6 py-3 text-sm font-bold text-white shadow-sm transition transform">
                        <span>BẮT ĐẦU LÀM BÀI</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            @endif
        @else
            {{-- TỰ LUẬN --}}
            {{-- 1. Khu vực nộp bài --}}
            <div class="glass-card-3d p-6 relative overflow-hidden">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-md bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 flex items-center justify-center text-[#1e4f8f]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <h3 class="font-semibold text-base text-slate-900">Nộp bài làm trực tuyến</h3>
                </div>

                @if (! $submission || ! $submission->student_submit)
                    @if (! $assignment->isOverdue())
                        <form method="POST" action="{{ route('student.assignments.essay.submit', $assignment) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Chọn file bài nộp (Định dạng PDF)</label>
                                <input type="file" name="file" accept=".pdf" required
                                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-[#1e4f8f] file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-slate-900 file:cursor-pointer hover:file:bg-[#163c6d] rounded-md border border-slate-200 bg-white p-2">
                                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Định dạng bắt buộc: PDF · Kích thước tối đa 5MB
                                </p>
                            </div>
                            <button class="w-full flex items-center justify-center gap-2 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] py-3 text-sm font-bold text-white shadow-sm transition transform cursor-pointer">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>XÁC NHẬN NỘP BÀI</span>
                            </button>
                        </form>
                    @else
                        <div class="rounded-md border border-rose-200 bg-rose-50 p-4 text-center flex items-center justify-center gap-2 text-rose-700">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm font-bold">Đã quá hạn nộp bài.</span>
                        </div>
                    @endif
                @else
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 text-emerald-600 shadow-sm">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Đã nộp bài thành công</p>
                                <p class="text-xs text-emerald-600 mt-0.5">Nộp lúc {{ $submission->submitted_at?->format('H:i d/m/Y') }}</p>
                            </div>
                        </div>
                        <a href="{{ asset('storage/'.$submission->student_submit) }}" target="_blank" class="mt-4 flex items-center justify-center gap-2 rounded-md border border-[#1e4f8f]/30 bg-slate-50 hover:bg-slate-100 px-4 py-2.5 text-sm font-bold text-[#1e4f8f] hover:text-[#163c6d] transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>Xem lại file đã nộp</span>
                        </a>
                    </div>
                @endif
            </div>

            {{-- 2. Khung Feedback / Kết quả chấm --}}
            @if ($submission && $submission->student_submit)
                <div class="feedback-box glass-card-3d p-6 relative overflow-hidden">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-9 h-9 rounded-md bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <h3 class="font-semibold text-base text-slate-900">Kết quả chấm điểm & Nhận xét</h3>
                    </div>
                    
                    @if (! $submission->isGraded())
                        <div class="rounded-md bg-amber-50 border border-amber-200 p-4 text-center flex items-center justify-center gap-2 text-amber-700">
                            <svg class="w-5 h-5 text-amber-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="text-sm font-semibold">Bài làm đang chờ giảng viên chấm điểm.</span>
                        </div>
                    @else
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-md bg-slate-50 p-4 border border-slate-200">
                                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Điểm số đạt được</p>
                                    <p class="mt-1 text-3xl font-bold text-[#1e4f8f] filter">{{ $submission->score }}<span class="text-sm text-slate-500 font-normal">/{{ $assignment->max_score }}</span></p>
                                </div>
                                <div class="rounded-md bg-slate-50 p-4 border border-slate-200">
                                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Giảng viên chấm</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $assignment->classRoom?->teacher?->full_name ?? '—' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $submission->graded_at?->format('H:i d/m/Y') }}</p>
                                </div>
                            </div>
                            
                            @if ($submission->submit_file_path)
                                <a href="{{ asset('storage/'.$submission->submit_file_path) }}" target="_blank" class="flex w-full items-center justify-center gap-2 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-3 text-sm font-semibold text-white transition-all shadow-sm">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    <span>Xem chi tiết file bài chấm & nét vẽ của giảng viên</span>
                                </a>
                            @endif

                            @if ($submission->feedback)
                                <div class="rounded-md bg-slate-50 p-4 border border-slate-200">
                                    <p class="text-xs font-bold uppercase tracking-wider text-[#1e4f8f] mb-2 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                        Nhận xét chi tiết
                                    </p>
                                    <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ $submission->feedback }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- Thảo luận --}}
    <div>
        @include('shared.discussions', ['storeRoute' => route('student.discussions.store', $assignment)])
    </div>
</div>
@endsection
