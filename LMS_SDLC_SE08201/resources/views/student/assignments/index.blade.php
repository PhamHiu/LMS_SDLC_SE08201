@extends('layouts.app')

@section('title', 'Bài tập & Quiz')

@section('content')

{{-- Thanh bộ lọc --}}
<div class="mb-5 inline-block max-w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
    <form method="GET" action="{{ route('student.assignments.index') }}" id="assignment-filter-form" class="flex flex-wrap items-center gap-4">
        @if($type)
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        
        <div class="w-full sm:w-64">
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
        <div class="w-full sm:w-80 relative">
            <label class="sr-only">Tìm kiếm</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Tìm theo tên bài, lớp..." oninput="debounceSearch()" class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all">
        </div>
        <a href="{{ route('student.assignments.index', array_filter(['type' => $type])) }}" class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition-all hover:bg-slate-100 hover:text-indigo-600" title="Làm mới">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </a>
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
        <a href="{{ route('student.assignments.show', $a) }}" class="block glass-card-3d p-5 rounded-2xl relative overflow-hidden transition-all duration-300 hover:scale-[1.008] border border-white/10 hover:border-[#D4FF24]/50">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-2xl p-0.5 shrink-0 shadow-lg {{ $a->isQuiz() ? 'bg-gradient-to-tr from-purple-600 to-[#1558C9] shadow-purple-500/20' : 'bg-gradient-to-tr from-[#1558C9] to-[#D4FF24] shadow-[#1558C9]/30' }}">
                        <div class="w-full h-full rounded-[14px] bg-[#030407]/90 flex items-center justify-center">
                            @if ($a->isQuiz())
                                <svg class="w-5 h-5 text-purple-300 filter drop-shadow-[0_0_6px_rgba(168,85,247,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                            @else
                                <svg class="w-5 h-5 text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-extrabold text-white text-base">{{ $a->title }}</p>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase {{ $a->isQuiz() ? 'bg-purple-500/20 text-purple-300 border border-purple-400/30' : 'bg-[#1558C9]/25 text-[#D4FF24] border border-[#D4FF24]/40' }}">{{ $a->typeLabel() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $a->classRoom?->class_name }} — {{ $a->classRoom?->subject?->subject_name }} · Hạn nộp: <span class="font-bold text-slate-200">{{ $a->due_date->format('H:i d/m/Y') }}</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($sub && $sub->score !== null)
                        <span class="rounded-full border border-emerald-400/40 bg-emerald-500/20 px-3.5 py-1.5 text-sm font-black text-emerald-300 shadow-[0_0_10px_rgba(52,211,153,0.3)]">{{ $sub->score }}/{{ $a->max_score }}</span>
                    @elseif ($sub && $sub->submitted_at)
                        <span class="rounded-full border border-[#1558C9]/50 bg-[#1558C9]/25 px-3 py-1.5 text-xs font-bold text-sky-300">Đã nộp — chờ chấm</span>
                    @elseif ($a->isOverdue())
                        <span class="rounded-full border border-rose-500/30 bg-rose-500/15 px-3 py-1.5 text-xs font-bold text-rose-300">Đã hết hạn</span>
                    @else
                        <span class="text-xs font-semibold text-slate-400">Chưa nộp</span>
                        <span class="deadline-countdown rounded-full border border-amber-400/40 bg-amber-500/20 px-3 py-1.5 text-xs font-bold text-amber-300"
                              data-deadline="{{ $a->due_date->toIso8601String() }}">{{ $a->due_date->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="glass-card-3d p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 border border-white/10 mb-3">
                <svg class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-400">Không có bài tập nào phù hợp.</p>
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
