@extends('layouts.app')

@section('title', 'Trung tâm Cảnh báo Nguy cơ Toàn trường — Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black !text-white flex items-center gap-2.5">
            <svg class="w-7 h-7 text-[#D4FF24] inline-block filter drop-shadow-[0_0_6px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>Trung tâm Cảnh báo Sớm Toàn trường (Early Warning Center)</span>
        </h1>
        <p class="text-xs !text-slate-300 mt-1">Phân tích dữ liệu học tập tổng hợp, phát hiện nguy cơ bỏ học và giám sát can thiệp sư phạm</p>
    </div>

    {{-- Bộ lọc mức độ nguy cơ --}}
    <div class="flex flex-wrap items-center gap-1.5 rounded-2xl bg-[#0a0d17]/80 border border-white/10 p-1.5 shadow-xl glass-card text-xs font-bold">
        <a href="{{ route('admin.risk.index', ['level' => 'ALL']) }}" class="rounded-xl px-3 py-1.5 transition {{ $selectedLevel === 'ALL' ? 'bg-[#1558C9] !text-white shadow-md shadow-[#1558C9]/40' : '!text-slate-300 hover:bg-white/10' }}">Tất cả ({{ $overview['total_students'] }})</a>
        <a href="{{ route('admin.risk.index', ['level' => 'SAFE']) }}" class="rounded-xl px-3 py-1.5 transition {{ $selectedLevel === 'SAFE' ? 'bg-emerald-600 !text-white shadow' : '!text-slate-300 hover:bg-white/10' }} flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block shadow-[0_0_6px_rgba(52,211,153,0.8)]"></span>
            <span>An toàn ({{ $overview['counts_by_level']['SAFE'] ?? 0 }})</span>
        </a>
        <a href="{{ route('admin.risk.index', ['level' => 'MEDIUM']) }}" class="rounded-xl px-3 py-1.5 transition {{ $selectedLevel === 'MEDIUM' ? 'bg-amber-600 !text-white shadow' : '!text-slate-300 hover:bg-white/10' }} flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-amber-400 inline-block shadow-[0_0_6px_rgba(251,191,36,0.8)]"></span>
            <span>Trung bình ({{ $overview['counts_by_level']['MEDIUM'] ?? 0 }})</span>
        </a>
        <a href="{{ route('admin.risk.index', ['level' => 'HIGH']) }}" class="rounded-xl px-3 py-1.5 transition {{ $selectedLevel === 'HIGH' ? 'bg-orange-600 !text-white shadow' : '!text-slate-300 hover:bg-white/10' }} flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-orange-400 inline-block shadow-[0_0_6px_rgba(251,146,60,0.8)]"></span>
            <span>Nguy cơ cao ({{ $overview['counts_by_level']['HIGH'] ?? 0 }})</span>
        </a>
        <a href="{{ route('admin.risk.index', ['level' => 'VERY_HIGH']) }}" class="rounded-xl px-3 py-1.5 transition {{ $selectedLevel === 'VERY_HIGH' ? 'bg-rose-600 !text-white shadow' : '!text-slate-300 hover:bg-white/10' }} flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-rose-400 inline-block shadow-[0_0_6px_rgba(244,63,94,0.8)]"></span>
            <span>Rất cao ({{ $overview['counts_by_level']['VERY_HIGH'] ?? 0 }})</span>
        </a>
    </div>
</div>

{{-- 4 THẺ THỐNG KÊ TỔNG QUAN --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/30 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">An toàn</span>
            <svg class="w-5 h-5 text-emerald-400 filter drop-shadow-[0_0_6px_rgba(52,211,153,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="mt-2 text-3xl font-black !text-white">{{ $overview['counts_by_level']['SAFE'] ?? 0 }}</p>
        <p class="text-xs text-emerald-200 mt-1">PASS toàn bộ, chuyên cần chuẩn &lt; 20%</p>
    </div>

    <div class="rounded-2xl border border-amber-500/30 bg-amber-950/30 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-300">Cần theo dõi (Trung bình)</span>
            <svg class="w-5 h-5 text-amber-400 filter drop-shadow-[0_0_6px_rgba(251,191,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <p class="mt-2 text-3xl font-black !text-white">{{ $overview['counts_by_level']['MEDIUM'] ?? 0 }}</p>
        <p class="text-xs text-amber-200 mt-1">Nghỉ tiệm cận 20% hoặc nợ 1 môn</p>
    </div>

    <div class="rounded-2xl border border-orange-500/30 bg-orange-950/30 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-orange-300">Nguy cơ Cao</span>
            <svg class="w-5 h-5 text-orange-400 filter drop-shadow-[0_0_6px_rgba(251,146,60,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <p class="mt-2 text-3xl font-black !text-white">{{ $overview['counts_by_level']['HIGH'] ?? 0 }}</p>
        <p class="text-xs text-orange-200 mt-1">Nghỉ &gt; 20% hoặc nợ từ 2 môn</p>
    </div>

    <div class="rounded-2xl border border-rose-500/30 bg-rose-950/30 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-300">Nguy cơ Rất cao</span>
            <svg class="w-5 h-5 text-rose-400 filter drop-shadow-[0_0_6px_rgba(244,63,94,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <p class="mt-2 text-3xl font-black !text-white">{{ $overview['counts_by_level']['VERY_HIGH'] ?? 0 }}</p>
        <p class="text-xs text-rose-200 mt-1">Trượt chuyên cần & nợ nhiều môn</p>
    </div>
</div>

{{-- HỆ THỐNG BIỂU ĐỒ PHÂN TÍCH NGUYÊN NHÂN NGHỈ HỌC (PIE / LINE / COLUMN) --}}
@include('components.risk-analytics-charts', ['charts' => $charts ?? null])

{{-- BẢNG DANH SÁCH SINH VIÊN TOÀN TRƯỜNG --}}
<div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl shadow-xl glass-card overflow-hidden">
    <div class="border-b border-white/10 bg-white/5 px-6 py-4 flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-bold !text-white">Danh sách sinh viên theo dõi nguy cơ ({{ $filteredStudents->count() }} sinh viên)</h2>
        <span class="text-xs !text-slate-300">Mô hình phân tích: <strong class="text-[#D4FF24]">Rule-based Engine v1.0 (BestCoder 2026)</strong></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-white/5 text-xs font-bold uppercase tracking-wider !text-slate-200 border-b border-white/10">
                <tr>
                    <th class="px-6 py-3.5">Mã SV</th>
                    <th class="px-6 py-3.5">Họ và tên</th>
                    <th class="px-6 py-3.5">Chuyên ngành</th>
                    <th class="px-6 py-3.5 text-center">Mức độ rủi ro</th>
                    <th class="px-6 py-3.5">Tóm tắt nguyên nhân đánh giá</th>
                    <th class="px-6 py-3.5 text-right">Chi tiết</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($filteredStudents as $item)
                    @php
                        $st = $item['student'];
                        $pred = $item['prediction'];
                        $riskDotColor = match($pred->risk_level) {
                            'SAFE' => 'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.8)]',
                            'MEDIUM' => 'bg-amber-400 shadow-[0_0_6px_rgba(251,191,36,0.8)]',
                            'HIGH' => 'bg-orange-400 shadow-[0_0_6px_rgba(251,146,60,0.8)]',
                            'VERY_HIGH' => 'bg-rose-400 shadow-[0_0_6px_rgba(244,63,94,0.8)]',
                            default => 'bg-slate-400'
                        };
                    @endphp
                    <tr class="hover:bg-white/5 transition duration-150">
                        <td class="px-6 py-4 font-mono font-bold !text-white">{{ $st->student_code ?? 'HL'.str_pad($st->user_id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-6 py-4">
                            <div class="font-bold !text-white">{{ $st->full_name }}</div>
                            <div class="text-xs !text-slate-400 mt-0.5">{{ $st->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="rounded-lg bg-white/10 border border-white/10 px-2.5 py-1 text-xs font-semibold !text-slate-200">{{ $st->major->major_name ?? 'Chưa phân ngành' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $pred->badgeColorClass() }}">
                                <span class="w-2 h-2 rounded-full {{ $riskDotColor }} inline-block"></span>
                                <span>{{ $pred->levelLabel() }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs !text-slate-200 max-w-md leading-relaxed">
                            {{ $pred->summary_explanation }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.risk.show', $st) }}" class="inline-flex items-center gap-1 rounded-xl bg-[#1558C9] hover:bg-[#1558C9]/80 px-3 py-1.5 text-xs font-bold !text-white shadow-md shadow-[#1558C9]/30 transition">
                                <svg class="w-3.5 h-3.5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Xem hồ sơ</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center !text-slate-400">Không có dữ liệu sinh viên nào phù hợp bộ lọc.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
