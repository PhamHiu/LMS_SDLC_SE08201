@extends('layouts.app')

@section('title', 'Lịch sử điểm danh')

@section('content')
<div class="space-y-6">

    {{-- Tiêu đề trang (High contrast, text-white & gradient) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1558C9]/20 border border-[#1558C9]/40 shadow-[0_0_15px_rgba(21,88,201,0.4)]">
                    <svg class="w-5 h-5 text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-wide drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                        Lịch sử điểm danh cá nhân
                    </h1>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Theo dõi tình trạng chuyên cần từng buổi học, bám sát điều kiện chuyên cần tối thiểu <strong class="text-amber-400">80%</strong> để đủ điều kiện thi.
                    </p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2 text-xs text-slate-300 shadow-inner">
            Sinh viên: <strong class="text-white font-bold">{{ auth()->user()->full_name }}</strong> <span class="text-slate-400">({{ auth()->user()->code ?? auth()->user()->email }})</span>
        </div>
    </div>

    {{-- Thống kê chuyên cần theo bộ lọc hiện tại (4 KPI Cards Nổi Bật, Độ Tương Phản Cực Cao) --}}
    @if(isset($summary))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {{-- Tổng số buổi học --}}
            <div class="glass-card p-5 border border-white/15 shadow-xl rounded-2xl group transition-all duration-300 hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_15px_30px_rgba(0,0,0,0.4)]">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300 uppercase tracking-wider">Tổng số buổi học</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 border border-indigo-400/30 text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-black text-white mt-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.6)]">
                    {{ $summary['total'] }} <span class="text-sm font-semibold text-slate-400">buổi</span>
                </p>
                <p class="text-[11px] font-medium text-slate-400 mt-1">{{ $selectedClassId ? 'Đang lọc theo lớp' : 'Toàn bộ các lớp' }}</p>
            </div>

            {{-- Có mặt đúng giờ --}}
            <div class="glass-card p-5 border border-emerald-500/30 bg-emerald-950/20 shadow-xl rounded-2xl group transition-all duration-300 hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_15px_30px_rgba(16,185,129,0.25)]">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-emerald-300 uppercase tracking-wider">Có mặt đúng giờ</p>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/30 border border-emerald-400/40 text-xs font-black text-emerald-300 shadow-[0_0_10px_rgba(52,211,153,0.5)]">P</span>
                </div>
                <p class="text-3xl font-black text-emerald-400 mt-2 drop-shadow-[0_0_12px_rgba(52,211,153,0.6)]">
                    {{ $summary['present'] }} <span class="text-sm font-bold text-emerald-300">({{ $summary['rate'] }}%)</span>
                </p>
                <p class="text-[11px] font-medium text-emerald-300/80 mt-1">Chuyên cần tốt</p>
            </div>

            {{-- Vắng mặt --}}
            <div class="glass-card p-5 border border-rose-500/30 bg-rose-950/20 shadow-xl rounded-2xl group transition-all duration-300 hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_15px_30px_rgba(244,63,94,0.25)]">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-rose-300 uppercase tracking-wider">Vắng mặt</p>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-500/30 border border-rose-400/40 text-xs font-black text-rose-300 shadow-[0_0_10px_rgba(244,63,94,0.5)]">A</span>
                </div>
                <p class="text-3xl font-black text-rose-400 mt-2 drop-shadow-[0_0_12px_rgba(244,63,94,0.6)]">
                    {{ $summary['absent'] }} <span class="text-sm font-semibold text-rose-300">buổi</span>
                </p>
                <p class="text-[11px] font-medium text-rose-300/80 mt-1">{{ $summary['absent'] > 0 ? 'Cần lưu ý giữ chuyên cần' : 'Chưa vắng buổi nào' }}</p>
            </div>

            {{-- Đi muộn --}}
            <div class="glass-card p-5 border border-amber-500/30 bg-amber-950/20 shadow-xl rounded-2xl group transition-all duration-300 hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_15px_30px_rgba(245,158,11,0.25)]">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-amber-300 uppercase tracking-wider">Đi muộn</p>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/30 border border-amber-400/40 text-xs font-black text-amber-300 shadow-[0_0_10px_rgba(251,191,36,0.5)]">L</span>
                </div>
                <p class="text-3xl font-black text-amber-400 mt-2 drop-shadow-[0_0_12px_rgba(251,191,36,0.6)]">
                    {{ $summary['late'] }} <span class="text-sm font-semibold text-amber-300">buổi</span>
                </p>
                <p class="text-[11px] font-medium text-amber-300/80 mt-1">Vào lớp sau giờ điểm danh</p>
            </div>
        </div>
    @endif

    {{-- Thanh bộ lọc (High contrast Dark Glassmorphism) --}}
    <div class="glass-card p-5 border border-white/10 shadow-xl rounded-2xl">
        <form method="GET" action="{{ route('student.attendance.index') }}" id="attendance-filter-form" class="flex flex-wrap items-center gap-4">
            <div class="w-full sm:w-80">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Lọc theo Lớp học:</span>
                </label>
                <select name="class_id" onchange="document.getElementById('attendance-filter-form').submit()" class="w-full rounded-xl border border-white/15 bg-slate-800/90 px-3.5 py-2.5 text-sm font-semibold text-white shadow-inner focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                    <option value="" class="bg-slate-900 text-white">Tất cả các lớp (Tổng hợp toàn khóa)</option>
                    @foreach($enrolledClasses as $cls)
                        <option value="{{ $cls->class_id }}" {{ $selectedClassId == $cls->class_id ? 'selected' : '' }} class="bg-slate-900 text-white">
                            {{ $cls->class_name }} — {{ $cls->subject->subject_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-80 relative">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Tìm kiếm:</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Tìm theo ngày, tên lớp, trạng thái..." oninput="debounceSearch()" class="w-full rounded-xl border border-white/15 bg-slate-800/90 pl-9 pr-4 py-2.5 text-sm text-white placeholder-slate-400 shadow-inner focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                </div>
            </div>

            <div class="pt-0 sm:pt-6">
                <a href="{{ route('student.attendance.index') }}" class="flex h-[42px] px-4 items-center gap-2 rounded-xl border border-white/10 bg-white/5 text-xs font-bold text-slate-200 transition-all hover:bg-white/10 hover:border-white/20 hover:text-white" title="Đặt lại bộ lọc về tất cả các lớp">
                    <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <span>Đặt lại</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Bảng dữ liệu điểm danh theo Tuần (Độ tương phản cao, chữ trắng rõ ràng, không trùng màu) --}}
    <div class="glass-card border border-white/10 shadow-2xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-800/95 text-xs font-black uppercase tracking-wider text-slate-200 border-b border-white/10">
                    <tr>
                        <th class="px-5 py-4 border-r border-white/10 w-44 text-center">Tuần học</th>
                        <th class="px-5 py-4 w-36">Ngày</th>
                        <th class="px-5 py-4">Lớp / Buổi học</th>
                        <th class="px-5 py-4 w-40 text-center">Trạng thái</th>
                        <th class="px-5 py-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @php $hasData = false; @endphp
                    @foreach ($weeks as $week)
                        @php $rowCount = $week['records']->count(); @endphp
                        @if($rowCount > 0)
                            @php $hasData = true; @endphp
                            @foreach ($week['records'] as $att)
                                <tr class="hover:bg-white/5 transition-colors">
                                    {{-- Cột Tuần học gộp hàng --}}
                                    @if($loop->first)
                                        <td rowspan="{{ $rowCount }}" class="border-r border-white/10 px-4 py-5 align-top bg-slate-900/80 text-center">
                                            <p class="font-black text-white text-base drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                                                {{ $week['week_start']->format('d/m') }} <span class="text-indigo-400 font-bold">→</span> {{ $week['week_end']->format('d/m') }}
                                            </p>
                                            <div class="mt-2.5 flex flex-col gap-1.5 text-[11px] font-bold">
                                                <span class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-500/20 border border-emerald-400/30 px-2 py-0.5 text-emerald-300">
                                                    <svg class="w-3.5 h-3.5 text-emerald-400 filter drop-shadow-[0_0_4px_rgba(52,211,153,0.8)] inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>{{ $week['present'] }} có mặt</span>
                                                </span>
                                                @if($week['absent'] > 0)
                                                    <span class="inline-flex items-center justify-center gap-1.5 rounded-md bg-rose-500/20 border border-rose-400/30 px-2 py-0.5 text-rose-300">
                                                        <svg class="w-3.5 h-3.5 text-rose-400 filter drop-shadow-[0_0_4px_rgba(244,63,94,0.8)] inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        <span>{{ $week['absent'] }} vắng</span>
                                                    </span>
                                                @endif
                                                @if($week['late'] > 0)
                                                    <span class="inline-flex items-center justify-center gap-1.5 rounded-md bg-amber-500/20 border border-amber-400/30 px-2 py-0.5 text-amber-300">
                                                        <svg class="w-3.5 h-3.5 text-amber-400 filter drop-shadow-[0_0_4px_rgba(251,191,36,0.8)] inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span>{{ $week['late'] }} muộn</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                    
                                    {{-- Cột Ngày (Chữ trắng sáng rõ nét) --}}
                                    <td class="px-5 py-4 font-mono font-bold text-indigo-300 whitespace-nowrap">
                                        {{ $att->session?->session_date?->isoFormat('dd DD/MM/YYYY') }}
                                    </td>

                                    {{-- Cột Lớp / Môn học (Chữ trắng sáng, không chìm vào nền) --}}
                                    <td class="px-5 py-4">
                                        <p class="font-extrabold text-white text-sm tracking-wide">
                                            {{ $att->session?->classRoom?->class_name }}
                                            <span class="text-xs font-semibold text-slate-300 ml-1">({{ $att->session?->classRoom?->subject?->subject_name ?? '' }})</span>
                                        </p>
                                        <div class="text-xs font-medium text-slate-400 mt-1 flex flex-wrap items-center gap-3">
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ $att->session?->slot?->label ?? 'Ca học' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span>Phòng: <strong class="text-white">{{ $att->session?->room?->room_name ?? '—' }}</strong></span>
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Cột Trạng thái (Huy hiệu phát sáng rực rỡ, phân biệt tuyệt đối) --}}
                                    <td class="px-5 py-4 text-center">
                                        @if($att->status === 'P')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/25 border border-emerald-400/50 px-3.5 py-1 text-xs font-black text-emerald-300 shadow-[0_0_12px_rgba(52,211,153,0.35)]">
                                                <svg class="h-3.5 w-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>Có mặt</span>
                                            </span>
                                        @elseif($att->status === 'A')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/25 border border-rose-400/50 px-3.5 py-1 text-xs font-black text-rose-300 shadow-[0_0_12px_rgba(244,63,94,0.35)]">
                                                <svg class="h-3.5 w-3.5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Vắng mặt</span>
                                            </span>
                                        @elseif($att->status === 'L')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/25 border border-amber-400/50 px-3.5 py-1 text-xs font-black text-amber-300 shadow-[0_0_12px_rgba(251,191,36,0.35)]">
                                                <svg class="h-3.5 w-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Đi muộn</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-white/10 border border-white/20 px-3 py-1 text-xs font-bold text-slate-300">
                                                {{ $att->statusLabel() }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Cột Ghi chú (Chữ sáng dễ đọc) --}}
                                    <td class="px-5 py-4 text-xs font-medium text-slate-300">
                                        {{ $att->note ?? 'Có mặt đúng giờ' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                    
                    @if(!$hasData)
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 border border-white/10 mb-3 shadow-inner">
                                    <svg class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-base font-bold text-white">Không tìm thấy dữ liệu điểm danh phù hợp.</p>
                                <p class="text-xs text-slate-400 mt-1">Hãy thử thay đổi từ khóa tìm kiếm hoặc chọn lớp khác.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let timeout = null;
    function debounceSearch() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            document.getElementById('attendance-filter-form').submit();
        }, 800);
    }
</script>
@endsection
