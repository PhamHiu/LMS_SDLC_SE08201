@extends('layouts.app')

@section('title', 'Lịch sử điểm danh')

@section('content')
<div class="space-y-6">

    {{-- Thanh bộ lọc --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
        <form method="GET" action="{{ route('student.attendance.index') }}" id="attendance-filter-form" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="sr-only">Chọn lớp</label>
                <select name="class_id" onchange="document.getElementById('attendance-filter-form').submit()" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
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
                <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Tìm theo ngày, lớp, trạng thái..." oninput="debounceSearch()" class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all">
            </div>
        </form>
    </div>

    {{-- Bảng dữ liệu --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4 border-r border-slate-200 w-40 text-center">Tuần</th>
                        <th class="px-5 py-4">Ngày</th>
                        <th class="px-5 py-4">Lớp / Buổi học</th>
                        <th class="px-5 py-4">Trạng thái</th>
                        <th class="px-5 py-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $hasData = false; @endphp
                    @foreach ($weeks as $week)
                        @php $rowCount = $week['records']->count(); @endphp
                        @if($rowCount > 0)
                            @php $hasData = true; @endphp
                            @foreach ($week['records'] as $index => $att)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    @if($index === 0)
                                        <td rowspan="{{ $rowCount }}" class="border-r border-slate-200 px-4 py-5 align-top bg-slate-50/30 text-center">
                                            <p class="font-extrabold text-slate-800 text-sm">{{ $week['week_start']->format('d/m') }} <span class="text-slate-400 font-medium">→</span> {{ $week['week_end']->format('d/m') }}</p>
                                            <div class="mt-4 inline-flex flex-col gap-2 text-xs text-left font-semibold">
                                                <span class="rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 py-1.5 text-emerald-700 flex justify-between gap-3 shadow-sm">
                                                    <span>✅ Có mặt</span> <span>{{ $week['present'] }}</span>
                                                </span>
                                                <span class="rounded-lg border border-amber-100 bg-amber-50 px-2.5 py-1.5 text-amber-700 flex justify-between gap-3 shadow-sm">
                                                    <span>🕐 Muộn</span> <span>{{ $week['late'] }}</span>
                                                </span>
                                                <span class="rounded-lg border border-rose-100 bg-rose-50 px-2.5 py-1.5 text-rose-700 flex justify-between gap-3 shadow-sm">
                                                    <span>❌ Vắng</span> <span>{{ $week['absent'] }}</span>
                                                </span>
                                            </div>
                                        </td>
                                    @endif
                                    
                                    <td class="px-5 py-4 text-slate-600 font-semibold whitespace-nowrap">{{ $att->session?->session_date?->isoFormat('dd DD/MM') }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-800 text-sm">{{ $att->session?->classRoom?->class_name }}</p>
                                        <p class="text-xs font-medium text-slate-500 mt-1">{{ $att->session?->slot?->label ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        @php
                                            $statusClass = match($att->status) {
                                                'P' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'L' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'A' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                default => 'bg-slate-100 text-slate-800 border-slate-200'
                                            };
                                        @endphp
                                        <span class="inline-block rounded-full border px-3 py-1.5 text-xs font-bold shadow-sm {{ $statusClass }}">{{ $att->statusLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-xs font-medium text-slate-500">{{ $att->note ?? '—' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                    
                    @if(!$hasData)
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-3">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-base font-semibold text-slate-600">Không tìm thấy dữ liệu điểm danh phù hợp.</p>
                                <p class="text-sm text-slate-400 mt-1">Hãy thử thay đổi từ khóa tìm kiếm hoặc chọn lớp khác.</p>
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
        }, 400);
    }
</script>
@endsection
