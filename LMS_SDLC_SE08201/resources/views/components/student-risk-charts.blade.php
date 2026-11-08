@if(!empty($studentCharts['has_data']))
<div class="mb-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- 1. PIE CHART CÁ NHÂN: CƠ CẤU ĐIỂM DANH & NGUYÊN NHÂN VẮNG --}}
    <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold !text-white">Cơ cấu Điểm danh & Lý do Vắng</h3>
                <p class="text-[11px] !text-slate-400 mt-0.5">Đã điểm danh {{ $studentCharts['pie']['total_sessions_marked'] ?? 0 }} buổi (Vắng {{ $studentCharts['pie']['total_absent'] ?? 0 }} buổi)</p>
            </div>
            <span class="rounded-md bg-rose-500/15 border border-rose-400/30 px-2 py-0.5 text-[10px] font-extrabold text-rose-300">PIE CHART</span>
        </div>
        <div class="relative w-full" style="height: 230px;">
            <canvas id="studentDetailPieChart"></canvas>
        </div>
    </div>

    {{-- 2. LINE CHART CÁ NHÂN: XU HƯỚNG TỶ LỆ VẮNG TÍCH LŨY --}}
    <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold !text-white">Diễn biến Tỷ lệ Vắng Tích lũy</h3>
                <p class="text-[11px] !text-slate-400 mt-0.5">Theo dõi đà tăng chuyên cần qua các buổi học gần nhất</p>
            </div>
            <span class="rounded-md bg-[#D4FF24]/15 border border-[#D4FF24]/40 px-2 py-0.5 text-[10px] font-extrabold text-[#D4FF24]">LINE CHART</span>
        </div>
        <div class="relative w-full" style="height: 230px;">
            <canvas id="studentDetailLineChart"></canvas>
        </div>
    </div>

    {{-- 3. COLUMN CHART CÁ NHÂN: ĐỐI CHIẾU TỪNG MÔN HỌC --}}
    <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/85 backdrop-blur-xl p-5 shadow-xl glass-card">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold !text-white">So sánh Vắng (%) & Điểm theo Môn</h3>
                <p class="text-[11px] !text-slate-400 mt-0.5">Phát hiện môn học sinh viên hay bỏ tiết hoặc nợ bài nhất</p>
            </div>
            <span class="rounded-md bg-sky-500/15 border border-sky-400/30 px-2 py-0.5 text-[10px] font-extrabold text-sky-300">COLUMN CHART</span>
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
        borderColor: 'rgba(212, 255, 36, 0.35)',
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
                    borderColor: '#0a0d17',
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
                        labels: { color: '#cbd5e1', boxWidth: 10, padding: 8, font: { size: 10, weight: '600' } }
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
                        borderColor: '#D4FF24',
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
                        labels: { color: '#cbd5e1', boxWidth: 8, font: { size: 10, weight: '600' } }
                    },
                    tooltip: tipCfg
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 35 } },
                    y: { beginAtZero: true, suggestedMax: 35, grid: { color: 'rgba(255,255,255,0.07)' }, ticks: { color: '#fda4af', callback: v => v + '%' } }
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
                        labels: { color: '#cbd5e1', boxWidth: 8, font: { size: 10, weight: '600' } }
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
                    x: { grid: { display: false }, ticks: { color: '#cbd5e1', font: { size: 9 }, maxRotation: 30 } },
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.07)' }, ticks: { color: '#cbd5e1', callback: v => v + '%' } }
                }
            }
        });
    }
});
</script>
@endpush
@endif
