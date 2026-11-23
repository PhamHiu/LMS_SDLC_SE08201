@extends('layouts.app')

@section('title', 'Chi tiết Nghỉ học theo Môn — ' . $student->full_name)

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <a href="{{ route('admin.risk.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#163c6d] transition mb-2">
            <span>←</span> Quay lại thống kê toàn trường
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Chi tiết số buổi nghỉ học theo môn</h1>
        <p class="text-sm text-slate-600 mt-1">
            Học sinh: <strong class="text-slate-900">{{ $student->full_name }}</strong>
            · Mã SV: <span class="font-mono font-bold text-[#1e4f8f]">{{ $student->student_code ?? 'HL'.str_pad($student->user_id, 5, '0', STR_PAD_LEFT) }}</span>
            · Ngành: {{ $student->major->major_name ?? '—' }}
        </p>
    </div>

    <div class="flex flex-wrap gap-2 text-xs font-bold">
        <span class="rounded-md border border-slate-200 bg-white px-3 py-2 text-slate-700">Tổng môn: {{ $breakdown['summary']['total_subjects'] }}</span>
        <span class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700">Trượt môn (&gt;20%): {{ $breakdown['summary']['failed_subjects'] }}</span>
        <span class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-amber-700">Sắp trượt: {{ $breakdown['summary']['warning_subjects'] }}</span>
    </div>
</div>

{{-- BẢNG SỐ BUỔI NGHỈ THEO TỪNG MÔN HỌC --}}
<div class="mb-6 rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
        <h2 class="font-bold text-slate-900">Thống kê chuyên cần từng môn học</h2>
        <span class="text-xs text-slate-500">Quy chế: nghỉ quá 20% tổng số buổi bị trượt môn</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5">Môn học</th>
                    <th class="px-5 py-3.5">Lớp</th>
                    <th class="px-5 py-3.5 text-center">Tổng buổi</th>
                    <th class="px-5 py-3.5 text-center">Có mặt</th>
                    <th class="px-5 py-3.5 text-center">Đi muộn</th>
                    <th class="px-5 py-3.5 text-center">Số buổi nghỉ</th>
                    <th class="px-5 py-3.5 text-center">Tỷ lệ nghỉ</th>
                    <th class="px-5 py-3.5 text-center">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($breakdown['subjects'] as $sub)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3.5">
                            <div class="font-semibold text-slate-900">{{ $sub['subject_name'] }}</div>
                            <div class="text-xs text-slate-500 font-mono">{{ $sub['subject_code'] }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-700 font-medium">{{ $sub['class_name'] }}</td>
                        <td class="px-5 py-3.5 text-center font-semibold text-slate-800">{{ $sub['total_sessions'] }}</td>
                        <td class="px-5 py-3.5 text-center font-semibold text-emerald-700">{{ $sub['present_sessions'] }}</td>
                        <td class="px-5 py-3.5 text-center font-semibold text-amber-700">{{ $sub['late_sessions'] }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="font-bold {{ $sub['status'] === 'FAILED' ? 'text-rose-700' : 'text-slate-900' }}">{{ $sub['absent_sessions'] }}</span>
                            <span class="text-xs text-slate-400"> (tối đa {{ $sub['max_allowed_absences'] }})</span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $sub['status'] === 'FAILED' ? 'bg-rose-100 text-rose-800' : ($sub['status'] === 'WARNING' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ number_format($sub['absence_rate'], 1) }}%
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($sub['status'] === 'FAILED')
                                <span class="inline-flex rounded-full border border-rose-300 bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Trượt môn (&gt;20%)</span>
                            @elseif($sub['status'] === 'WARNING')
                                <span class="inline-flex rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Sắp trượt (15%–20%)</span>
                            @else
                                <span class="inline-flex rounded-full border border-emerald-300 bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Đạt chuyên cần</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-slate-500">Học sinh chưa ghi danh môn học nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- LỊCH SỬ CÁC BUỔI ĐÃ NGHỈ --}}
    <div class="lg:col-span-2 rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
            <h2 class="font-bold text-slate-900">Lịch sử các buổi đã nghỉ ({{ $breakdown['absent_history']->count() }} buổi gần nhất)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3">Ngày học</th>
                        <th class="px-5 py-3">Môn học / Lớp</th>
                        <th class="px-5 py-3">Ghi chú / Lý do</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($breakdown['absent_history'] as $hist)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-800">{{ \Carbon\Carbon::parse($hist->session_date)->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="font-semibold text-slate-900">{{ $hist->subject_name }}</span>
                                <span class="text-xs text-slate-500">({{ $hist->class_name }})</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $hist->note ?: 'Không có ghi chú' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-center text-slate-500 text-xs">Chưa có buổi nghỉ nào được ghi nhận.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- NHẬT KÝ HỖ TRỢ / NHẮC NHỞ CỦA GV --}}
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-slate-900 mb-3">Lịch sử nhắc nhở / hỗ trợ của GV</h2>
        <div class="space-y-3">
            @forelse($interventions as $item)
                <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs">
                    <div class="flex justify-between font-bold text-[#1e4f8f] mb-1">
                        <span>{{ $item->actionLabel() }}</span>
                        <span class="text-slate-500 font-normal">{{ $item->created_at->format('d/m/Y') }}</span>
                    </div>
                    <p class="text-slate-700 leading-relaxed">{{ $item->notes }}</p>
                    <p class="text-slate-500 mt-1.5">Bởi: {{ $item->teacher?->full_name ?? 'Hệ thống' }}</p>
                </div>
            @empty
                <p class="text-xs text-slate-500 text-center py-4">Chưa có ghi chú hỗ trợ nào.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
