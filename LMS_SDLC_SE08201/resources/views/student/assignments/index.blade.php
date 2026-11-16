@extends('layouts.app')

@section('title', 'Bài tập & Quiz')

@section('content')

{{-- Thanh bộ lọc --}}
<div class="mb-5 inline-block max-w-full rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition-all">
    <form method="GET" action="{{ route('student.assignments.index') }}" id="assignment-filter-form" class="flex flex-wrap items-center gap-4">
        @if($type)
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        
        <div class="w-full sm:w-64">
            <label class="sr-only">Chọn lớp</label>
            <select name="class_id" onchange="document.getElementById('assignment-filter-form').submit()" class="w-full rounded-md border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="">Tất cả các lớp</option>
                @foreach($enrolledClasses as $cls)
                    <option value="{{ $cls->class_id }}" {{ $selectedClassId == $cls->class_id ? 'selected' : '' }}>
                        {{ $cls->class_name }} — {{ $cls->subject->subject_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-80 relative">
            <label class="sr-only">Tìm kiếm</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Tìm theo tên bài, lớp..." oninput="debounceSearch()" class="w-full rounded-md border border-slate-200 pl-10 pr-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
        </div>
        <a href="{{ route('student.assignments.index', array_filter(['type' => $type])) }}" class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-500 transition-all hover:bg-slate-100 hover:text-[#163c6d]" title="Làm mới">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </a>
    </form>
</div>

<div class="mb-5 flex gap-2">
    <a href="{{ route('student.assignments.index', array_filter(['class_id' => $selectedClassId, 'search' => $searchQuery])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ ! $type ? 'bg-[#1e4f8f] text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors' }}">Tất cả</a>
    <a href="{{ route('student.assignments.index', array_filter(['type' => 'essay', 'class_id' => $selectedClassId, 'search' => $searchQuery])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $type === 'essay' ? 'bg-[#1e4f8f] text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors' }}">Tự luận</a>
    <a href="{{ route('student.assignments.index', array_filter(['type' => 'quiz', 'class_id' => $selectedClassId, 'search' => $searchQuery])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $type === 'quiz' ? 'bg-[#1e4f8f] text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors' }}">Trắc nghiệm</a>
</div>

<div class="space-y-3">
    @forelse ($assignments as $a)
        @php($sub = $a->my_submission)
        <a href="{{ route('student.assignments.show', $a) }}" class="block glass-card-3d p-5 rounded-lg relative overflow-hidden transition-all duration-300 border border-slate-200 hover:border-[#1e4f8f]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-lg p-0.5 shrink-0 shadow-sm {{ $a->isQuiz() ? ' bg-[#1e4f8f] ' : ' bg-[#1e4f8f] ' }}">
                        <div class="w-full h-full rounded-[14px] bg-white flex items-center justify-center">
                            @if ($a->isQuiz())
                                <svg class="w-5 h-5 text-blue-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                            @else
                                <svg class="w-5 h-5 text-[#1e4f8f] filter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-slate-900 text-base">{{ $a->title }}</p>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase {{ $a->isQuiz() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-[#1e4f8f]/10 text-[#1e4f8f] border border-[#1e4f8f]/30' }}">{{ $a->typeLabel() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $a->classRoom?->class_name }} — {{ $a->classRoom?->subject?->subject_name }} · Hạn nộp: <span class="font-bold text-slate-700">{{ $a->due_date->format('H:i d/m/Y') }}</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($sub && $sub->score !== null)
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1.5 text-sm font-bold text-emerald-700">{{ $sub->score }}/{{ $a->max_score }}</span>
                    @elseif ($sub && $sub->submitted_at)
                        <span class="rounded-full border border-[#1e4f8f]/30 bg-[#1e4f8f]/10 px-3 py-1.5 text-xs font-bold text-sky-700">Đã nộp — chờ chấm</span>
                    @elseif ($a->isOverdue())
                        <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">Đã hết hạn</span>
                    @else
                        <span class="text-xs font-semibold text-slate-500">Chưa nộp</span>
                        <span class="deadline-countdown rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700"
                              data-deadline="{{ $a->due_date->toIso8601String() }}">{{ $a->due_date->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="glass-card-3d p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-lg bg-slate-50 border border-slate-200 mb-3">
                <svg class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-500">Không có bài tập nào phù hợp.</p>
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
        }, 1000);
    }
</script>

@include('shared.deadline-countdown')
@endsection
