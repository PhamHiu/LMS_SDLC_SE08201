@extends('layouts.app')

@section('title', 'Hồ sơ Cảnh báo Học tập Sinh viên — Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <a href="{{ route('admin.risk.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold !text-slate-400 hover:!text-[#D4FF24] transition mb-2">
            <span>←</span> Quay lại danh sách toàn trường
        </a>
        <h1 class="text-2xl font-black !text-white flex items-center gap-2.5">
            <svg class="w-7 h-7 text-[#D4FF24] inline-block filter drop-shadow-[0_0_6px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>Giám sát Cảnh báo Học tập & Can thiệp</span>
        </h1>
        <p class="text-xs !text-slate-300 mt-1">Sinh viên: <strong class="!text-white">{{ $student->full_name }}</strong> · Mã SV: <span class="font-mono text-[#D4FF24] font-bold">{{ $student->student_code ?? 'HL'.str_pad($student->user_id, 5, '0', STR_PAD_LEFT) }}</span> · Ngành: {{ $student->major->major_name ?? '—' }}</p>
    </div>

    <div>
        @php
            $badgeDot = match($prediction->risk_level) {
                'SAFE' => 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]',
                'MEDIUM' => 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]',
                'HIGH' => 'bg-orange-400 shadow-[0_0_8px_rgba(251,146,60,0.8)]',
                'VERY_HIGH' => 'bg-rose-400 shadow-[0_0_8px_rgba(244,63,94,0.8)]',
                default => 'bg-slate-400'
            };
        @endphp
        <span class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-black shadow-lg {{ $prediction->badgeColorClass() }}">
            <span class="w-3 h-3 rounded-full {{ $badgeDot }} inline-block"></span>
            <span>MỨC ĐỘ: {{ mb_strtoupper($prediction->levelLabel()) }}</span>
        </span>
    </div>
</div>

{{-- BIỂU ĐỒ CHẨN ĐOÁN CÁ NHÂN (PIE / LINE / COLUMN) --}}
@include('components.student-risk-charts', ['studentCharts' => $studentCharts ?? null])

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        {{-- Card Tóm tắt nguyên nhân --}}
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl p-6 shadow-xl glass-card">
            <h2 class="text-base font-bold !text-white flex items-center gap-2.5 mb-3">
                <svg class="w-5 h-5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Căn cứ Phân tích & Giải thích Tự động</span>
            </h2>
            <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-sm leading-relaxed !text-slate-200">
                <p class="font-bold !text-white text-base">{{ $prediction->summary_explanation }}</p>
                <div class="mt-3 pt-3 border-t border-white/10 flex flex-wrap gap-4 text-xs !text-slate-300">
                    <span>Phương pháp: <strong class="!text-white">{{ $prediction->analysis_method }}</strong></span>
                    <span>Phiên bản mô hình: <strong class="!text-white">{{ $prediction->model_version }}</strong></span>
                    <span>Thời điểm phân tích: <strong class="!text-white">{{ $prediction->analyzed_at->format('H:i d/m/Y') }}</strong></span>
                </div>
            </div>

            @if($prediction->recommendations)
                <div class="mt-4 rounded-xl bg-[#1558C9]/20 border border-[#1558C9]/40 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#D4FF24] mb-1">Khuyến nghị sư phạm từ hệ thống:</h3>
                    <p class="text-sm !text-slate-100 font-medium leading-relaxed">{{ $prediction->recommendations }}</p>
                </div>
            @endif
        </div>

        {{-- Card Các yếu tố rủi ro --}}
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl p-6 shadow-xl glass-card">
            <h2 class="text-base font-bold !text-white flex items-center gap-2.5 mb-4">
                <svg class="w-5 h-5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Chi tiết các Yếu tố Ảnh hưởng (Contributing Factors)</span>
            </h2>

            <div class="space-y-3">
                @forelse($prediction->factors as $factor)
                    @php
                        $factorIconColor = $factor->severity === 'CRITICAL' ? 'text-rose-400' : ($factor->severity === 'WARNING' ? 'text-amber-400' : 'text-sky-400');
                    @endphp
                    <div class="rounded-xl border p-4 transition {{ $factor->severity === 'CRITICAL' ? 'border-rose-500/30 bg-rose-950/30' : ($factor->severity === 'WARNING' ? 'border-amber-500/30 bg-amber-950/30' : 'border-white/10 bg-white/5') }}">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-bold !text-white flex items-center gap-2">
                                <svg class="w-4 h-4 {{ $factorIconColor }} inline-block filter drop-shadow-[0_0_4px_currentColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>{{ $factor->title }}</span>
                            </h3>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase border {{ $factor->severity === 'CRITICAL' ? 'bg-rose-500/20 border-rose-400/30 text-rose-300' : ($factor->severity === 'WARNING' ? 'bg-amber-500/20 border-amber-400/30 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300') }}">
                                {{ $factor->severity }}
                            </span>
                        </div>
                        <p class="text-xs !text-slate-300 mt-2 leading-relaxed">{{ $factor->description }}</p>
                    </div>
                @empty
                    <div class="text-center py-6 !text-slate-400 text-sm flex items-center justify-center gap-1.5">
                        <svg class="w-5 h-5 text-emerald-400 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Không có yếu tố rủi ro nào được ghi nhận.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CỘT PHẢI: THÔNG TIN HỌC VỤ & NHẬT KÝ CAN THIỆP --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl p-6 shadow-xl glass-card">
            <h2 class="text-base font-bold !text-white flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>Các lớp đang học</span>
            </h2>
            <div class="space-y-2">
                @forelse($student->enrolledClasses as $c)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-xs">
                        <strong class="!text-white block text-sm">{{ $c->class_name }}</strong>
                        <span class="!text-slate-300 mt-0.5 block">Môn: {{ $c->subject?->subject_name ?? '—' }}</span>
                    </div>
                @empty
                    <p class="text-xs !text-slate-400">Chưa ghi danh lớp nào.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl p-6 shadow-xl glass-card">
            <h2 class="text-base font-bold !text-white flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Lịch sử can thiệp của GV</span>
            </h2>
            <div class="space-y-3">
                @forelse($interventions as $item)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-xs">
                        <div class="flex justify-between font-bold text-[#D4FF24] mb-1">
                            <span>{{ $item->actionLabel() }}</span>
                            <span class="!text-slate-400 font-normal">{{ $item->created_at->format('d/m/Y') }}</span>
                        </div>
                        <p class="!text-slate-200 mt-1 leading-relaxed">{{ $item->notes }}</p>
                        <p class="!text-slate-300 mt-1.5 font-medium">Bởi: {{ $item->teacher?->full_name ?? 'Hệ thống' }}</p>
                    </div>
                @empty
                    <p class="text-xs !text-slate-400 text-center py-4">Chưa có nhật ký can thiệp.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
