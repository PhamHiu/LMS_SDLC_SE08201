@extends('layouts.app')

@section('title', 'Thống kê Số buổi Nghỉ học theo Môn — Giảng viên')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Tỷ lệ nghỉ học của học sinh theo môn</h1>
        <p class="text-sm text-slate-500 mt-1">Thống kê số buổi nghỉ của học sinh theo lớp / môn học. Quy chế: nghỉ quá <strong>20%</strong> số buổi sẽ bị <strong>trượt môn</strong>.</p>
    </div>

    {{-- Chọn lớp học --}}
    @if($classes->isNotEmpty())
        <form method="GET" action="{{ route('teacher.risk.index') }}" class="flex items-center gap-2">
            <label for="class_id" class="text-xs font-bold uppercase tracking-wider text-slate-600">Lớp / Môn:</label>
            <select name="class_id" id="class_id" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm focus:border-[#1e4f8f] focus:outline-none">
                @foreach($classes as $c)
                    <option value="{{ $c->class_id }}" {{ (int) $selectedClassId === (int) $c->class_id ? 'selected' : '' }}>
                        {{ $c->class_name }} — {{ $c->subject?->subject_name ?? '—' }}
                    </option>
                @endforeach
            </select>
        </form>
    @endif
</div>

@if($report)
    {{-- 4 THẺ THỐNG KÊ TỔNG QUAN LỚP --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Sĩ số lớp</span>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $report['counts']['total'] ?? 0 }} <span class="text-sm font-normal text-slate-500">học sinh</span></p>
            <p class="text-xs text-slate-500 mt-1">Tổng {{ $report['class']->total_sessions }} buổi học</p>
        </div>

        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Đạt chuyên cần (&lt; 15%)</span>
            <p class="mt-2 text-3xl font-bold text-emerald-800">{{ $report['counts']['SAFE'] ?? 0 }}</p>
            <p class="text-xs text-emerald-700 mt-1">Nghỉ dưới 15% tổng số buổi</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Sắp trượt môn (15% – 20%)</span>
            <p class="mt-2 text-3xl font-bold text-amber-800">{{ $report['counts']['WARNING'] ?? 0 }}</p>
            <p class="text-xs text-amber-700 mt-1">Cần nhắc nhở, không nghỉ thêm</p>
        </div>

        <div class="rounded-lg border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Trượt môn (&gt; 20%)</span>
            <p class="mt-2 text-3xl font-bold text-rose-800">{{ $report['counts']['FAILED'] ?? 0 }}</p>
            <p class="text-xs text-rose-700 mt-1">Đã nghỉ vượt quá 20% số buổi</p>
        </div>
    </div>

    {{-- BỘ LỌC TRẠNG THÁI & TÌM KIẾM --}}
    <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('teacher.risk.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

            <div class="min-w-[200px]">
                <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Trạng thái</label>
                <select name="status" id="status" onchange="this.form.submit()" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-[#1e4f8f] focus:outline-none">
                    <option value="ALL" {{ $selectedStatus === 'ALL' ? 'selected' : '' }}>Tất cả ({{ $report['counts']['total'] ?? 0 }})</option>
                    <option value="FAILED" {{ $selectedStatus === 'FAILED' ? 'selected' : '' }}>Trượt môn &gt; 20% ({{ $report['counts']['FAILED'] ?? 0 }})</option>
                    <option value="WARNING" {{ $selectedStatus === 'WARNING' ? 'selected' : '' }}>Sắp trượt 15%–20% ({{ $report['counts']['WARNING'] ?? 0 }})</option>
                    <option value="SAFE" {{ $selectedStatus === 'SAFE' ? 'selected' : '' }}>Đạt &lt; 15% ({{ $report['counts']['SAFE'] ?? 0 }})</option>
                </select>
            </div>

            <div class="min-w-[220px] flex-1">
                <label for="q" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Tìm học sinh</label>
                <input type="text" name="q" id="q" value="{{ $search }}" placeholder="Nhập mã SV hoặc họ tên..." class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-[#1e4f8f] focus:outline-none">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2 text-sm font-semibold text-white transition">Lọc</button>
                @if($selectedStatus !== 'ALL' || $search)
                    <a href="{{ route('teacher.risk.index', ['class_id' => $selectedClassId]) }}" class="rounded-md border border-slate-300 bg-white hover:bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 transition">Đặt lại</a>
                @endif
            </div>
        </form>
    </div>

    {{-- BẢNG DANH SÁCH SỐ BUỔI NGHỈ CỦA LỚP --}}
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-bold text-slate-900">Danh sách số buổi nghỉ lớp {{ $report['class']->class_name }}</h2>
            <span class="text-xs text-slate-600">
                Môn: <strong>{{ $report['class']->subject?->subject_name ?? '—' }}</strong>
                · Tổng {{ $report['class']->total_sessions }} buổi (Nghỉ tối đa {{ (int) floor(max(1, (int) $report['class']->total_sessions) * 0.2) }} buổi)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Mã SV</th>
                        <th class="px-5 py-3.5">Họ và tên</th>
                        <th class="px-5 py-3.5 text-center">Có mặt</th>
                        <th class="px-5 py-3.5 text-center">Đi muộn</th>
                        <th class="px-5 py-3.5 text-center">Số buổi nghỉ</th>
                        <th class="px-5 py-3.5 text-center">Tỷ lệ nghỉ</th>
                        <th class="px-5 py-3.5 text-center">Trạng thái</th>
                        <th class="px-5 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($report['rows'] as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-slate-900">{{ $row['student_code'] }}</td>
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $row['full_name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $row['email'] }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-center font-semibold text-emerald-700">{{ $row['present_sessions'] }}</td>
                            <td class="px-5 py-3.5 text-center font-semibold text-amber-700">{{ $row['late_sessions'] }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="font-bold {{ $row['status'] === 'FAILED' ? 'text-rose-700' : ($row['status'] === 'WARNING' ? 'text-amber-700' : 'text-slate-900') }}">
                                    {{ $row['absent_sessions'] }} / {{ $row['total_sessions'] }} buổi
                                </span>
                                <div class="text-[11px] text-slate-500">Tối đa: {{ $row['max_allowed_absences'] }} buổi</div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $row['status'] === 'FAILED' ? 'bg-rose-100 text-rose-800' : ($row['status'] === 'WARNING' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ number_format($row['absence_rate'], 1) }}%
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($row['status'] === 'FAILED')
                                    <span class="inline-flex rounded-full border border-rose-300 bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Trượt môn (&gt;20%)</span>
                                @elseif($row['status'] === 'WARNING')
                                    <span class="inline-flex rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Sắp trượt (15%–20%)</span>
                                @else
                                    <span class="inline-flex rounded-full border border-emerald-300 bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Đạt chuyên cần</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('teacher.risk.show', $row['student']) }}" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-bold text-[#1e4f8f] hover:bg-blue-100 transition">
                                    <span>Chi tiết</span>
                                    <span>→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-500">Không có học sinh nào phù hợp bộ lọc.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rounded-lg border border-slate-200 bg-white p-12 text-center text-slate-500">
        <p>Bạn chưa được phân công phụ trách lớp học nào.</p>
    </div>
@endif
@endsection
