@extends('layouts.app')

@section('title', 'Lịch sử điểm danh')

@section('content')
<div class="space-y-6">

    {{-- Tiêu đề trang (High contrast, text-white & gradient) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-[#1e4f8f]/10 border border-[#1e4f8f]/30">
                    <svg class="w-5 h-5 text-[#1e4f8f] filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-wide">
                        Lịch sử điểm danh cá nhân
                    </h1>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Theo dõi tình trạng chuyên cần từng buổi học, bám sát điều kiện chuyên cần tối thiểu <strong class="text-amber-600">80%</strong> để đủ điều kiện thi.
                    </p>
                </div>
            </div>
        </div>
        <div class="rounded-md bg-slate-50 border border-slate-200 px-3.5 py-2 text-xs text-slate-600 shadow-sm">
            Sinh viên: <strong class="text-slate-900 font-bold">{{ auth()->user()->full_name }}</strong> <span class="text-slate-500">({{ auth()->user()->code ?? auth()->user()->email }})</span>
        </div>
    </div>

    {{-- Thống kê chuyên cần theo bộ lọc hiện tại (4 KPI Cards Nổi Bật, Độ Tương Phản Cực Cao) --}}
    @if(isset($summary))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {{-- Tổng số buổi học --}}
            <div class="glass-card p-5 border border-slate-200 shadow-sm rounded-lg group transition-all duration-300">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tổng số buổi học</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 border border-blue-200 text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900 mt-2">
                    {{ $summary['total'] }} <span class="text-sm font-semibold text-slate-500">buổi</span>
                </p>
                <p class="text-[11px] font-medium text-slate-500 mt-1">{{ $selectedClassId ? 'Đang lọc theo lớp' : 'Toàn bộ các lớp' }}</p>
            </div>

            {{-- Có mặt đúng giờ --}}
            <div class="glass-card p-5 border border-emerald-200 bg-emerald-50 shadow-sm rounded-lg group transition-all duration-300">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Có mặt đúng giờ</p>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">P</span>
                </div>
                <p class="text-3xl font-bold text-emerald-600 mt-2">
                    {{ $summary['present'] }} <span class="text-sm font-bold text-emerald-700">({{ $summary['rate'] }}%)</span>
                </p>
                <p class="text-[11px] font-medium text-emerald-700 mt-1">Chuyên cần tốt</p>
            </div>

            {{-- Vắng mặt --}}
            <div class="glass-card p-5 border border-rose-200 bg-rose-50 shadow-sm rounded-lg group transition-all duration-300">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-rose-700 uppercase tracking-wider">Vắng mặt</p>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">A</span>
                </div>
                <p class="text-3xl font-bold text-rose-600 mt-2">
                    {{ $summary['absent'] }} <span class="text-sm font-semibold text-rose-700">buổi</span>
                </p>
                <p class="text-[11px] font-medium text-rose-700 mt-1">{{ $summary['absent'] > 0 ? 'Cần lưu ý giữ chuyên cần' : 'Chưa vắng buổi nào' }}</p>
            </div>

            {{-- Đi muộn --}}
            <div class="glass-card p-5 border border-amber-200 bg-amber-50 shadow-sm rounded-lg group transition-all duration-300">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Đi muộn</p>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 border border-amber-200 text-xs font-bold text-amber-700">L</span>
                </div>
                <p class="text-3xl font-bold text-amber-600 mt-2">
                    {{ $summary['late'] }} <span class="text-sm font-semibold text-amber-700">buổi</span>
                </p>
                <p class="text-[11px] font-medium text-amber-700 mt-1">Vào lớp sau giờ điểm danh</p>
            </div>
        </div>
    @endif

    {{-- Thanh bộ lọc (High contrast Dark Glassmorphism) --}}
    <div class="glass-card p-5 border border-slate-200 shadow-sm rounded-lg">
        <form method="GET" action="{{ route('student.attendance.index') }}" id="attendance-filter-form" class="flex flex-wrap items-center gap-4">
            <div class="w-full sm:w-80">
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Lọc theo Lớp học:</span>
                </label>
                <select name="class_id" onchange="document.getElementById('attendance-filter-form').submit()" class="w-full rounded-md border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-1 focus:ring-[#1e4f8f] cursor-pointer">
                    <option value="" class="bg-slate-50 text-slate-900">Tất cả các lớp (Tổng hợp toàn khóa)</option>
                    @foreach($enrolledClasses as $cls)
                        <option value="{{ $cls->class_id }}" {{ $selectedClassId == $cls->class_id ? 'selected' : '' }} class="bg-slate-900 text-white">
                            {{ $cls->class_name }} — {{ $cls->subject->subject_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-80 relative">
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Tìm kiếm:</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Tìm theo ngày, tên lớp, trạng thái..." oninput="debounceSearch()" class="w-full rounded-md border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-1 focus:ring-[#1e4f8f] transition-all">
                </div>
            </div>

            <div class="pt-0 sm:pt-6">
                <a href="{{ route('student.attendance.index') }}" class="flex h-[42px] px-4 items-center gap-2 rounded-md border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700 transition-all hover:bg-slate-100 hover:border-slate-200 hover:text-[#163c6d]" title="Đặt lại bộ lọc về tất cả các lớp">
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <span>Đặt lại</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Bảng dữ liệu điểm danh theo Tuần (Độ tương phản cao, chữ trắng rõ ràng, không trùng màu) --}}
    <div class="glass-card border border-slate-200 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4 border-r border-slate-200 w-44 text-center">Tuần học</th>
                        <th class="px-5 py-4 w-36">Ngày</th>
                        <th class="px-5 py-4">Lớp / Buổi học</th>
                        <th class="px-5 py-4 w-40 text-center">Trạng thái</th>
                        <th class="px-5 py-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @php $hasData = false; @endphp
                    @foreach ($weeks as $week)
                        @php $rowCount = $week['records']->count(); @endphp
                        @if($rowCount > 0)
                            @php $hasData = true; @endphp
                            @foreach ($week['records'] as $att)
                                <tr class="hover:bg-slate-100 transition-colors">
                                    {{-- Cột Tuần học gộp hàng --}}
                                    @if($loop->first)
                                        <td rowspan="{{ $rowCount }}" class="border-r border-slate-200 px-4 py-5 align-top bg-slate-50 text-center">
                                            <p class="font-bold text-slate-900 text-base">
                                                {{ $week['week_start']->format('d/m') }} <span class="text-blue-600 font-bold">→</span> {{ $week['week_end']->format('d/m') }}
                                            </p>
                                            <div class="mt-2.5 flex flex-col gap-1.5 text-[11px] font-bold">
                                                <span class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-emerald-700">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 filter inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>{{ $week['present'] }} có mặt</span>
                                                </span>
                                                @if($week['absent'] > 0)
                                                    <span class="inline-flex items-center justify-center gap-1.5 rounded-md bg-rose-50 border border-rose-200 px-2 py-0.5 text-rose-700">
                                                        <svg class="w-3.5 h-3.5 text-rose-600 filter inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        <span>{{ $week['absent'] }} vắng</span>
                                                    </span>
                                                @endif
                                                @if($week['late'] > 0)
                                                    <span class="inline-flex items-center justify-center gap-1.5 rounded-md bg-amber-50 border border-amber-200 px-2 py-0.5 text-amber-700">
                                                        <svg class="w-3.5 h-3.5 text-amber-600 filter inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span>{{ $week['late'] }} muộn</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                    
                                    {{-- Cột Ngày (Chữ trắng sáng rõ nét) --}}
                                    <td class="px-5 py-4 font-mono font-bold text-blue-700 whitespace-nowrap">
                                        {{ $att->session?->session_date?->isoFormat('dd DD/MM/YYYY') }}
                                    </td>

                                    {{-- Cột Lớp / Môn học (Chữ trắng sáng, không chìm vào nền) --}}
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900 text-sm tracking-wide">
                                            {{ $att->session?->classRoom?->class_name }}
                                            <span class="text-xs font-semibold text-slate-600 ml-1">({{ $att->session?->classRoom?->subject?->subject_name ?? '' }})</span>
                                        </p>
                                        <div class="text-xs font-medium text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ $att->session?->slot?->label ?? 'Ca học' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span>Phòng: <strong class="text-slate-900">{{ $att->session?->room?->room_name ?? '—' }}</strong></span>
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Cột Trạng thái (Huy hiệu phát sáng rực rỡ, phân biệt tuyệt đối) --}}
                                    <td class="px-5 py-4 text-center">
                                        @if($att->status === 'P')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3.5 py-1 text-xs font-bold text-emerald-700">
                                                <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>Có mặt</span>
                                            </span>
                                        @elseif($att->status === 'A')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 border border-rose-200 px-3.5 py-1 text-xs font-bold text-rose-700">
                                                <svg class="h-3.5 w-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Vắng mặt</span>
                                            </span>
                                        @elseif($att->status === 'L')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3.5 py-1 text-xs font-bold text-amber-700">
                                                <svg class="h-3.5 w-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Đi muộn</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-600">
                                                {{ $att->statusLabel() }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Cột Ghi chú (Chữ sáng dễ đọc) --}}
                                    <td class="px-5 py-4 text-xs font-medium text-slate-600">
                                        {{ $att->note ?? 'Có mặt đúng giờ' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                    
                    @if(!$hasData)
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="inline-flex h-16 w-16 items-center justify-center rounded-lg bg-slate-50 border border-slate-200 mb-3 shadow-sm">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-base font-bold text-slate-900">Không tìm thấy dữ liệu điểm danh phù hợp.</p>
                                <p class="text-xs text-slate-500 mt-1">Hãy thử thay đổi từ khóa tìm kiếm hoặc chọn lớp khác.</p>
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
