@extends('layouts.app')

@section('title', 'Chi tiết Nghỉ học theo Môn — ' . $student->full_name)

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <a href="{{ route('teacher.risk.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-[#163c6d] transition mb-2">
            <span>←</span> Quay lại danh sách lớp
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Chi tiết số buổi nghỉ học theo môn</h1>
        <p class="text-sm text-slate-600 mt-1">
            Học sinh: <strong class="text-slate-900">{{ $student->full_name }}</strong>
            · Mã SV: <span class="font-mono font-bold text-[#1e4f8f]">{{ $student->student_code ?? 'HL'.str_pad($student->user_id, 5, '0', STR_PAD_LEFT) }}</span>
            · Email: {{ $student->email }}
        </p>
    </div>

    <div class="flex flex-wrap gap-2 text-xs font-bold">
        <span class="rounded-md border border-slate-200 bg-white px-3 py-2 text-slate-700">Tổng môn: {{ $breakdown['summary']['total_subjects'] }}</span>
        <span class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700">Trượt môn (&gt;20%): {{ $breakdown['summary']['failed_subjects'] }}</span>
        <span class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-amber-700">Sắp trượt: {{ $breakdown['summary']['warning_subjects'] }}</span>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        {{-- BẢNG SỐ BUỔI NGHỈ THEO TỪNG MÔN HỌC --}}
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Thống kê chuyên cần từng môn học</h2>
                <span class="text-xs text-slate-500">Nghỉ quá 20% → Trượt môn</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3">Môn học / Lớp</th>
                            <th class="px-5 py-3 text-center">Tổng buổi</th>
                            <th class="px-5 py-3 text-center">Có mặt / Muộn</th>
                            <th class="px-5 py-3 text-center">Nghỉ học</th>
                            <th class="px-5 py-3 text-center">Tỷ lệ</th>
                            <th class="px-5 py-3 text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($breakdown['subjects'] as $sub)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ $sub['subject_name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $sub['subject_code'] }} · Lớp {{ $sub['class_name'] }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-800">{{ $sub['total_sessions'] }}</td>
                                <td class="px-5 py-3.5 text-center text-xs text-slate-600">
                                    <span class="font-semibold text-emerald-700">{{ $sub['present_sessions'] }}</span> /
                                    <span class="font-semibold text-amber-700">{{ $sub['late_sessions'] }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="font-bold {{ $sub['status'] === 'FAILED' ? 'text-rose-700' : 'text-slate-900' }}">{{ $sub['absent_sessions'] }}</span>
                                    <div class="text-[11px] text-slate-400">Tối đa {{ $sub['max_allowed_absences'] }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $sub['status'] === 'FAILED' ? 'bg-rose-100 text-rose-800' : ($sub['status'] === 'WARNING' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ number_format($sub['absence_rate'], 1) }}%
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($sub['status'] === 'FAILED')
                                        <span class="inline-flex rounded-full border border-rose-300 bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800">Trượt môn (&gt;20%)</span>
                                    @elseif($sub['status'] === 'WARNING')
                                        <span class="inline-flex rounded-full border border-amber-300 bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Sắp trượt</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-emerald-300 bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Đạt</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">Chưa có dữ liệu môn học.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- LỊCH SỬ CÁC BUỔI ĐÃ NGHỈ --}}
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                <h2 class="font-bold text-slate-900">Lịch sử các buổi đã nghỉ ({{ $breakdown['absent_history']->count() }} buổi gần nhất)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3">Ngày học</th>
                            <th class="px-5 py-3">Môn học / Lớp</th>
                            <th class="px-5 py-3">Ghi chú</th>
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
                                <td colspan="3" class="px-5 py-6 text-center text-slate-500 text-xs">Chưa có buổi nghỉ nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- LỊCH SỬ GHI CHÚ NHẮC NHỞ / HỖ TRỢ --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Lịch sử nhắc nhở &amp; hỗ trợ chuyên cần</h2>
            <div class="space-y-3">
                @forelse($interventions as $item)
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-[#1e4f8f]">{{ $item->actionLabel() }}</span>
                            <span class="text-slate-500">{{ $item->created_at->format('H:i d/m/Y') }}</span>
                        </div>
                        <p class="text-sm text-slate-700">{{ $item->notes }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200">
                            <span>Giảng viên: <strong>{{ $item->teacher?->full_name ?? 'Hệ thống' }}</strong></span>
                            <span class="font-semibold {{ $item->status === 'RESOLVED' ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $item->status === 'RESOLVED' ? 'Đã khắc phục' : ($item->status === 'IN_PROGRESS' ? 'Đang theo dõi' : 'Chờ phản hồi') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">Chưa có ghi chú nhắc nhở nào.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CỘT PHẢI: FORM GHI NHẬN NHẮC NHỞ CHUYÊN CẦN --}}
    <div class="lg:col-span-1">
        <div class="sticky top-20 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Ghi nhận nhắc nhở / hỗ trợ</h2>

            <form action="{{ route('teacher.risk.intervene', $student) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Hình thức</label>
                    <select name="action_type" required class="w-full rounded-md border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#1e4f8f] focus:outline-none">
                        <option value="ATTENDANCE_REMINDER">Nhắc nhở chuyên cần (tránh vượt 20%)</option>
                        <option value="ACADEMIC_TUTORING">Hỗ trợ ôn tập / Bù kiến thức</option>
                        <option value="COUNSELING">Trao đổi trực tiếp với học sinh</option>
                        <option value="OTHER">Khác</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Trạng thái</label>
                    <select name="status" required class="w-full rounded-md border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#1e4f8f] focus:outline-none">
                        <option value="IN_PROGRESS">Đang theo dõi</option>
                        <option value="RESOLVED">Đã khắc phục</option>
                        <option value="PENDING">Chờ phản hồi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nội dung ghi chú</label>
                    <textarea name="notes" rows="4" required placeholder="Ghi chú nội dung nhắc nhở chuyên cần..." class="w-full rounded-md border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#1e4f8f] focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full rounded-md bg-[#1e4f8f] py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#163c6d] transition">
                    Lưu ghi chú
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
