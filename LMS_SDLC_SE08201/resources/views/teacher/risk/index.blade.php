@extends('layouts.app')

@section('title', 'Cảnh báo Nguy cơ Học tập — Giảng viên')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <span>⚠️</span> Hệ thống Cảnh báo Sớm (Early Warning System)
        </h1>
        <p class="text-sm text-slate-500 mt-1">Phân tích nguy cơ bỏ học dựa trên chuyên cần (quy tắc 20%), điểm số và lịch sử học tập</p>
    </div>

    {{-- Chọn lớp học --}}
    <form method="GET" action="{{ route('teacher.risk.index') }}" class="flex items-center gap-2">
        <label for="class_id" class="text-xs font-bold uppercase tracking-wider text-slate-600">Lớp:</label>
        <select name="class_id" id="class_id" onchange="this.form.submit()" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none">
            @foreach($classes as $c)
                <option value="{{ $c->class_id }}" {{ $selectedClassId == $c->class_id ? 'selected' : '' }}>
                    {{ $c->class_name }} — {{ $c->subject?->subject_name ?? '—' }}
                </option>
            @endforeach
        </select>
    </form>
</div>

@if($summary)
    {{-- Thống kê tổng quan lớp --}}
    @php
        $studentsData = collect($summary['students_data']);
        $safeCount = $studentsData->filter(fn($s) => $s['overall_risk']->risk_level === 'SAFE')->count();
        $mediumCount = $studentsData->filter(fn($s) => $s['overall_risk']->risk_level === 'MEDIUM')->count();
        $highCount = $studentsData->filter(fn($s) => in_array($s['overall_risk']->risk_level, ['HIGH', 'VERY_HIGH']))->count();
        $failedAttendanceCount = $studentsData->filter(fn($s) => $s['subject_detail']['attendance_failed'])->count();
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sĩ số lớp</span>
            <p class="mt-2 text-3xl font-black text-slate-800">{{ $summary['students_count'] }} <span class="text-sm font-normal text-slate-500">sinh viên</span></p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Học tập An toàn</span>
            <p class="mt-2 text-3xl font-black text-emerald-800">{{ $safeCount }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Cần theo dõi (Trung bình)</span>
            <p class="mt-2 text-3xl font-black text-amber-800">{{ $mediumCount }}</p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Nguy cơ Cao / Cấm thi</span>
            <p class="mt-2 text-3xl font-black text-rose-800">{{ $highCount }}</p>
            @if($failedAttendanceCount > 0)
                <p class="text-[11px] text-rose-600 mt-1 font-semibold">⚠️ {{ $failedAttendanceCount }} sinh viên đã nghỉ > 20%</p>
            @endif
        </div>
    </div>

    {{-- HỆ THỐNG BIỂU ĐỒ PHÂN TÍCH NGUYÊN NHÂN NGHỈ HỌC (PIE / LINE / COLUMN) --}}
    @include('components.risk-analytics-charts', ['charts' => $charts ?? null])

    {{-- Bảng danh sách sinh viên --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4 flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Danh sách theo dõi học tập lớp {{ $summary['class']->class_name }}</h2>
            <span class="text-xs text-slate-500">Môn: <strong>{{ $summary['class']->subject?->subject_name ?? '—' }}</strong> (Tổng {{ $summary['class']->total_sessions }} buổi)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Mã SV</th>
                        <th class="px-6 py-3.5">Họ và tên</th>
                        <th class="px-6 py-3.5 text-center">Chuyên cần môn này</th>
                        <th class="px-6 py-3.5 text-center">Tỷ lệ vắng</th>
                        <th class="px-6 py-3.5 text-center">Điểm TB</th>
                        <th class="px-6 py-3.5 text-center">Mức cảnh báo AI</th>
                        <th class="px-6 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($summary['students_data'] as $row)
                        @php
                            $sub = $row['subject_detail'];
                            $risk = $row['overall_risk'];
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800">{{ $row['student']->student_code ?? 'HL'.str_pad($row['student']->user_id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800">{{ $row['student']->full_name }}</div>
                                <div class="text-xs text-slate-400">{{ $row['student']->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-slate-700">{{ $sub['absent_sessions'] }}</span> / {{ $sub['total_sessions'] }} buổi
                                @if($sub['attendance_failed'])
                                    <span class="block text-[11px] font-bold text-rose-600">Trượt chuyên cần (>20%)</span>
                                @elseif($sub['absence_rate'] >= 15.0)
                                    <span class="block text-[11px] font-semibold text-amber-600">Tiệm cận 20%</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $sub['absence_rate'] > 20.0 ? 'bg-rose-100 text-rose-800' : ($sub['absence_rate'] >= 15.0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $sub['absence_rate'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-bold {{ $sub['average_score'] !== null && $sub['average_score'] < 5.0 ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $sub['average_score'] !== null ? number_format($sub['average_score'], 1) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $risk->badgeColorClass() }}">
                                    <span>{{ match($risk->risk_level) { 'SAFE' => '🟢', 'MEDIUM' => '🟡', 'HIGH' => '🟠', 'VERY_HIGH' => '🔴', default => '⚪' } }}</span>
                                    {{ $risk->levelLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('teacher.risk.show', $row['student']) }}" class="inline-flex items-center gap-1 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                    <span>Xem chi tiết & Hỗ trợ</span>
                                    <span>→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">Lớp học này chưa có sinh viên nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500">
        <span class="text-4xl mb-2 block">🏫</span>
        <p>Bạn chưa được phân công phụ trách lớp học nào.</p>
    </div>
@endif
@endsection

