@extends('layouts.app')

@section('title', 'Thống kê Lớp học (Giảng viên)')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Thống kê Lớp học</h1>
    <p class="text-sm text-slate-500 mt-1">Phân tích kết quả học tập và chuyên cần của sinh viên</p>
</div>

{{-- ===== FILTER BAR ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6 sticky top-0 z-10">
    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Lớp học</label>
            <select name="class_id" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">-- Tất cả lớp của tôi --</option>
                @foreach ($classes as $c)
                    <option value="{{ $c->class_id }}">{{ $c->class_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Từ ngày</label>
            <input type="date" name="date_from" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Đến ngày</label>
            <input type="date" name="date_to" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        </div>
        <div class="flex items-end gap-3">
            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-medium transition shadow-sm hover:shadow">
                🔍 Lọc
            </button>
        </div>
    </form>
</div>

{{-- ===== TABS ===== --}}
<div class="mb-6 border-b border-slate-200">
    <nav class="-mb-px flex space-x-6" aria-label="Tabs" id="analyticsTabs">
        <button data-tab="grades" class="tab-btn whitespace-nowrap border-b-2 border-indigo-500 py-4 px-1 text-sm font-bold text-indigo-600">
            📊 Điểm số
        </button>
        <button data-tab="attendance" class="tab-btn whitespace-nowrap border-b-2 border-transparent py-4 px-1 text-sm font-medium text-slate-500 hover:border-slate-300 hover:text-slate-700">
            📅 Chuyên cần
        </button>
    </nav>
</div>

{{-- ===== TAB CONTENT ===== --}}
<div id="tabContent" class="min-h-[500px] relative">
    {{-- Loader --}}
    <div id="loader" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center hidden">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent"></div>
    </div>
    
    <div id="grades-content" class="tab-pane">
        @include('teacher.analytics.partials.grades')
    </div>
    <div id="attendance-content" class="tab-pane hidden">
        @include('teacher.analytics.partials.attendance')
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đăng ký DataLabels plugin
    Chart.register(ChartDataLabels);
    Chart.defaults.maintainAspectRatio = false; // Fix lỗi tràn khung
    
    // Config mặc định cho datalabels của biểu đồ tròn
    const pieDatalabelsConfig = {
        anchor: 'end',
        align: 'end',
        offset: 5,
        color: '#475569',
        font: { weight: 'bold', size: 11 },
        formatter: (value, ctx) => {
            if (!value) return ''; // Ẩn nếu = 0
            let sum = 0;
            let dataArr = ctx.chart.data.datasets[0].data;
            dataArr.map(data => { sum += Number(data); });
            if (sum === 0) return value;
            let percentage = (value * 100 / sum).toFixed(1) + "%";
            return value + " (" + percentage + ")";
        }
    };

    let currentTab = 'grades';
    let charts = {};

    const tabs = document.querySelectorAll('.tab-btn');
    const form = document.getElementById('filterForm');
    const loader = document.getElementById('loader');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('border-indigo-500', 'text-indigo-600', 'font-bold');
                t.classList.add('border-transparent', 'text-slate-500', 'font-medium');
            });
            tab.classList.remove('border-transparent', 'text-slate-500', 'font-medium');
            tab.classList.add('border-indigo-500', 'text-indigo-600', 'font-bold');

            document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
            currentTab = tab.dataset.tab;
            document.getElementById(currentTab + '-content').classList.remove('hidden');

            loadData();
        });
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadData();
    });

    function updateExportLinks(queryStr) {
        document.querySelectorAll('.export-link').forEach(link => {
            const baseUrl = link.dataset.baseUrl;
            link.href = baseUrl + (queryStr ? '?' + queryStr : '');
        });
    }

    function loadData() {
        loader.classList.remove('hidden');
        
        const formData = new FormData(form);
        const query = new URLSearchParams(formData).toString();
        
        updateExportLinks(query);

        fetch(`/teacher/analytics/${currentTab}?${query}`)
            .then(res => res.json())
            .then(data => {
                renderContent(currentTab, data);
            })
            .catch(err => {
                console.error("Lỗi", err);
            })
            .finally(() => {
                loader.classList.add('hidden');
            });
    }

    function renderContent(tab, data) {
        if (tab === 'grades') renderGrades(data);
        if (tab === 'attendance') renderAttendance(data);
    }

    function destroyChart(id) {
        if (charts[id]) charts[id].destroy();
    }

    function renderGrades(data) {
        document.getElementById('t-gr-avg').innerText = data.summary.avg_score;
        document.getElementById('t-gr-max').innerText = data.summary.max_score;
        document.getElementById('t-gr-min').innerText = data.summary.min_score;
        document.getElementById('t-gr-total').innerText = data.summary.total_graded;

        destroyChart('t-gr-dist-chart');
        charts['t-gr-dist-chart'] = new Chart(document.getElementById('t-gr-dist-chart'), {
            type: 'bar',
            data: {
                labels: Object.keys(data.distribution),
                datasets: [{
                    label: 'Số bài',
                    data: Object.values(data.distribution),
                    backgroundColor: '#6366f1'
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'right' },
                    datalabels: { display: false }
                }
            }
        });
            }
        });

        // Bảng Preview (Điểm số)
        const tbody = document.getElementById('t-gr-preview-tbody');
        if (tbody) {
            tbody.innerHTML = '';
            document.getElementById('t-gr-preview-count').innerText = `${data.data.length} bản ghi`;
            data.data.forEach(s => {
                const tr = document.createElement('tr');
                tr.classList.add('hover:bg-slate-50', 'transition');
                
                const studentName = s.student ? s.student.full_name : 'N/A';
                const assignTitle = s.assignment ? s.assignment.title : 'N/A';
                const subjectName = s.assignment?.class_room?.subject ? s.assignment.class_room.subject.subject_name : 'N/A';
                const className = s.assignment?.class_room ? s.assignment.class_room.class_name : 'N/A';
                const score = s.score !== null ? s.score : '--';
                const date = new Date(s.graded_at).toLocaleDateString('vi-VN');

                tr.innerHTML = `
                    <td class="px-4 py-3 font-medium text-slate-700">${studentName}</td>
                    <td class="px-4 py-3">${assignTitle}</td>
                    <td class="px-4 py-3">${subjectName}</td>
                    <td class="px-4 py-3">${className}</td>
                    <td class="px-4 py-3 text-center font-bold ${score >= 5 ? 'text-emerald-600' : 'text-rose-600'}">${score}</td>
                    <td class="px-4 py-3 text-xs">${date}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    function renderAttendance(data) {
        document.getElementById('t-att-rate').innerText = data.summary.present_rate + '%';
        document.getElementById('t-att-p').innerText = data.summary.present_count;
        document.getElementById('t-att-a').innerText = data.summary.absent_count;
        document.getElementById('t-att-l').innerText = data.summary.late_count;

        destroyChart('t-att-pie-chart');
        charts['t-att-pie-chart'] = new Chart(document.getElementById('t-att-pie-chart'), {
            type: 'pie',
            data: {
                labels: ['Có mặt', 'Vắng', 'Muộn'],
                datasets: [{
                    data: [data.summary.present_count, data.summary.absent_count, data.summary.late_count],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
                }]
            },
            options: {
                layout: { padding: 30 },
                plugins: {
                    legend: { position: 'right' },
                    datalabels: pieDatalabelsConfig
                }
            }
        });
            }
        });

        // Bảng Preview (Chuyên cần)
        const tbody = document.getElementById('t-att-preview-tbody');
        if (tbody) {
            tbody.innerHTML = '';
            document.getElementById('t-att-preview-count').innerText = `${data.data.length} bản ghi`;
            data.data.forEach(a => {
                const tr = document.createElement('tr');
                tr.classList.add('hover:bg-slate-50', 'transition');
                
                const studentName = a.student ? a.student.full_name : 'N/A';
                const subjectName = a.session?.class_room?.subject ? a.session.class_room.subject.subject_name : 'N/A';
                const className = a.session?.class_room ? a.session.class_room.class_name : 'N/A';
                
                let statusHtml = '';
                if (a.status === 'present') statusHtml = '<span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">Có mặt</span>';
                else if (a.status === 'absent') statusHtml = '<span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-lg text-xs font-bold">Vắng</span>';
                else if (a.status === 'late') statusHtml = '<span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold">Muộn</span>';
                else statusHtml = '<span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold">--</span>';

                const date = new Date(a.attendance_time).toLocaleDateString('vi-VN');

                tr.innerHTML = `
                    <td class="px-4 py-3 font-medium text-slate-700">${studentName}</td>
                    <td class="px-4 py-3">${subjectName}</td>
                    <td class="px-4 py-3">${className}</td>
                    <td class="px-4 py-3 text-center">${statusHtml}</td>
                    <td class="px-4 py-3 text-xs">${date}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    loadData();
});
</script>
@endpush
