@extends('layouts.app')

@section('title', 'Dashboard quản trị')

@section('content')
{{-- Global Filter: desktop ngang, mobile dọc --}}
<div id="filterBarWrapper" class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Tổng quan hoạt động hệ thống</p>
        </div>
        {{-- Nút toggle bộ lọc: chỉ hiện trên mobile --}}
        <button id="mobileFilterToggleBtn" type="button"
            class="flex xl:hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Bộ lọc
        </button>
    </div>

    <div id="filterContainer" class="hidden xl:block">
        <form id="globalDashboardFilterForm"
            class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 bg-white p-3 rounded-2xl shadow-sm border border-slate-200 w-full text-sm">
            <input type="date" name="date_from"
                class="w-full sm:w-auto min-w-0 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 text-slate-600"
                title="Từ ngày">
            <span class="hidden sm:inline text-slate-400">-</span>
            <input type="date" name="date_to"
                class="w-full sm:w-auto min-w-0 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 text-slate-600"
                title="Đến ngày">
            <select name="major_id"
                class="w-full sm:w-auto min-w-0 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 text-slate-600">
                <option value="">Tất cả ngành</option>
                @foreach ($majors as $m)
                    <option value="{{ $m->major_id }}">{{ $m->major_name }}</option>
                @endforeach
            </select>
            <select name="subject_id"
                class="w-full sm:w-auto min-w-0 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 text-slate-600">
                <option value="">Tất cả môn</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
                @endforeach
            </select>
            <div class="flex-1 hidden sm:block"></div>
            <button type="reset" id="resetDashboardFilterBtn"
                class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-4 py-2 text-slate-600 font-medium transition w-full sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Làm mới
            </button>
        </form>
    </div>
</div>

{{-- ===== KPI CARDS (5 cards hàng ngang) ===== --}}
<div class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-5">
    {{-- Card 1: Tổng người dùng --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-indigo-100">Tổng người dùng</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">👥</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ number_format($total_users) }}</p>
        <p class="mt-1 text-xs text-indigo-200">{{ $total_students }} SV · {{ $total_teachers }} GV</p>
    </div>
    {{-- Card 2: Lớp học --}}
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-emerald-100">Lớp học</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">🏫</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $total_classes }}</p>
        <p class="mt-1 text-xs text-emerald-200">{{ $active_classes }} đang hoạt động</p>
    </div>
    {{-- Card 3: Môn học --}}
    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-amber-100">Môn học</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">📚</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $total_subjects }}</p>
        <p class="mt-1 text-xs text-amber-100">Trong chương trình ĐT</p>
    </div>
    {{-- Card 4: Chuyên ngành --}}
    <div class="rounded-2xl bg-gradient-to-br from-fuchsia-500 to-purple-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-fuchsia-100">Chuyên ngành</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">🏛️</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $total_majors }}</p>
        <p class="mt-1 text-xs text-fuchsia-100">Khoa / ngành đào tạo</p>
    </div>
    {{-- Card 5: Chuyên cần --}}
    <div class="rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-600 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-cyan-100">Chuyên cần</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">✅</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $attendance_rate !== null ? $attendance_rate.'%' : '—' }}</p>
        <p class="mt-1 text-xs text-cyan-100">Tỷ lệ toàn hệ thống</p>
    </div>
</div>

