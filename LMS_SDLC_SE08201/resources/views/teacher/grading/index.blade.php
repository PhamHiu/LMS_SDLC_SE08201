@extends('layouts.app')

@section('title', 'Chấm bài — '.$assignment->title)

@section('content')
<div class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-800">{{ $assignment->title }}</h2>
            <p class="text-sm text-slate-500">Lớp {{ $assignment->classRoom?->class_name }} · {{ $assignment->typeLabel() }} · Thang điểm {{ $assignment->max_score }}</p>
        </div>
        <a href="{{ route('teacher.assignments.show', $assignment) }}" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200">← Chi tiết bài tập</a>
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
                {{-- Form chấm điểm tự luận --}}
                <details class="mt-4" {{ $sub->isGraded() ? '' : 'open' }}>
                    <summary class="cursor-pointer text-sm font-semibold text-indigo-600 hover:underline">{{ $sub->isGraded() ? 'Chấm lại / sửa nhận xét' : 'Chấm điểm ngay' }}</summary>
                    <form method="POST" action="{{ route('teacher.grading.grade', $sub) }}" enctype="multipart/form-data" class="mt-3 grid gap-3 sm:grid-cols-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Điểm (tối đa {{ $assignment->max_score }})</label>
                            <input type="number" name="score" value="{{ $sub->score }}" min="0" max="{{ $assignment->max_score }}" step="0.25" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Nhận xét</label>
                            <input type="text" name="feedback" value="{{ $sub->feedback }}" placeholder="Nhận xét cho sinh viên..."
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">File feedback (tùy chọn)</label>
                            <input type="file" name="result_file" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2 file:py-1.5 file:text-xs">
                        </div>
                        <div class="sm:col-span-4">
                            <button class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">💾 Lưu điểm</button>
                        </div>
                    </form>
                </details>
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
@endsection
