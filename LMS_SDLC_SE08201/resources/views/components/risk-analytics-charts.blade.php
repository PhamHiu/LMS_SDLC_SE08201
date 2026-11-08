@if(!empty($charts))
<div class="mb-7 space-y-6">
    {{-- BANNER NHẬN ĐỊNH NGUYÊN NHÂN NGHỈ HỌC TỪ AI --}}
    <div class="rounded-2xl border border-[#1558C9]/40 bg-gradient-to-r from-[#0a0d17]/95 via-[#0f1b38]/90 to-[#0a0d17]/95 p-5 shadow-xl glass-card">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#1558C9]/30 border border-[#D4FF24]/40 text-[#D4FF24] shadow-[0_0_15px_rgba(212,255,36,0.25)]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm sm:text-base font-extrabold !text-white">AI Phân tích Đa chiều: Vì sao Sinh viên Nghỉ học & Có Nguy cơ Bỏ học?</h2>
                        <span class="rounded-full bg-[#D4FF24]/15 border border-[#D4FF24]/40 px-2.5 py-0.5 text-[11px] font-bold text-[#D4FF24]">{{ $charts['scope_label'] ?? '' }}</span>
                    </div>
                    <p class="mt-1.5 text-xs sm:text-sm !text-slate-200 leading-relaxed">
                        {{ $charts['ai_insight'] ?? '' }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-3 self-start lg:self-center">
                <div class="rounded-xl border border-rose-500/30 bg-rose-950/40 px-4 py-2.5 text-center">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-rose-300">Tổng lượt vắng</span>
                    <span class="text-lg font-black !text-white">{{ $charts['total_absences'] ?? 0 }}</span>
                    <span class="text-xs text-rose-300 font-semibold">({{ $charts['overall_absence_rate'] ?? 0 }}%)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== HÀNG 1: 2 BIỂU ĐỒ TRÒN (PIE CHARTS) ==================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        {{-- PIE CHART 1: NGUYÊN NHÂN HỌC SINH NGHỈ HỌC (7 CỘT) --}}
        <div class="xl:col-span-7 rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 sm:p-6 shadow-xl glass-card flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-base font-bold !text-white flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-400 shadow-[0_0_8px_rgba(244,63,94,0.8)]"></span>
                            <span>Cơ cấu Nguyên nhân Nghỉ học (AI Root-Cause)</span>
                        </h3>
                        <p class="text-xs !text-slate-400 mt-0.5">Tổng hợp từ ghi chú điểm danh & tương quan học lực, lịch ca học, ngưỡng cấm thi</p>
                    </div>
                    <span class="rounded-lg bg-rose-500/15 border border-rose-400/30 px-2.5 py-1 text-[11px] font-extrabold tracking-wider text-rose-300">PIE CHART</span>
                </div>

                @if(($charts['total_absences'] ?? 0) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                        <div class="md:col-span-6 relative mx-auto w-full max-w-[260px]" style="height: 250px;">
                            <canvas id="riskReasonPieChart"></canvas>
                        </div>
                        <div class="md:col-span-6 space-y-2">
                            @foreach($charts['reasons_pie']['breakdown'] ?? [] as $item)
                                <div class="flex items-center justify-between gap-2 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 hover:bg-white/[0.07] transition">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $item['color'] }}"></span>
                                        <span class="truncate text-xs font-semibold !text-slate-200" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="text-xs font-black !text-white">{{ $item['count'] }}</span>
                                        <span class="ml-1 text-[11px] font-bold text-[#D4FF24]">({{ $item['percent'] }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="flex h-[250px] items-center justify-center text-sm !text-slate-400">
                        Chưa phát sinh lượt nghỉ học nào trong phạm vi dữ liệu này.
                    </div>
                @endif
            </div>
        </div>

        {{-- PIE CHART 2: TỶ TRỌNG YẾU TỐ RỦI RO & PHÂN TẦNG CẢNH BÁO (5 CỘT) --}}
        <div class="xl:col-span-5 rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 sm:p-6 shadow-xl glass-card flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-base font-bold !text-white flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]"></span>
                            <span id="secondaryPieTitle">Tỷ trọng Nhóm Yếu tố Rủi ro AI</span>
                        </h3>
                        <p class="text-xs !text-slate-400 mt-0.5">Đo lường tác động giữa chuyên cần, điểm yếu và nợ bài tập</p>
                    </div>
                    <div class="flex items-center gap-1 rounded-xl bg-white/5 border border-white/10 p-1 text-[11px] font-bold">
                        <button type="button" id="btnPieFactors" onclick="switchRiskSecondaryPie('factors')" class="rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition">Yếu tố lỗi</button>
                        <button type="button" id="btnPieLevels" onclick="switchRiskSecondaryPie('levels')" class="rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition">Mức rủi ro</button>
                    </div>
                </div>

                <div class="relative w-full" style="height: 255px;">
                    <canvas id="riskFactorsPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== HÀNG 2: 2 BIỂU ĐỒ ĐƯỜNG (LINE CHARTS) ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- LINE CHART 1: XU HƯỚNG TỶ LỆ NGHỈ HỌC THEO THỜI GIAN --}}
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 sm:p-6 shadow-xl glass-card">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold !text-white flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#D4FF24] shadow-[0_0_8px_rgba(212,255,36,0.8)]"></span>
                        <span>Diễn biến Tỷ lệ Nghỉ học theo {{ ($charts['mode'] ?? 'admin') === 'teacher' ? 'Từng Buổi học' : 'Tuần Đào tạo' }}</span>
                    </h3>
                    <p class="text-xs !text-slate-400 mt-0.5">Phát hiện thời điểm sinh viên bắt đầu hụt hơi và vượt ngưỡng cấm thi 20%</p>
                </div>
                <span class="rounded-lg bg-[#D4FF24]/15 border border-[#D4FF24]/40 px-2.5 py-1 text-[11px] font-extrabold tracking-wider text-[#D4FF24]">LINE CHART</span>
            </div>
            <div class="relative w-full" style="height: 275px;">
                <canvas id="riskTimelineLineChart"></canvas>
            </div>
        </div>

        {{-- LINE CHART 2: ĐƯỜNG CONG TƯƠNG QUAN ĐIỂM SỐ & NỘP BÀI THEO MỨC VẮNG --}}
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 sm:p-6 shadow-xl glass-card">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold !text-white flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                        <span>Tương quan Điểm TB & Hoàn thành Bài tập theo Mức Vắng</span>
                    </h3>
                    <p class="text-xs !text-slate-400 mt-0.5">Minh chứng mối liên hệ nhân quả: vắng càng nhiều → nợ bài & tụt điểm sâu</p>
                </div>
                <span class="rounded-lg bg-emerald-500/15 border border-emerald-400/30 px-2.5 py-1 text-[11px] font-extrabold tracking-wider text-emerald-300">LINE CHART</span>
            </div>
            <div class="relative w-full" style="height: 275px;">
                <canvas id="riskCorrelationLineChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ==================== HÀNG 3: 2 BIỂU ĐỒ CỘT (COLUMN CHARTS) ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- COLUMN CHART 1: PHÂN TÍCH NGHỈ HỌC THEO CA HỌC & THỨ TRONG TUẦN --}}
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 sm:p-6 shadow-xl glass-card">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold !text-white flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.8)]"></span>
                        <span id="scheduleColTitle">Tỷ lệ Nghỉ học theo Khung Ca học</span>
                    </h3>
                    <p class="text-xs !text-slate-400 mt-0.5">Nhận diện ảnh hưởng của ca sáng sớm (07:15), ca trưa và ngày cuối tuần</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="flex items-center gap-1 rounded-xl bg-white/5 border border-white/10 p-1 text-[11px] font-bold">
                        <button type="button" id="btnColSlot" onclick="switchScheduleColumn('slot')" class="rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition">Theo Ca</button>
                        <button type="button" id="btnColDow" onclick="switchScheduleColumn('dow')" class="rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition">Theo Thứ</button>
                    </div>
                    <span class="hidden sm:inline-block rounded-lg bg-sky-500/15 border border-sky-400/30 px-2.5 py-1 text-[11px] font-extrabold tracking-wider text-sky-300">COLUMN CHART</span>
                </div>
            </div>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="riskScheduleColumnChart"></canvas>
            </div>
        </div>

        {{-- COLUMN CHART 2: SO SÁNH TỶ LỆ VẮNG & CẤM THI THEO HỌC LỰC / LỚP MÔN --}}
        <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 sm:p-6 shadow-xl glass-card">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold !text-white flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-purple-400 shadow-[0_0_8px_rgba(168,85,247,0.8)]"></span>
                        <span id="comparisonColTitle">Tỷ lệ Nghỉ học & Cấm thi theo Nhóm Học lực</span>
                    </h3>
                    <p class="text-xs !text-slate-400 mt-0.5">So sánh tỷ lệ vắng TB (%) và tỷ lệ vượt ngưỡng >20% giữa các nhóm học lực / lớp môn</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="flex items-center gap-1 rounded-xl bg-white/5 border border-white/10 p-1 text-[11px] font-bold">
                        <button type="button" id="btnColAcad" onclick="switchComparisonColumn('academic')" class="rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition">Học lực</button>
                        <button type="button" id="btnColClass" onclick="switchComparisonColumn('classes')" class="rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition">Lớp / Môn</button>
                    </div>
                    <span class="hidden sm:inline-block rounded-lg bg-purple-500/15 border border-purple-400/30 px-2.5 py-1 text-[11px] font-extrabold tracking-wider text-purple-300">COLUMN CHART</span>
                </div>
            </div>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="riskComparisonColumnChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = @json($charts);
    const darkTooltip = {
        backgroundColor: '#0f172a',
        borderColor: 'rgba(212, 255, 36, 0.35)',
        borderWidth: 1,
        titleColor: '#f8fafc',
        bodyColor: '#e2e8f0',
        padding: 11,
        cornerRadius: 10
    };

    // 1. PIE CHART: NGUYÊN NHÂN HỌC SINH NGHỈ HỌC
    const reasonEl = document.getElementById('riskReasonPieChart');
    if (reasonEl && chartData.reasons_pie && chartData.total_absences > 0) {
        new Chart(reasonEl, {
            type: 'pie',
            data: {
                labels: chartData.reasons_pie.labels,
                datasets: [{
                    data: chartData.reasons_pie.data,
                    backgroundColor: chartData.reasons_pie.colors,
                    borderColor: '#0a0d17',
                    borderWidth: 2.5,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: { display: false },
                    legend: { display: false },
                    tooltip: {
                        ...darkTooltip,
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.parsed || 0;
                                const pct = chartData.total_absences > 0
                                    ? ((val / chartData.total_absences) * 100).toFixed(1)
                                    : 0;
                                const fullLabel = chartData.reasons_pie.full_labels?.[ctx.dataIndex] || ctx.label;
                                return ` ${fullLabel}: ${val} lượt (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. PIE / DOUGHNUT CHART: YẾU TỐ RỦI RO <-> MỨC ĐỘ CẢNH BÁO
    const factorEl = document.getElementById('riskFactorsPieChart');
    let secondaryPieInstance = null;
    if (factorEl) {
        secondaryPieInstance = new Chart(factorEl, {
            type: 'pie',
            data: {
                labels: chartData.risk_factors_pie.labels,
                datasets: [{
                    data: chartData.risk_factors_pie.data,
                    backgroundColor: chartData.risk_factors_pie.colors,
                    borderColor: '#0a0d17',
                    borderWidth: 2.5,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '42%',
                plugins: {
                    datalabels: { display: false },
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#cbd5e1',
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        ...darkTooltip,
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + Number(b || 0), 0);
                                const val = ctx.parsed || 0;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });

        window.switchRiskSecondaryPie = function (mode) {
            if (!secondaryPieInstance) return;
            const src = mode === 'levels' ? chartData.risk_levels_pie : chartData.risk_factors_pie;
            secondaryPieInstance.data.labels = src.labels;
            secondaryPieInstance.data.datasets[0].data = src.data;
            secondaryPieInstance.data.datasets[0].backgroundColor = src.colors;
            secondaryPieInstance.update();

            document.getElementById('secondaryPieTitle').textContent = mode === 'levels'
                ? 'Phân tầng Mức độ Nguy cơ Sinh viên'
                : 'Tỷ trọng Nhóm Yếu tố Rủi ro AI';

            const bFac = document.getElementById('btnPieFactors');
            const bLvl = document.getElementById('btnPieLevels');
            if (mode === 'levels') {
                bLvl.className = 'rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition';
                bFac.className = 'rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition';
            } else {
                bFac.className = 'rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition';
                bLvl.className = 'rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition';
            }
        };
    }

    // 3. LINE CHART 1: DIỄN BIẾN NGHỈ HỌC THEO THỜI GIAN
    const tlEl = document.getElementById('riskTimelineLineChart');
    if (tlEl && chartData.timeline_line) {
        new Chart(tlEl, {
            type: 'line',
            data: {
                labels: chartData.timeline_line.labels,
                datasets: [
                    {
                        label: 'Tỷ lệ vắng (%)',
                        data: chartData.timeline_line.absence_rates,
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244, 63, 94, 0.16)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#f43f5e',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ngưỡng cấm thi (20%)',
                        data: chartData.timeline_line.threshold_20,
                        borderColor: '#D4FF24',
                        borderDash: [6, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Số lượt SV vắng',
                        data: chartData.timeline_line.absent_counts,
                        borderColor: '#38bdf8',
                        backgroundColor: 'rgba(56, 189, 248, 0.1)',
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#38bdf8',
                        yAxisID: 'y1'
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
                        labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '600' } }
                    },
                    tooltip: darkTooltip
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 35 }
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        suggestedMax: 40,
                        grid: { color: 'rgba(255,255,255,0.07)' },
                        ticks: { color: '#fda4af', callback: v => v + '%' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#7dd3fc', precision: 0 }
                    }
                }
            }
        });
    }

    // 4. LINE CHART 2: TƯƠNG QUAN ĐIỂM SỐ & TỶ LỆ NỘP BÀI THEO MỨC ĐỘ VẮNG
    const corrEl = document.getElementById('riskCorrelationLineChart');
    if (corrEl && chartData.correlation_line) {
        new Chart(corrEl, {
            type: 'line',
            data: {
                labels: chartData.correlation_line.labels,
                datasets: [
                    {
                        label: 'Điểm trung bình (Thang 10)',
                        data: chartData.correlation_line.avg_scores,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        fill: true,
                        spanGaps: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 4.5,
                        pointBackgroundColor: '#10b981',
                        yAxisID: 'yScore'
                    },
                    {
                        label: 'Tỷ lệ hoàn thành bài tập (%)',
                        data: chartData.correlation_line.submission_rates,
                        borderColor: '#818cf8',
                        backgroundColor: 'rgba(129, 140, 248, 0.12)',
                        spanGaps: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 4.5,
                        pointBackgroundColor: '#818cf8',
                        yAxisID: 'yPct'
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
                        labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '600' } }
                    },
                    tooltip: darkTooltip
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#cbd5e1', font: { size: 11 } }
                    },
                    yScore: {
                        type: 'linear',
                        position: 'left',
                        min: 0,
                        max: 10,
                        grid: { color: 'rgba(255,255,255,0.07)' },
                        ticks: { color: '#6ee7b7', callback: v => v + ' đ' }
                    },
                    yPct: {
                        type: 'linear',
                        position: 'right',
                        min: 0,
                        max: 100,
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#a5b4fc', callback: v => v + '%' }
                    }
                }
            }
        });
    }

    // 5. COLUMN CHART 1: NGHỈ HỌC THEO CA HỌC & THỨ TRONG TUẦN
    const schedEl = document.getElementById('riskScheduleColumnChart');
    let schedColInstance = null;
    if (schedEl && chartData.schedule_column) {
        const initSlot = chartData.schedule_column.by_slot;
        schedColInstance = new Chart(schedEl, {
            type: 'bar',
            data: {
                labels: initSlot.labels,
                datasets: [
                    {
                        label: 'Tỷ lệ vắng (%)',
                        data: initSlot.rates,
                        backgroundColor: 'rgba(56, 189, 248, 0.82)',
                        borderColor: '#38bdf8',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 38,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Số lượt vắng',
                        data: initSlot.counts,
                        backgroundColor: 'rgba(245, 158, 11, 0.78)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 38,
                        yAxisID: 'y1'
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
                        labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '600' } }
                    },
                    tooltip: darkTooltip
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#cbd5e1', font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: { color: 'rgba(255,255,255,0.07)' },
                        ticks: { color: '#7dd3fc', callback: v => v + '%' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#fcd34d', precision: 0 }
                    }
                }
            }
        });

        window.switchScheduleColumn = function (mode) {
            if (!schedColInstance) return;
            const src = mode === 'dow' ? chartData.schedule_column.by_dow : chartData.schedule_column.by_slot;
            schedColInstance.data.labels = src.labels;
            schedColInstance.data.datasets[0].data = src.rates;
            schedColInstance.data.datasets[1].data = src.counts;
            schedColInstance.update();

            document.getElementById('scheduleColTitle').textContent = mode === 'dow'
                ? 'Tỷ lệ Nghỉ học theo Thứ trong tuần'
                : 'Tỷ lệ Nghỉ học theo Khung Ca học';

            const bSlot = document.getElementById('btnColSlot');
            const bDow = document.getElementById('btnColDow');
            if (mode === 'dow') {
                bDow.className = 'rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition';
                bSlot.className = 'rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition';
            } else {
                bSlot.className = 'rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition';
                bDow.className = 'rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition';
            }
        };
    }

    // 6. COLUMN CHART 2: SO SÁNH THEO NHÓM HỌC LỰC <-> LỚP / MÔN HỌC
    const compEl = document.getElementById('riskComparisonColumnChart');
    let compColInstance = null;
    if (compEl && chartData.academic_column) {
        compColInstance = new Chart(compEl, {
            type: 'bar',
            data: {
                labels: chartData.academic_column.labels,
                datasets: [
                    {
                        label: 'Tỷ lệ vắng TB (%)',
                        data: chartData.academic_column.avg_absence_rates,
                        backgroundColor: 'rgba(168, 85, 247, 0.85)',
                        borderColor: '#a855f7',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 38
                    },
                    {
                        label: 'Tỷ lệ SV vượt ngưỡng >20% (%)',
                        data: chartData.academic_column.banned_rates,
                        backgroundColor: 'rgba(244, 63, 94, 0.85)',
                        borderColor: '#f43f5e',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 38
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
                        labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '600' } }
                    },
                    tooltip: {
                        ...darkTooltip,
                        callbacks: {
                            label: function (ctx) {
                                return ` ${ctx.dataset.label}: ${ctx.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#cbd5e1', font: { size: 10 }, maxRotation: 25 }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 50,
                        grid: { color: 'rgba(255,255,255,0.07)' },
                        ticks: { color: '#cbd5e1', callback: v => v + '%' }
                    }
                }
            }
        });

        window.switchComparisonColumn = function (mode) {
            if (!compColInstance) return;
            if (mode === 'classes') {
                compColInstance.data.labels = chartData.subject_class_column.labels;
                compColInstance.data.datasets[0].data = chartData.subject_class_column.absence_rates;
                compColInstance.data.datasets[1].data = chartData.subject_class_column.banned_rates;
                document.getElementById('comparisonColTitle').textContent = 'Tỷ lệ Nghỉ học & Cấm thi theo Lớp / Môn học';
            } else {
                compColInstance.data.labels = chartData.academic_column.labels;
                compColInstance.data.datasets[0].data = chartData.academic_column.avg_absence_rates;
                compColInstance.data.datasets[1].data = chartData.academic_column.banned_rates;
                document.getElementById('comparisonColTitle').textContent = 'Tỷ lệ Nghỉ học & Cấm thi theo Nhóm Học lực';
            }
            compColInstance.update();

            const bAcad = document.getElementById('btnColAcad');
            const bCls = document.getElementById('btnColClass');
            if (mode === 'classes') {
                bCls.className = 'rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition';
                bAcad.className = 'rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition';
            } else {
                bAcad.className = 'rounded-lg bg-[#1558C9] px-2.5 py-1 !text-white shadow transition';
                bCls.className = 'rounded-lg px-2.5 py-1 !text-slate-300 hover:bg-white/10 transition';
            }
        };
    }
});
</script>
@endpush
@endif
