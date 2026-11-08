@extends('layouts.app')

@section('title', 'Bảng điểm & Lịch sử điểm số')

@section('content')
<div class="space-y-6">

    {{-- Tiêu đề trang (High contrast, text-white & gradient) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-yellow-500/20 to-amber-500/20 border border-yellow-400/30 text-yellow-400 shadow-[0_0_15px_rgba(250,204,21,0.3)]">
                    <svg class="w-5 h-5 filter drop-shadow-[0_0_6px_rgba(250,204,21,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-wide drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                        Lịch sử điểm số & Kết quả học tập
                    </h1>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Theo dõi điểm số chi tiết từng bài tập, quiz và điểm trung bình theo từng môn học.
                    </p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2 text-xs text-slate-300 shadow-inner">
            Sinh viên: <strong class="text-white font-bold">{{ auth()->user()->full_name }}</strong> <span class="text-slate-400">({{ auth()->user()->code ?? auth()->user()->email }})</span>
        </div>
    </div>

    {{-- Thanh bộ lọc mới (High contrast Dark Glassmorphism) --}}
    <div class="glass-card p-5 border border-white/10 shadow-xl rounded-2xl">
        <form method="GET" action="{{ route('student.grades.index') }}" id="grade-filter-form" class="flex flex-wrap items-center gap-4">
            <div class="w-full sm:w-80">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Lọc theo Lớp học:</span>
                </label>
                <select name="class_id" onchange="document.getElementById('grade-filter-form').submit()" class="w-full rounded-xl border border-white/15 bg-slate-800/90 px-3.5 py-2.5 text-sm font-semibold text-white shadow-inner focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                    <option value="" class="bg-slate-900 text-white">Tất cả các lớp</option>
                    @foreach ($enrolledClasses as $cls)
                        <option value="{{ $cls->class_id }}" @selected($selectedClassId == $cls->class_id) class="bg-slate-900 text-white">
                            {{ $cls->class_name }} — {{ $cls->subject->subject_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-80 relative">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Tìm kiếm bài tập:</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $searchQuery ?? '' }}" placeholder="Tìm theo mã môn, tên môn, bài tập..." oninput="debounceSearch()" class="w-full rounded-xl border border-white/15 bg-slate-800/90 pl-9 pr-4 py-2.5 text-sm text-white placeholder-slate-400 shadow-inner focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                </div>
            </div>

            <div class="pt-0 sm:pt-6">
                <a href="{{ route('student.grades.index') }}" class="flex h-[42px] px-4 items-center gap-2 rounded-xl border border-white/10 bg-white/5 text-xs font-bold text-slate-200 transition-all hover:bg-white/10 hover:border-white/20 hover:text-white" title="Đặt lại bộ lọc">
                    <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <span>Làm mới</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Danh sách bảng điểm theo từng lớp (Tương phản cao, chữ trắng sáng, phân tách rõ ràng) --}}
    <div class="space-y-6">
        @forelse ($byClass as $item)
            <div class="glass-card border border-white/10 shadow-2xl rounded-2xl overflow-hidden transition-all duration-300 hover:border-indigo-400/40">
                {{-- Header Lớp học & Điểm TB --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 bg-slate-800/85 px-6 py-4.5">
                    <div>
                        <h2 class="font-black text-white text-lg tracking-wide flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(129,140,248,0.8)]"></span>
                            <span>{{ $item['class']->class_name }}</span>
                        </h2>
                        <p class="text-xs font-semibold text-slate-300 mt-0.5">{{ $item['class']->subject?->subject_name }}</p>
                    </div>
                    <span class="rounded-xl bg-gradient-to-r from-indigo-500/25 to-purple-500/25 border border-indigo-400/40 px-4 py-2 text-sm font-black text-indigo-200 shadow-[0_0_15px_rgba(99,102,241,0.3)]">
                        Điểm TB: <strong class="text-white text-base ml-1">{{ $item['avg'] ?: '—' }}</strong>
                    </span>
                </div>

                {{-- Bảng danh sách bài tập của lớp --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-slate-900/90 text-xs font-black uppercase tracking-wider text-slate-300 border-b border-white/10">
                            <tr>
                                <th class="px-6 py-3.5">Bài tập</th>
                                <th class="px-5 py-3.5 w-32 text-center">Loại bài</th>
                                <th class="px-5 py-3.5 w-40">Nộp lúc</th>
                                <th class="px-5 py-3.5 w-32 text-center">Điểm số</th>
                                <th class="px-6 py-3.5 max-w-xs">Nhận xét</th>
                                <th class="px-5 py-3.5 w-28 text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($item['submissions'] as $sub)
                                <tr class="hover:bg-white/5 transition-colors">
                                    {{-- Tên bài tập (Chữ trắng sáng) --}}
                                    <td class="px-6 py-4">
                                        <p class="font-extrabold text-white text-sm tracking-wide">{{ $sub->assignment?->title }}</p>
                                        @if ($sub->submit_file_path)
                                            <a href="{{ asset('storage/'.$sub->submit_file_path) }}" target="_blank" class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                                                <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                <span>Xem file bài làm</span>
                                            </a>
                                        @endif
                                    </td>

                                    {{-- Loại bài (Quiz / Tự luận) --}}
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-block rounded-lg bg-white/10 border border-white/15 px-2.5 py-1 text-[11px] font-bold text-slate-200">
                                            {{ $sub->assignment?->typeLabel() }}
                                        </span>
                                    </td>

                                    {{-- Thời gian nộp (Chữ sáng font-mono) --}}
                                    <td class="px-5 py-4 text-xs font-mono font-medium text-slate-300">
                                        {{ $sub->submitted_at?->format('H:i d/m/Y') }}
                                    </td>

                                    {{-- Điểm số (Huy hiệu phát sáng, chữ cực kỳ rõ) --}}
                                    <td class="px-5 py-4 text-center">
                                        @if ($sub->score !== null)
                                            <span class="inline-block rounded-full border border-emerald-400/40 bg-emerald-500/20 px-3.5 py-1.5 text-xs font-black text-emerald-300 shadow-[0_0_12px_rgba(52,211,153,0.35)]">
                                                {{ $sub->score }} / {{ $sub->assignment?->max_score }}
                                            </span>
                                        @else
                                            <span class="inline-block rounded-full border border-amber-400/40 bg-amber-500/20 px-3.5 py-1.5 text-xs font-bold text-amber-300 shadow-[0_0_10px_rgba(251,191,36,0.35)]">
                                                Chờ chấm
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Nhận xét của giảng viên (Chữ sáng) --}}
                                    <td class="px-6 py-4 text-xs text-slate-300 max-w-xs">
                                        <span class="whitespace-pre-line line-clamp-2">{{ $sub->feedback ?? '—' }}</span>
                                    </td>

                                    {{-- Nút hành động --}}
                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('student.assignments.show', $sub->assignment) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-200 shadow-sm hover:bg-indigo-600 hover:text-white hover:border-indigo-400 transition-all" title="Chi tiết bài làm">
                                            <span>Chi tiết</span>
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            <div class="glass-card rounded-2xl border border-dashed border-white/15 p-16 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 border border-white/10 mb-3 shadow-inner">
                    <svg class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <p class="text-base font-bold text-white">Bạn chưa có điểm bài tập nào phù hợp.</p>
                <p class="text-xs text-slate-400 mt-1">Hãy thử thay đổi tiêu chí tìm kiếm hoặc kiểm tra lại các lớp đang học.</p>
            </div>
        @endforelse
    </div>
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
