@extends('layouts.app')

@section('title', 'Bài tập & Quiz')

@section('content')

{{-- Thanh bộ lọc --}}
<div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
    <form method="GET" action="{{ route('student.assignments.index') }}" id="assignment-filter-form" class="flex flex-wrap gap-4">
        @if($type)
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        
        <div class="flex-1 min-w-[200px]">
            <label class="sr-only">Chọn lớp</label>
            <select name="class_id" onchange="document.getElementById('assignment-filter-form').submit()" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">Tất cả các lớp</option>
                @foreach($enrolledClasses as $cls)
                    <option value="{{ $cls->class_id }}" {{ $selectedClassId == $cls->class_id ? 'selected' : '' }}>
                        {{ $cls->class_name }} — {{ $cls->subject->subject_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex-auto min-w-[300px] relative">
            <label class="sr-only">Tìm kiếm</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Tìm theo tên bài, lớp..." oninput="debounceSearch()" class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all">
        </div>
    </form>
</div>

<div class="mb-5 flex gap-2">
    <a href="{{ route('student.assignments.index', array_filter(['class_id' => $selectedClassId, 'search' => $searchQuery])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ ! $type ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors' }}">Tất cả</a>
    <a href="{{ route('student.assignments.index', array_filter(['type' => 'essay', 'class_id' => $selectedClassId, 'search' => $searchQuery])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $type === 'essay' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors' }}">Tự luận</a>
    <a href="{{ route('student.assignments.index', array_filter(['type' => 'quiz', 'class_id' => $selectedClassId, 'search' => $searchQuery])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $type === 'quiz' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors' }}">Trắc nghiệm</a>
</div>

<div class="space-y-3">
    @forelse ($assignments as $a)
        @php($sub = $a->my_submission)
        <a href="{{ route('student.assignments.show', $a) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $a->isQuiz() ? '🧩' : '📝' }}</span>
                        <p class="font-bold text-slate-800">{{ $a->title }}</p>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $a->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $a->typeLabel() }}</span>
                    </div>
                    <p class="mt-1.5 text-xs font-medium text-slate-500">{{ $a->classRoom?->class_name }} — {{ $a->classRoom?->subject?->subject_name }} · Hạn: <span class="font-bold">{{ $a->due_date->format('H:i d/m/Y') }}</span></p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($sub && $sub->score !== null)
                        <span class="rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1.5 text-sm font-extrabold text-emerald-800 shadow-sm">{{ $sub->score }}/{{ $a->max_score }}</span>
                    @elseif ($sub && $sub->submitted_at)
                        <span class="rounded-full border border-sky-200 bg-sky-100 px-3 py-1.5 text-xs font-bold text-sky-800 shadow-sm">Đã nộp — chờ chấm</span>
                    @elseif ($a->isOverdue())
                        <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500 shadow-sm">Đã hết hạn</span>
                    @else
                        <span class="text-xs font-semibold text-slate-400">Chưa nộp</span>
                        <span class="deadline-countdown rounded-full border border-amber-200 bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 shadow-sm"
                              data-deadline="{{ $a->due_date->toIso8601String() }}">{{ $a->due_date->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-3">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-600">Không có bài tập nào phù hợp.</p>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $assignments->links() }}</div>

<script>
    let timeout = null;
    function debounceSearch() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            document.getElementById('assignment-filter-form').submit();
        }, 400);
    }
</script>

@include('shared.deadline-countdown')
@endsection
