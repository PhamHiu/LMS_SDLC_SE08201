@extends('layouts.app')

@section('title', 'Bảng điểm')

@section('content')

{{-- Thanh bộ lọc mới --}}
<div class="mb-6 inline-block max-w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
    <form method="GET" action="{{ route('student.grades.index') }}" id="grade-filter-form" class="flex flex-wrap items-center gap-4">
        <div class="w-full sm:w-64">
            <label class="sr-only">Chọn lớp học</label>
            <select name="class_id" onchange="document.getElementById('grade-filter-form').submit()" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">Tất cả các lớp</option>
                @foreach ($enrolledClasses as $cls)
                    <option value="{{ $cls->class_id }}" @selected($selectedClassId == $cls->class_id)>
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
            <input type="text" name="search" value="{{ $searchQuery ?? '' }}" placeholder="Tìm theo mã môn, tên môn, bài tập..." oninput="debounceSearch()" class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all">
        </div>
        <a href="{{ route('student.grades.index') }}" class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition-all hover:bg-slate-100 hover:text-indigo-600" title="Làm mới">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </a>
    </form>
</div>

<div class="space-y-6">
    @forelse ($byClass as $item)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden transition-all hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">{{ $item['class']->class_name }}</h2>
                    <p class="text-xs font-semibold text-slate-500 mt-1">{{ $item['class']->subject?->subject_name }}</p>
                </div>
                <span class="rounded-lg bg-indigo-50 px-4 py-2 text-sm font-extrabold text-indigo-700 shadow-sm border border-indigo-100">Điểm TB: {{ $item['avg'] ?: '—' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white text-left text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3">Bài tập</th>
                            <th class="px-5 py-3 w-28 text-center">Loại</th>
                            <th class="px-5 py-3 w-36">Nộp lúc</th>
                            <th class="px-5 py-3 w-28 text-center">Điểm</th>
                            <th class="px-5 py-3 max-w-xs">Nhận xét</th>
                            <th class="px-5 py-3 w-24 text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item['submissions'] as $sub)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-700">{{ $sub->assignment?->title }}</p>
                                    @if ($sub->submit_file_path)
                                        <a href="{{ asset('storage/'.$sub->submit_file_path) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-indigo-500 hover:text-indigo-700">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                            File bài làm
                                        </a>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $sub->assignment?->typeLabel() }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs font-medium text-slate-500">{{ $sub->submitted_at?->format('H:i d/m/Y') }}</td>
                                <td class="px-5 py-4 text-center">
                                    @if ($sub->score !== null)
                                        <span class="inline-block rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1.5 text-xs font-extrabold text-emerald-800 shadow-sm">{{ $sub->score }}/{{ $sub->assignment?->max_score }}</span>
                                    @else
                                        <span class="inline-block rounded-full border border-amber-200 bg-amber-100 px-3 py-1.5 text-[11px] font-bold text-amber-800 shadow-sm">Chờ chấm</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-600 max-w-xs"><span class="whitespace-pre-line line-clamp-2">{{ $sub->feedback ?? '—' }}</span></td>
                                <td class="px-5 py-4 text-center">
                                    <a href="{{ route('student.assignments.show', $sub->assignment) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-500 shadow-sm hover:border-indigo-500 hover:bg-indigo-50 hover:text-indigo-600 transition-all" title="Chi tiết bài làm">
                                        <span class="text-xs font-bold mr-1 hidden sm:inline-block">Chi tiết</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-3">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-600">Bạn chưa có điểm bài tập nào phù hợp.</p>
        </div>
    @endforelse
</div>

<script>
    let timeout = null;
    function debounceSearch() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            document.getElementById('grade-filter-form').submit();
        }, 1000);
    }
</script>
@endsection
