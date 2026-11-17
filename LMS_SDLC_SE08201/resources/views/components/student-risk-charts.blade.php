@if(!empty($studentCharts['has_data']))
{{-- PHÂN RÃ TRỌNG SỐ ĐÓNG GÓP ĐIỂM NGUY CƠ (EXPLAINABLE AI - FEATURE ATTRIBUTION) --}}
@php
    $sb = $studentCharts['score_breakdown'] ?? [];
    $attSb = $sb['attendance'] ?? ['points' => 0, 'percent' => 0, 'normalized' => 0, 'weight' => 40];
    $acadSb = $sb['academic'] ?? ['points' => 0, 'percent' => 0, 'normalized' => 0, 'weight' => 35];
    $retakeSb = $sb['retake'] ?? ['points' => 0, 'percent' => 0, 'normalized' => 0, 'weight' => 25];
    $totalRiskScore = $studentCharts['risk_score'] ?? 0;
    $riskLvl = $studentCharts['risk_level'] ?? 'SAFE';
    $lvlBadge = match($riskLvl) {
        'VERY_HIGH' => 'bg-rose-50 text-rose-700 border-rose-200',
        'HIGH' => 'bg-orange-50 text-orange-700 border-orange-200',
        'MEDIUM' => 'bg-amber-50 text-amber-700 border-amber-200',
        default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    };
