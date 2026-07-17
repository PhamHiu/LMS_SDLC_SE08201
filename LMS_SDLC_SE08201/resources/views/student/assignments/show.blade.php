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
    @if ($assignment->attachment_path)
        <div class="mt-4 flex items-center gap-2">
            <span class="text-sm font-medium text-slate-700">📎 File đính kèm:</span>
            <a href="{{ asset(Storage::url($assignment->attachment_path)) }}" class="rounded bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100" target="_blank">⬇ Tải xuống</a>
        </div>
    @endif
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

                {{-- Xem lại bài làm --}}
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">Xem lại bài làm</h3></div>
                    <div class="space-y-4 p-5">
                        @foreach ($submission->details as $i => $detail)
                            @php
                                $q = $detail->question;
                            @endphp
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
                    @if ($submission->feedback)
                        <div class="mt-3 rounded-lg bg-white p-3 text-sm text-slate-600">
                            <p class="text-xs font-bold uppercase text-slate-400">Nhận xét của giảng viên</p>
                            <div class="mt-1 prose prose-slate max-w-none text-sm font-normal text-slate-700">{!! $submission->feedback !!}</div>
                        </div>
                    @endif

                    <!-- Teacher's inline comments list -->
                    @if ($submission->comments->count() > 0)
                        <div class="mt-4 pt-4 border-t border-emerald-100">
                            <p class="text-xs font-bold uppercase text-slate-400 mb-3">Ghi chú & Nhận xét chi tiết trên bài làm</p>
                            <div class="space-y-3">
                                @foreach ($submission->comments as $comment)
                                    @php
                                        $commentData = json_decode($comment->content, true);
                                        $hasHighlight = is_array($commentData) && isset($commentData['text']);
                                        $highlightedText = $hasHighlight ? $commentData['text'] : null;
                                        $commentText = $hasHighlight ? $commentData['comment'] : $comment->content;
                                        
                                        $isTeacher = $comment->user->isTeacher();
                                    @endphp
                                    <div class="rounded-xl border border-slate-100 bg-white p-3.5 shadow-sm space-y-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-600">
                                                    {{ substr($comment->user->full_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <span class="text-xs font-bold text-slate-700">{{ $comment->user->full_name }}</span>
                                                    <span class="text-[9px] text-slate-400 ml-1.5">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            @if ($isTeacher)
                                                <span class="text-[8px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-1.5 py-0.5 rounded uppercase">Giáo viên nhận xét</span>
                                            @endif
                                        </div>

                                        @if ($highlightedText)
                                            <div class="p-2 rounded bg-amber-50/50 border-l-2 border-amber-400 text-[10px] text-amber-800 italic">
                                                "{{ $highlightedText }}"
                                            </div>
                                        @endif

                                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $commentText }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($submission->result_file_path)
                        @php
                            $resExt = strtolower(pathinfo($submission->result_file_path, PATHINFO_EXTENSION));
                            $isResPreviewable = in_array($resExt, ['pdf', 'docx', 'png', 'jpg', 'jpeg', 'webp', 'gif']);
                            $resLink = $isResPreviewable 
                                ? route('submissions.preview', ['submission' => $submission, 'type' => 'result']) 
                                : route('student.submissions.download-raw', ['submission' => $submission, 'type' => 'result']);
                        @endphp
                        <div class="mt-4 p-4 rounded-xl border border-indigo-100 bg-indigo-50/50 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📝</span>
                                <div>
                                    <p class="text-sm font-semibold text-indigo-900">Bài làm có ghi chú của giáo viên</p>
                                    <p class="text-xs text-indigo-500">Xem các sửa đổi và nhận xét trực tiếp</p>
                                </div>
                            </div>
                            <a href="{{ $resLink }}" target="_blank" class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition-all duration-200">Xem chi tiết</a>
                        </div>
                    @endif

                    @if ($submission->submit_file_path)
                        @php
                            $subRawExt = strtolower(pathinfo($submission->submit_file_path, PATHINFO_EXTENSION));
                            $isSubRawPreviewable = in_array($subRawExt, ['pdf', 'docx', 'png', 'jpg', 'jpeg', 'webp', 'gif']);
                            $subRawLink = $isSubRawPreviewable 
                                ? route('submissions.preview', ['submission' => $submission]) 
                                : route('student.submissions.download-raw', ['submission' => $submission]);
                        @endphp
                        <div class="mt-3 p-4 rounded-xl border border-slate-200 bg-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📄</span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Bài làm bạn đã nộp</p>
                                    <p class="text-xs text-slate-500">Nộp lúc {{ $submission->submitted_at?->format('H:i d/m/Y') }}</p>
                                </div>
                            </div>
                            <a href="{{ $subRawLink }}" target="_blank" class="rounded-lg bg-slate-100 border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-all duration-200">Xem file gốc</a>
                        </div>
                    @endif

                    <p class="mt-3 text-xs text-emerald-600">Chấm lúc {{ $submission->graded_at?->format('H:i d/m/Y') }}</p>
                </div>
            @elseif ($submission && $submission->submitted_at)
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/30 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-indigo-900 flex items-center gap-1.5 text-sm uppercase tracking-wide">
                                📥 Đã nộp bài thành công
                            </h3>
                            <p class="mt-1 text-xs text-indigo-600">Trạng thái: <strong>Chờ giảng viên chấm điểm</strong></p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold text-amber-700 animate-pulse">Đang chờ chấm</span>
                    </div>

                    <!-- File detail card -->
                    <div class="mt-4 p-3.5 rounded-xl border border-indigo-100 bg-white flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">
                                @php
                                    $ext = pathinfo($submission->submit_file_path, PATHINFO_EXTENSION);
                                    $icon = match(strtolower($ext)) {
                                        'pdf' => '📕',
                                        'doc', 'docx' => '📘',
                                        'zip', 'rar' => '📦',
                                        default => '📄'
                                    };
                                @endphp
                                {{ $icon }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-700 max-w-[220px] truncate" title="{{ basename($submission->submit_file_path) }}">
                                    {{ basename($submission->submit_file_path) }}
                                </p>
                                <p class="text-[10px] text-slate-400">Nộp lúc {{ $submission->submitted_at->format('H:i d/m/Y') }} ({{ $submission->submitted_at->diffForHumans() }})</p>
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            @php
                                $isSubPreviewable = in_array(strtolower($ext), ['pdf', 'docx', 'png', 'jpg', 'jpeg', 'webp', 'gif']);
                                $subLink = $isSubPreviewable 
                                    ? route('submissions.preview', ['submission' => $submission]) 
                                    : route('student.submissions.download-raw', ['submission' => $submission]);
                            @endphp
                            <a href="{{ $subLink }}" target="_blank" class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-all duration-200">
                                Xem bài
                            </a>
                            @if (!$assignment->isOverdue())
                                <button type="button" onclick="toggleResubmitForm()" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition-all duration-200">
                                    ✏️ Cập nhật
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Hidden re-submit form -->
                    @if (!$assignment->isOverdue())
                        <div id="resubmit-form-container" class="hidden mt-4 pt-4 border-t border-slate-200/60">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider">Cập nhật/Nộp lại bài làm mới</h4>
                            <form method="POST" action="{{ route('student.assignments.essay.submit', $assignment) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <input type="file" name="file" required
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 border border-slate-300 rounded-lg">
                                <p class="text-[10px] text-slate-400">Ghi chú: Bài làm mới sẽ ghi đè lên bài cũ. Định dạng cho phép: PDF, Word, ZIP, RAR (Tối đa 20MB).</p>
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="toggleResubmitForm()" class="rounded-lg bg-slate-50 border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Hủy</button>
                                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm">Nộp bài mới</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            @if (!$submission && !$assignment->isOverdue())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-bold text-slate-800 flex items-center gap-1.5">
                        <span class="text-lg">⬆️</span> Nộp bài làm
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Đính kèm file báo cáo của bạn dưới định dạng yêu cầu để giảng viên chấm điểm.</p>
                    <form method="POST" action="{{ route('student.assignments.essay.submit', $assignment) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <input type="file" name="file" required
                               class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-indigo-700 border border-slate-300 rounded-lg">
                        <p class="text-xs text-slate-400">Định dạng: PDF, Word, ZIP, RAR · Tối đa 20MB</p>
                        <button class="w-full rounded-lg bg-indigo-600 py-3 text-sm font-bold text-white hover:bg-indigo-700 shadow-sm transition-all duration-200">Nộp bài</button>
                    </form>
                </div>
            @elseif (!$submission && $assignment->isOverdue())
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
<script>
    function toggleResubmitForm() {
        const container = document.getElementById('resubmit-form-container');
        if (container) {
            container.classList.toggle('hidden');
        }
    }
</script>
@endsection
