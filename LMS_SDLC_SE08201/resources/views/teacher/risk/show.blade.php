@extends('layouts.app')

@section('title', 'Hồ sơ Cảnh báo Học tập — ' . $student->full_name)

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <a href="{{ route('teacher.risk.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-indigo-600 transition mb-2">
            <span>←</span> Quay lại danh sách lớp
        </a>
        <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <span>👤</span> Hồ sơ Cảnh báo & Hỗ trợ Sinh viên
        </h1>
        <p class="text-sm text-slate-500 mt-0.5">Sinh viên: <strong>{{ $student->full_name }}</strong> · Mã SV: <span class="font-mono text-indigo-700 font-bold">{{ $student->student_code ?? 'HL'.str_pad($student->user_id, 5, '0', STR_PAD_LEFT) }}</span> · Email: {{ $student->email }}</p>
    </div>

    <div>
        <span class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-black shadow-sm {{ $prediction->badgeColorClass() }}">
            <span class="text-base">{{ match($prediction->risk_level) { 'SAFE' => '🟢', 'MEDIUM' => '🟡', 'HIGH' => '🟠', 'VERY_HIGH' => '🔴', default => '⚪' } }}</span>
            <span>MỨC ĐỘ: {{ mb_strtoupper($prediction->levelLabel()) }}</span>
        </span>
    </div>
</div>

{{-- BIỂU ĐỒ CHẨN ĐOÁN CÁ NHÂN (PIE / LINE / COLUMN) --}}
@include('components.student-risk-charts', ['studentCharts' => $studentCharts ?? null])

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- CỘT TRÁI: GIẢI THÍCH NGUYÊN NHÂN & CÁC YẾU TỐ RỦI RO (2 CỘT) --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Card Tóm tắt nguyên nhân (Explainable AI) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-3">
                <span>🔍</span> Căn cứ Phân tích & Giải thích Tự động (Rule Engine)
            </h2>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm leading-relaxed text-slate-700">
                <p class="font-semibold text-slate-800">{{ $prediction->summary_explanation }}</p>
                <div class="mt-3 pt-3 border-t border-slate-200 flex flex-wrap gap-4 text-xs text-slate-500">
                    <span>Phương pháp: <strong>{{ $prediction->analysis_method }}</strong></span>
                    <span>Phiên bản: <strong>{{ $prediction->model_version }}</strong></span>
                    <span>Thời điểm: <strong>{{ $prediction->analyzed_at->format('H:i d/m/Y') }}</strong></span>
                </div>
            </div>

            @if($prediction->recommendations)
                <div class="mt-4 rounded-xl bg-indigo-50/70 border border-indigo-200 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-800 mb-1">Khuyến nghị sư phạm từ hệ thống:</h3>
                    <p class="text-sm text-indigo-950 font-medium">{{ $prediction->recommendations }}</p>
                </div>
            @endif
        </div>

        {{-- Card Danh sách các yếu tố rủi ro chi tiết theo môn --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-4">
                <span>📋</span> Chi tiết các Yếu tố Ảnh hưởng (Contributing Factors)
            </h2>

            <div class="space-y-3">
                @forelse($prediction->factors as $factor)
                    <div class="rounded-xl border p-4 transition {{ $factor->severity === 'CRITICAL' ? 'border-rose-200 bg-rose-50/40' : ($factor->severity === 'WARNING' ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-slate-50/50') }}">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span>{{ $factor->severity === 'CRITICAL' ? '🚨' : ($factor->severity === 'WARNING' ? '⚠️' : 'ℹ️') }}</span>
                                {{ $factor->title }}
                            </h3>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $factor->severity === 'CRITICAL' ? 'bg-rose-100 text-rose-700' : ($factor->severity === 'WARNING' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ $factor->severity }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">{{ $factor->description }}</p>
                        @if($factor->subject)
                            <div class="mt-2 text-[11px] font-medium text-slate-500">
                                Môn học: <strong class="text-slate-700">{{ $factor->subject->subject_name }}</strong> ({{ $factor->subject->subject_code }})
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-400 text-sm">
                        <span>✅</span> Không có yếu tố rủi ro tiêu cực nào được ghi nhận.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Lịch sử các can thiệp hỗ trợ trước đây --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-4">
                <span>🤝</span> Nhật ký Hoạt động Can thiệp & Hỗ trợ (Intervention History)
            </h2>

            <div class="space-y-3">
                @forelse($interventions as $item)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-indigo-700">{{ $item->actionLabel() }}</span>
                            <span class="text-slate-400">{{ $item->created_at->format('H:i d/m/Y') }}</span>
                        </div>
                        <p class="text-sm text-slate-700">{{ $item->notes }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200">
                            <span>Giảng viên: <strong>{{ $item->teacher?->full_name ?? 'Hệ thống' }}</strong></span>
                            <span class="font-semibold {{ $item->status === 'RESOLVED' ? 'text-emerald-600' : 'text-amber-600' }}">
                                Trạng thái: {{ $item->status === 'RESOLVED' ? 'Đã khắc phục' : ($item->status === 'IN_PROGRESS' ? 'Đang hỗ trợ' : 'Chờ xử lý') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4">Chưa có hoạt động can thiệp nào được ghi nhận cho sinh viên này.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CỘT PHẢI: FORM GHI NHẬN HÀNH ĐỘNG HỖ TRỢ CỦA GIÁO VIÊN --}}
    <div class="lg:col-span-1">
        <div class="sticky top-20 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-4">
                <span>✍️</span> Ghi nhận Can thiệp Sư phạm
            </h2>

            <form action="{{ route('teacher.risk.intervene', $student) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="prediction_id" value="{{ $prediction->prediction_id }}">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Hình thức can thiệp</label>
                    <select name="action_type" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="ACADEMIC_TUTORING">Phụ đạo kiến thức / Hướng dẫn bài</option>
                        <option value="ATTENDANCE_REMINDER">Gặp mặt nhắc nhở chuyên cần</option>
                        <option value="COUNSELING">Tư vấn tâm lý / Định hướng học tập</option>
                        <option value="OTHER">Hình thức hỗ trợ khác</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Trạng thái xử lý</label>
                    <select name="status" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="IN_PROGRESS">Đang hỗ trợ / Theo dõi tiếp</option>
                        <option value="RESOLVED">Đã giải quyết ổn thỏa</option>
                        <option value="PENDING">Chờ phản hồi từ sinh viên</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nội dung ghi chú can thiệp</label>
                    <textarea name="notes" rows="4" required placeholder="Ghi chú chi tiết: Đã hẹn gặp sinh viên, hướng dẫn ôn tập lại chương 2, nhắc nhở không được nghỉ thêm..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow-md hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                    <span>Lưu nhật ký can thiệp</span>
                    <span>✓</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