@endphp
<div class="mb-6 rounded-lg border border-[#1e4f8f]/30 bg-white p-5 shadow-sm glass-card">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-[#1e4f8f]/10 text-[#1e4f8f] font-black text-xs">∑</span>
                <h3 class="text-sm sm:text-base font-bold !text-slate-900">Mô hình Trọng số Giải tích & Phân rã Điểm Nguy cơ (Explainable AI)</h3>
                <span class="rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $lvlBadge }}">{{ $riskLvl }}</span>
            </div>
            <p class="text-xs !text-slate-600 mt-1">
                Công thức chuẩn hóa động: <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 font-mono text-[11px]">RiskScore = 100 × (0.40·A + 0.35·G + 0.25·R)</code> = 
                <strong class="text-[#1e4f8f] font-bold">{{ number_format($totalRiskScore, 1) }} / 100</strong>
            </p>
        </div>
        <div class="text-left lg:text-right shrink-0">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Tổng điểm rủi ro</span>
            <div class="flex items-baseline gap-1 lg:justify-end">
                <span class="text-2xl font-black !text-slate-900">{{ number_format($totalRiskScore, 1) }}</span>
                <span class="text-xs font-semibold text-slate-400">/ 100đ</span>
            </div>
        </div>
    </div>

    {{-- 3 CỘT ĐÓNG GÓP ĐIỂM THỰC TẾ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        {{-- 1. Chuyên cần (40%) --}}
        <div class="rounded-md border border-rose-100 bg-rose-50/40 p-3.5">
            <div class="flex items-center justify-between gap-1 mb-1.5">
                <span class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Chuyên cần & Vắng (40%)
                </span>
                <span class="text-xs font-black text-rose-700">{{ number_format($attSb['points'], 1) }}đ</span>
            </div>
            <div class="w-full bg-rose-200/50 rounded-full h-2 overflow-hidden mb-1.5">
                <div class="bg-rose-500 h-2 rounded-full transition-all" style="width: {{ min(100, ($attSb['points'] / 40.0) * 100) }}%"></div>
            </div>
            <div class="flex justify-between items-center text-[11px] text-slate-600">
                <span>Chỉ số A = {{ number_format($attSb['normalized'], 2) }}</span>
                <strong class="text-rose-700 font-bold">{{ number_format($attSb['percent'], 1) }}% tổng điểm</strong>
            </div>
        </div>

        {{-- 2. Kết quả học tập (35%) --}}
        <div class="rounded-md border border-amber-100 bg-amber-50/40 p-3.5">
            <div class="flex items-center justify-between gap-1 mb-1.5">
                <span class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Học lực & Điểm GPA (35%)
                </span>
                <span class="text-xs font-black text-amber-700">{{ number_format($acadSb['points'], 1) }}đ</span>
            </div>
            <div class="w-full bg-amber-200/50 rounded-full h-2 overflow-hidden mb-1.5">
                <div class="bg-amber-500 h-2 rounded-full transition-all" style="width: {{ min(100, ($acadSb['points'] / 35.0) * 100) }}%"></div>
            </div>
            <div class="flex justify-between items-center text-[11px] text-slate-600">
                <span>Chỉ số G = {{ number_format($acadSb['normalized'], 2) }}</span>
                <strong class="text-amber-700 font-bold">{{ number_format($acadSb['percent'], 1) }}% tổng điểm</strong>
            </div>
        </div>

        {{-- 3. Trượt môn / Học lại (25%) --}}
        <div class="rounded-md border border-purple-100 bg-purple-50/40 p-3.5">
            <div class="flex items-center justify-between gap-1 mb-1.5">
                <span class="text-xs font-bold text-purple-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    Trượt môn / Học lại (25%)
                </span>
                <span class="text-xs font-black text-purple-700">{{ number_format($retakeSb['points'], 1) }}đ</span>
            </div>
            <div class="w-full bg-purple-200/50 rounded-full h-2 overflow-hidden mb-1.5">
                <div class="bg-purple-500 h-2 rounded-full transition-all" style="width: {{ min(100, ($retakeSb['points'] / 25.0) * 100) }}%"></div>
            </div>
            <div class="flex justify-between items-center text-[11px] text-slate-600">
                <span>Chỉ số R = {{ number_format($retakeSb['normalized'], 2) }}</span>
                <strong class="text-purple-700 font-bold">{{ number_format($retakeSb['percent'], 1) }}% tổng điểm</strong>
            </div>
        </div>
    </div>

    {{-- CHÚ THÍCH HỌC THUẬT (MỤC 4.4 & 8) --}}
    <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-start gap-2 text-[11px] !text-slate-500">
        <svg class="w-4 h-4 text-[#1e4f8f] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="leading-relaxed">
            <strong>Diễn giải khoa học chuẩn mực (OECD / UNICEF):</strong> Tỷ lệ phần trăm trên trả lời câu hỏi <em>"Phần nào của điểm cảnh báo đến từ yếu tố này?"</em>. Điểm nguy cơ là công cụ phân loại mức độ ưu tiên can thiệp sư phạm, <strong>không đồng nghĩa</strong> sinh viên có {{ number_format($totalRiskScore, 1) }}% xác suất bỏ học.
        </p>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- 1. PIE CHART CÁ NHÂN: CƠ CẤU ĐIỂM DANH & NGUYÊN NHÂN VẮNG --}}
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm glass-card">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold !text-slate-900">Cơ cấu Điểm danh & Lý do Vắng</h3>
                <p class="text-[11px] !text-slate-500 mt-0.5">Đã điểm danh {{ $studentCharts['pie']['total_sessions_marked'] ?? 0 }} buổi (Vắng {{ $studentCharts['pie']['total_absent'] ?? 0 }} buổi)</p>
            </div>
            <span class="rounded-md bg-rose-50 border border-rose-200 px-2 py-0.5 text-[10px] font-semibold text-rose-700">PIE CHART</span>
        </div>
        <div class="relative w-full" style="height: 230px;">
            <canvas id="studentDetailPieChart"></canvas>
        </div>
    </div>

    {{-- 2. LINE CHART CÁ NHÂN: XU HƯỚNG TỶ LỆ VẮNG TÍCH LŨY --}}
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm glass-card">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold !text-slate-900">Diễn biến Tỷ lệ Vắng Tích lũy</h3>
                <p class="text-[11px] !text-slate-500 mt-0.5">Theo dõi đà tăng chuyên cần qua các buổi học gần nhất</p>
            </div>
            <span class="rounded-md bg-[#1e4f8f]/5 border border-[#1e4f8f]/30 px-2 py-0.5 text-[10px] font-semibold text-[#1e4f8f]">LINE CHART</span>
        </div>
        <div class="relative w-full" style="height: 230px;">
            <canvas id="studentDetailLineChart"></canvas>
        </div>
    </div>

    {{-- 3. COLUMN CHART CÁ NHÂN: ĐỐI CHIẾU TỪNG MÔN HỌC --}}
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm glass-card">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold !text-slate-900">So sánh Vắng (%) & Điểm theo Môn</h3>
                <p class="text-[11px] !text-slate-500 mt-0.5">Phát hiện môn học sinh viên hay bỏ tiết hoặc nợ bài nhất</p>
            </div>
            <span class="rounded-md bg-sky-50 border border-sky-200 px-2 py-0.5 text-[10px] font-semibold text-sky-700">COLUMN CHART</span>
        </div>
        <div class="relative w-full" style="height: 230px;">
            <canvas id="studentDetailColumnChart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stData = @json($studentCharts);
    const tipCfg = {
        backgroundColor: '#0f172a',
        borderColor: 'rgba(15, 23, 42, 0.15)',
        borderWidth: 1,
        titleColor: '#f8fafc',
        bodyColor: '#e2e8f0',
        padding: 10,
        cornerRadius: 8
    };

    const pEl = document.getElementById('studentDetailPieChart');
    if (pEl && stData.pie) {
        new Chart(pEl, {
            type: 'pie',
            data: {
                labels: stData.pie.labels,
                datasets: [{
                    data: stData.pie.data,
                    backgroundColor: stData.pie.colors,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: { display: false },
                    legend: {
                        position: 'bottom',
                        labels: { color: '#475569', boxWidth: 10, padding: 8, font: { size: 10, weight: '600' } }
                    },
                    tooltip: tipCfg
                }
            }
        });
    }

    const lEl = document.getElementById('studentDetailLineChart');
    if (lEl && stData.line) {
        new Chart(lEl, {
            type: 'line',
            data: {
                labels: stData.line.labels,
                datasets: [
                    {
                        label: 'Tỷ lệ vắng tích lũy (%)',
                        data: stData.line.cumulative_absence_rate,
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244, 63, 94, 0.16)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 3.5
                    },
                    {
                        label: 'Ngưỡng cấm thi (20%)',
                        data: stData.line.threshold_line,
                        borderColor: '#d97706',
                        borderDash: [5, 5],
                        borderWidth: 1.8,
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    datalabels: { display: false },
                    legend: {
                        position: 'top',
                        labels: { color: '#475569', boxWidth: 8, font: { size: 10, weight: '600' } }
                    },
                    tooltip: tipCfg
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 9 }, maxRotation: 35 } },
                    y: { beginAtZero: true, suggestedMax: 35, grid: { color: 'rgba(15, 23, 42, 0.08)' }, ticks: { color: '#fda4af', callback: v => v + '%' } }
                }
            }
        });
    }

    const cEl = document.getElementById('studentDetailColumnChart');
    if (cEl && stData.column) {
        new Chart(cEl, {
            type: 'bar',
            data: {
                labels: stData.column.labels,
                datasets: [
                    {
                        label: 'Tỷ lệ vắng (%)',
                        data: stData.column.absence_rates,
                        backgroundColor: 'rgba(244, 63, 94, 0.85)',
                        borderRadius: 6,
                        maxBarThickness: 28
                    },
                    {
                        label: 'Điểm TB (Quy đổi %)',
                        data: stData.column.scores_pct,
                        backgroundColor: 'rgba(56, 189, 248, 0.82)',
                        borderRadius: 6,
                        maxBarThickness: 28
                    },
                    {
                        label: 'Tỷ lệ nộp bài (%)',
                        data: stData.column.submission_rates,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 6,
                        maxBarThickness: 28
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    datalabels: { display: false },
                    legend: {
                        position: 'top',
                        labels: { color: '#475569', boxWidth: 8, font: { size: 10, weight: '600' } }
                    },
                    tooltip: {
                        ...tipCfg,
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.datasetIndex === 1) {
                                    return ` Điểm TB: ${(ctx.parsed.y / 10).toFixed(1)}/10 (${ctx.parsed.y}%)`;
                                }
                                return ` ${ctx.dataset.label}: ${ctx.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#475569', font: { size: 9 }, maxRotation: 30 } },
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(15, 23, 42, 0.08)' }, ticks: { color: '#475569', callback: v => v + '%' } }
                }
            }
        });
    }
});
</script>
@endpush
@endif