{{-- ===== LAYOUT: 1 cột trên mobile / 2 cột trên desktop ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-12 gap-6">
    {{-- ===== KHUNG TRÁI (8/12) ===== --}}
    <div class="lg:col-span-8 space-y-6">

        {{-- 1. Line Chart: Xu hướng điểm TB --}}
        <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <h2 class="font-bold text-slate-800">📈 Xu hướng điểm trung bình</h2>
            </div>
            <div class="relative w-full" style="height:260px">
                <canvas id="scoreTrendChart" style="width:100%!important"></canvas>
            </div>
        </div>

        {{-- 2+3. Hai Doughnut cạnh nhau --}}
        <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
            {{-- Doughnut: Phân bố điểm --}}
            <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="font-bold text-slate-800 mb-4">🍩 Phân bố điểm số</h2>
                <div class="relative w-full" style="height:240px">
                    <canvas id="scoreDistChart" style="width:100%!important"></canvas>
                </div>
            </div>
            {{-- Doughnut: SV theo ngành --}}
            <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="font-bold text-slate-800 mb-4">🍩 Sinh viên theo ngành</h2>
                <div class="relative w-full" style="height:240px">
                    <canvas id="studentMajorChart" style="width:100%!important"></canvas>
                </div>
            </div>
        </div>

        {{-- 4. Horizontal Bar: Top môn điểm cao --}}
        <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <h2 class="font-bold text-slate-800 mb-4">📊 Top môn có điểm trung bình cao nhất</h2>
            <div class="relative w-full" style="height:280px">
                <canvas id="topSubjectsChart" style="width:100%!important"></canvas>
            </div>
        </div>

        {{-- 5. Pie: Điểm danh --}}
        <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
                <div class="w-full">
                    <h2 class="font-bold text-slate-800 mb-4">🥧 Tỷ lệ điểm danh</h2>
                    <div class="relative w-full" style="height:200px">
                        <canvas id="attendanceChart" style="width:100%!important"></canvas>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500 text-white text-sm font-bold">P</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Có mặt</p>
                            <p class="text-lg font-extrabold text-emerald-600" id="attTextP">0</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-rose-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-500 text-white text-sm font-bold">A</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Vắng mặt</p>
                            <p class="text-lg font-extrabold text-rose-600" id="attTextA">0</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500 text-white text-sm font-bold">L</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Đi muộn</p>
                            <p class="text-lg font-extrabold text-amber-600" id="attTextL">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Bảng lịch học hôm nay --}}
        <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-800">📅 Lịch học hôm nay</h2>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">STT</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Slot/Ca</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Lớp</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Môn học</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Phòng</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Giảng viên</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($today_sessions as $i => $s)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 text-slate-600">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if ($s->slot)
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 whitespace-nowrap">
                                            {{ $s->slot->label }} ({{ \Carbon\Carbon::parse($s->slot->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->slot->end_time)->format('H:i') }})
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-700 whitespace-nowrap">{{ $s->classRoom?->class_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $s->classRoom?->subject?->subject_name }}</td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $s->room?->room_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $s->classRoom?->teacher?->full_name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">Hôm nay không có lịch học nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== KHUNG PHẢI (4/12) ===== --}}
    <div class="lg:col-span-4 space-y-6">

        {{-- Thông báo hệ thống --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-800">📢 Thông báo hệ thống</h2>
                <a href="{{ route('admin.notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Quản lý →</a>
            </div>
            <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                @forelse ($recent_notifications as $n)
                    <li class="px-5 py-3 hover:bg-slate-50 transition">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 text-xs">📢</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-700 line-clamp-1">{{ $n->title }}
                                    @if ($n->isSystemWide()) <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600">Hệ thống</span> @endif
                                </p>
                                <p class="text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-400">Chưa có thông báo</li>
                @endforelse
            </ul>
        </div>

        {{-- Bài tập sắp đến hạn --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-800">⏰ Bài tập sắp đến hạn</h2>
            </div>
            <ul class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                @forelse ($upcoming_assignments as $a)
                    <li class="px-5 py-3 hover:bg-slate-50 transition">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ $a->title }}</p>
                                <p class="text-xs text-slate-400">{{ $a->classRoom?->class_name }} · GV: {{ $a->classRoom?->teacher?->full_name }}</p>
                            </div>
                            @php($hoursLeft = now()->diffInHours($a->due_date, false))
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $hoursLeft < 24 ? 'bg-rose-100 text-rose-700 animate-pulse' : ($hoursLeft < 72 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ $a->due_date->diffForHumans() }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-400">Không có bài tập sắp hạn</li>
                @endforelse
            </ul>
        </div>

        {{-- Trạng thái hệ thống --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-800">🖥️ Trạng thái hệ thống</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                @php($statusItems = [
                    ['icon' => '🏫', 'label' => 'Lớp đang hoạt động', 'value' => $system_status['active_classes'], 'color' => 'emerald'],
                    ['icon' => '📝', 'label' => 'Bài tập đang mở', 'value' => $system_status['open_assignments'], 'color' => 'indigo'],
                    ['icon' => '⏳', 'label' => 'Bài chờ chấm', 'value' => $system_status['pending_grading'], 'color' => 'amber'],
                    ['icon' => '💬', 'label' => 'Thảo luận mới hôm nay', 'value' => $system_status['today_discussions'], 'color' => 'cyan'],
                    ['icon' => '🆕', 'label' => 'Người dùng mới (7 ngày)', 'value' => $system_status['new_users_7days'], 'color' => 'fuchsia'],
                ])
                @foreach ($statusItems as $si)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-{{ $si['color'] }}-50 hover:bg-{{ $si['color'] }}-100 transition">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">{{ $si['icon'] }}</span>
                            <span class="text-sm font-medium text-slate-700">{{ $si['label'] }}</span>
                        </div>
                        <span class="text-lg font-extrabold text-{{ $si['color'] }}-600">{{ $si['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.register(ChartDataLabels);

    const pieDatalabelsConfig = {
        anchor: 'center',
        align: 'center',
        color: '#fff',
        font: { weight: 'bold', size: 10 },
        formatter: (value, ctx) => {
            if (!value) return ''; 
            let sum = 0;
            let dataArr = ctx.chart.data.datasets[0].data;
            dataArr.map(data => { sum += Number(data); });
            if (sum === 0) return value;
            let percentage = (value * 100 / sum).toFixed(1) + "%";
            return percentage; // Chỉ hiện % bên trong để tiết kiệm diện tích cho dashboard
        }
    };

    // === 1. Line Chart: Xu hướng điểm TB ===
    const scoreTrendCtx = document.getElementById('scoreTrendChart');
    let scoreTrendChart = new Chart(scoreTrendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Điểm TB',
                data: [],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 },
                datalabels: { display: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 12 } } },
                y: { beginAtZero: true, max: 10, grid: { color: '#f1f5f9' }, ticks: { font: { size: 12 } } }
            }
        }
    });

    const dashboardForm = document.getElementById('globalDashboardFilterForm');

    // === Khởi tạo 4 biểu đồ rỗng (trend đã có ở trên) ===
    const legendPos = window.innerWidth < 1024 ? 'bottom' : 'right';
    const scoreDistChart = new Chart(document.getElementById('scoreDistChart'), {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e', '#6366f1'], borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, layout: { padding: 10 }, plugins: { legend: { position: legendPos, labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } }, tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }, datalabels: pieDatalabelsConfig }, cutout: '50%' }
    });

    const majorColors = ['#6366f1', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#14b8a6', '#ef4444', '#84cc16'];
    const studentMajorChart = new Chart(document.getElementById('studentMajorChart'), {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: majorColors, borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, layout: { padding: 10 }, plugins: { legend: { position: legendPos, labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } }, tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }, datalabels: pieDatalabelsConfig }, cutout: '50%' }
    });

    const topSubjectsChart = new Chart(document.getElementById('topSubjectsChart'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Điểm TB', data: [], backgroundColor: [], borderRadius: 6, barThickness: 24 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }, datalabels: { display: false } }, scales: { x: { beginAtZero: true, max: 10, grid: { color: '#f1f5f9' } }, y: { grid: { display: false }, ticks: { font: { size: 12 } } } } }
    });

    const attendanceChart = new Chart(document.getElementById('attendanceChart'), {
        type: 'pie',
        data: { labels: ['Có mặt (P)', 'Vắng (A)', 'Muộn (L)'], datasets: [{ data: [0, 0, 0], backgroundColor: ['#10b981', '#ef4444', '#f59e0b'], borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, layout: { padding: 10 }, plugins: { legend: { position: legendPos, labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } }, tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }, datalabels: pieDatalabelsConfig } }
    });

    function loadDashboardCharts() {
        const query = new URLSearchParams(new FormData(dashboardForm)).toString();
        fetch("{{ route('admin.dashboard.charts-data') }}?" + query)
            .then(res => res.json())
            .then(data => {
                // 1. Trend
                scoreTrendChart.data.labels = data.scoreTrend.labels;
                scoreTrendChart.data.datasets[0].data = data.scoreTrend.scores;
                scoreTrendChart.update();

                // 2. Score Dist
                scoreDistChart.data.labels = Object.keys(data.scoreDist);
                scoreDistChart.data.datasets[0].data = Object.values(data.scoreDist);
                scoreDistChart.update();

                // 3. Students by Major
                studentMajorChart.data.labels = data.studentsMajor.labels;
                studentMajorChart.data.datasets[0].data = data.studentsMajor.counts;
                studentMajorChart.data.datasets[0].backgroundColor = majorColors.slice(0, data.studentsMajor.labels.length);
                studentMajorChart.update();

                // 4. Top Subjects
                topSubjectsChart.data.labels = data.topSubjects.labels;
                topSubjectsChart.data.datasets[0].data = data.topSubjects.scores;
                topSubjectsChart.data.datasets[0].backgroundColor = data.topSubjects.scores.map((_, i) => majorColors[i % majorColors.length]);
                topSubjectsChart.update();

                // 5. Attendance
                attendanceChart.data.datasets[0].data = [data.attendance.P, data.attendance.A, data.attendance.L];
                attendanceChart.update();
                document.getElementById('attTextP').innerText = new Intl.NumberFormat().format(data.attendance.P);
                document.getElementById('attTextA').innerText = new Intl.NumberFormat().format(data.attendance.A);
                document.getElementById('attTextL').innerText = new Intl.NumberFormat().format(data.attendance.L);
            })
            .catch(err => console.error(err));
    }
    
    // Gọi Ajax lấy data lần đầu
    loadDashboardCharts();

    // Auto submit lọc khi form change
    dashboardForm.addEventListener('change', loadDashboardCharts);

    const dashboardMajorSelect = dashboardForm.querySelector('[name="major_id"]');
    const dashboardSubjectSelect = dashboardForm.querySelector('[name="subject_id"]');

    function fetchDashboardFilterOptions() {
        const query = new URLSearchParams(new FormData(dashboardForm)).toString();
        fetch("{{ route('admin.analytics.filter-options') }}?" + query)
            .then(res => res.json())
            .then(data => {
                const currentSubject = dashboardSubjectSelect.value;
                dashboardSubjectSelect.innerHTML = '<option value="">Tất cả môn</option>';
                data.subjects.forEach(s => {
                    let opt = new Option(s.subject_name, s.subject_id);
                    if (s.subject_id == currentSubject) opt.selected = true;
                    dashboardSubjectSelect.add(opt);
                });
            })
            .catch(err => console.error('Lỗi filter options:', err));
    }

    if(dashboardMajorSelect) {
        dashboardMajorSelect.addEventListener('change', fetchDashboardFilterOptions);
    }

    // Mobile filter toggle
    const mobileFilterToggleBtn = document.getElementById('mobileFilterToggleBtn');
    const filterContainer = document.getElementById('filterContainer');
    if(mobileFilterToggleBtn && filterContainer) {
        mobileFilterToggleBtn.addEventListener('click', () => {
            filterContainer.classList.toggle('hidden');
        });
    }

    // Reset bộ lọc
    const resetDashboardFilterBtn = document.getElementById('resetDashboardFilterBtn');
    if(resetDashboardFilterBtn && dashboardSubjectSelect) {
        resetDashboardFilterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            dashboardForm.reset();
            dashboardSubjectSelect.innerHTML = '<option value="">Tất cả môn</option>';
            loadDashboardCharts();
        });
    }
});
</script>
@endpush
