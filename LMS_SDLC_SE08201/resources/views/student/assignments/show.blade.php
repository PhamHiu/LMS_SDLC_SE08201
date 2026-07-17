@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
{{-- Thông tin bài tập --}}
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center gap-2">
        <h2 class="text-xl font-extrabold text-slate-800">{{ $assignment->title }}</h2>
        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $assignment->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $assignment->typeLabel() }}</span>
    </div>
    <p class="mt-1 text-sm text-slate-500">{{ $assignment->classRoom?->class_name }} — {{ $assignment->classRoom?->subject?->subject_name }} · Thang điểm {{ $assignment->max_score }}</p>
    <p class="mt-1 text-sm {{ $assignment->isOverdue() ? 'text-rose-500 font-medium' : 'text-slate-500' }}">
        ⏰ Hạn nộp: {{ $assignment->due_date->format('H:i d/m/Y') }} {{ $assignment->isOverdue() ? '(đã hết hạn)' : '('.$assignment->due_date->diffForHumans().')' }}
    </p>
    @if ($assignment->description)
        <div class="mt-4 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{{ $assignment->description }}</div>
    @endif
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Khu vực nộp bài / kết quả --}}
    <div class="space-y-6">
        @if ($assignment->isQuiz())
            {{-- QUIZ --}}
            @if ($submission)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm">
                    <h3 class="font-bold text-emerald-800">✅ Bạn đã hoàn thành bài Quiz</h3>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $submission->score }}<span class="text-lg font-semibold text-emerald-500">/{{ $assignment->max_score }}</span></p>
                    <p class="mt-1 text-xs text-emerald-600">Nộp lúc {{ $submission->submitted_at?->format('H:i d/m/Y') }} · Hệ thống tự động chấm</p>
                </div>

                @if ($canViewAnswers)
                    {{-- Xem lại bài làm — chỉ khi giáo viên cho phép xem đáp án --}}
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">Xem lại bài làm & đáp án</h3></div>
                        <div class="space-y-4 p-5">
                            @foreach ($submission->details as $i => $detail)
                                @php($q = $detail->question)
                                @if ($q)
                                    <div class="rounded-xl border p-4 {{ $detail->isCorrect() ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }}">
                                        <p class="text-sm font-semibold text-slate-700">Câu {{ $i + 1 }}: {{ $q->content }} {{ $detail->isCorrect() ? '✅' : '❌' }}</p>
                                        <div class="mt-2 space-y-1 text-sm">
                                            @foreach ($q->options() as $key => $option)
                                                <p class="rounded-lg px-3 py-1.5
                                                    {{ $key === $q->correct_answer ? 'bg-emerald-100 font-semibold text-emerald-700' : ($key === $detail->selected_option ? 'bg-rose-100 text-rose-700' : 'text-slate-500') }}">
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
                    {{-- Giáo viên chưa bật "Cho phép xem đáp án" → chỉ hiện trạng thái đã nộp --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm">
                        🔒 Giáo viên chưa cho phép xem đáp án của bài Quiz này. Bạn chỉ có thể xem điểm và trạng thái đã nộp.
                    </div>
                @endif
            @elseif ($assignment->isOverdue())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                    <p class="text-slate-500">⌛ Bài Quiz đã hết hạn làm bài.</p>
                </div>
            @else
                <div class="rounded-2xl border border-purple-200 bg-purple-50/40 p-6 text-center shadow-sm">
                    <p class="text-4xl">🧩</p>
                    <h3 class="mt-2 font-bold text-purple-800">Bài Quiz — chỉ được làm 1 lần</h3>
                    <p class="mt-1 text-sm text-purple-600">Hệ thống sẽ tự động chấm điểm ngay khi bạn nộp bài.</p>
                    <a href="{{ route('student.assignments.quiz.take', $assignment) }}" class="mt-4 inline-block rounded-lg bg-purple-600 px-6 py-3 text-sm font-bold text-white hover:bg-purple-700">Bắt đầu làm bài →</a>
                </div>
            @endif
        @else
            {{-- TỰ LUẬN --}}
            @if ($submission && $submission->isGraded())
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm">
                    <h3 class="font-bold text-emerald-800">✅ Đã chấm điểm</h3>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $submission->score }}<span class="text-lg font-semibold text-emerald-500">/{{ $assignment->max_score }}</span></p>

                    {{-- File bài làm đã nộp --}}
                    @if ($submission->submit_file_path)
                        <a href="{{ asset('storage/'.$submission->submit_file_path) }}" target="_blank"
                           class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:border-indigo-300 hover:text-indigo-700">
                            📄 Xem file bài làm đã nộp
                        </a>
                    @endif

                    {{-- File nhận xét/kết quả do giảng viên đính kèm --}}
                    @if ($submission->result_file_path)
                        <a href="{{ asset('storage/'.$submission->result_file_path) }}" target="_blank"
                           class="mt-2 inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm font-medium text-emerald-700 hover:border-emerald-400">
                            📎 File nhận xét của giảng viên
                        </a>
                    @endif

                    @if ($submission->feedback)
                        <div class="mt-3 rounded-lg bg-white p-3 text-sm text-slate-600">
                            <p class="text-xs font-bold uppercase text-slate-400">Nhận xét của giảng viên</p>
                            <p class="mt-1 whitespace-pre-line">{{ $submission->feedback }}</p>
                        </div>
                    @endif
                    <p class="mt-2 text-xs text-emerald-600">Chấm lúc {{ $submission->graded_at?->format('H:i d/m/Y') }}</p>
                </div>
            @elseif ($submission && $submission->submitted_at)
                <div class="rounded-2xl border border-sky-200 bg-sky-50/50 p-6 shadow-sm">
                    <h3 class="font-bold text-sky-800">📥 Đã nộp bài — chờ giảng viên chấm</h3>
                    <p class="mt-1 text-sm text-sky-600">Nộp lúc {{ $submission->submitted_at->format('H:i d/m/Y') }}</p>
                    @if ($submission->submit_file_path)
                        <a href="{{ asset('storage/'.$submission->submit_file_path) }}" target="_blank"
                           class="mt-3 inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm font-medium text-sky-700 hover:border-sky-400">
                            📄 Xem file bài làm đã nộp
                        </a>
                    @endif
                </div>
            @endif

            @if (! $assignment->isOverdue() && (! $submission || ! $submission->isGraded()))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-bold text-slate-800">{{ $submission ? '🔄 Nộp lại bài (ghi đè bài cũ)' : '⬆️ Nộp bài làm' }}</h3>
                    <form method="POST" action="{{ route('student.assignments.essay.submit', $assignment) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <input type="file" name="file" required
                               class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-indigo-700">
                        <p class="text-xs text-slate-400">Định dạng: PDF, Word, ZIP, RAR · Tối đa 20MB</p>
                        <button class="w-full rounded-lg bg-indigo-600 py-3 text-sm font-bold text-white hover:bg-indigo-700">Nộp bài</button>
                    </form>
                </div>
            @elseif ($assignment->isOverdue() && ! $submission)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                    <p class="text-slate-500">⌛ Đã quá hạn nộp bài.</p>
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
