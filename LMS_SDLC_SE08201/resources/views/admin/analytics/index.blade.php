@extends('layouts.app')

@section('title', 'Thống kê & Báo cáo (Admin)')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Thống kê & Báo cáo</h1>
        <p class="text-sm text-slate-500 mt-1">Phân tích toàn diện dữ liệu hệ thống</p>
    </div>
</div>

{{-- ===== FILTER BAR ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6 sticky top-0 z-10">
    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Từ ngày</label>
            <input type="date" name="date_from" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Đến ngày</label>
            <input type="date" name="date_to" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Chuyên ngành</label>
            <select name="major_id" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">-- Tất cả --</option>
                @foreach ($majors as $m)
                    <option value="{{ $m->major_id }}">{{ $m->major_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Môn học</label>
            <select name="subject_id" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">-- Tất cả --</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Lớp học</label>
            <select name="class_id" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">-- Tất cả --</option>
                @foreach ($classes as $c)
                    <option value="{{ $c->class_id }}">{{ $c->class_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Giảng viên</label>
            <select name="teacher_id" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">-- Tất cả --</option>
                @foreach ($teachers as $t)
                    <option value="{{ $t->user_id }}">{{ $t->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2 flex items-end gap-3">
            <button type="button" onclick="window.location.reload()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-5 py-2.5 rounded-xl font-medium transition">
                ↻ Reset
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
        <button data-tab="courses" class="tab-btn whitespace-nowrap border-b-2 border-transparent py-4 px-1 text-sm font-medium text-slate-500 hover:border-slate-300 hover:text-slate-700">
            🏫 Khóa học
        </button>
        <button data-tab="users" class="tab-btn whitespace-nowrap border-b-2 border-transparent py-4 px-1 text-sm font-medium text-slate-500 hover:border-slate-300 hover:text-slate-700">
            👥 Người dùng
        </button>
    </nav>
</div>

{{-- ===== TAB CONTENT ===== --}}
<div id="tabContent" class="min-h-[500px] relative">
    {{-- Loader --}}
    <div id="loader" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center hidden">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite]"></div>
    </div>
    
    {{-- Các partials sẽ được import sẵn và ẩn đi, cập nhật qua JS --}}
    <div id="grades-content" class="tab-pane">
        @include('admin.analytics.partials.grades')
    </div>
    <div id="attendance-content" class="tab-pane hidden">
        @include('admin.analytics.partials.attendance')
    </div>
    <div id="courses-content" class="tab-pane hidden">
        @include('admin.analytics.partials.courses')
    </div>
    <div id="users-content" class="tab-pane hidden">
        @include('admin.analytics.partials.users')
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
    let charts = {}; // Lưu trữ instance của Chart.js để destroy khi update

    const tabs = document.querySelectorAll('.tab-btn');
    const form = document.getElementById('filterForm');
    const loader = document.getElementById('loader');

    // Chuyển tab
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

    form.addEventListener('change', () => {
        loadData();
    });

    const majorSelect = form.querySelector('[name="major_id"]');
    const subjectSelect = form.querySelector('[name="subject_id"]');
    const classSelect = form.querySelector('[name="class_id"]');
    const teacherSelect = form.querySelector('[name="teacher_id"]');

    function fetchFilterOptions() {
        const formData = new FormData(form);
        const query = new URLSearchParams(formData).toString();
        fetch(`/admin/analytics/filter-options?${query}`)
            .then(res => res.json())
            .then(data => {
                const currentSubject = subjectSelect.value;
                const currentClass = classSelect.value;
                const currentTeacher = teacherSelect.value;

                subjectSelect.innerHTML = '<option value="">Tất cả</option>';
                data.subjects.forEach(s => {
                    let opt = new Option(s.subject_name, s.subject_id);
                    if (s.subject_id == currentSubject) opt.selected = true;
                    subjectSelect.add(opt);
                });

                classSelect.innerHTML = '<option value="">Tất cả</option>';
                data.classes.forEach(c => {
                    let opt = new Option(c.class_name, c.class_id);
                    if (c.class_id == currentClass) opt.selected = true;
                    classSelect.add(opt);
                });

                teacherSelect.innerHTML = '<option value="">Tất cả</option>';
                data.teachers.forEach(t => {
                    let opt = new Option(t.full_name, t.user_id);
                    if (t.user_id == currentTeacher) opt.selected = true;
                    teacherSelect.add(opt);
                });
            })
            .catch(err => console.error('Lỗi filter:', err));
    }

    [majorSelect, subjectSelect, classSelect].forEach(select => {
        if(select) select.addEventListener('change', fetchFilterOptions);
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

        fetch(`/admin/analytics/${currentTab}?${query}`)
            .then(res => res.json())
            .then(data => {
                renderContent(currentTab, data);
            })
            .catch(err => {
                console.error("Lỗi tải dữ liệu", err);
                alert("Có lỗi xảy ra khi tải dữ liệu.");
            })
            .finally(() => {
                loader.classList.add('hidden');
            });
    }

    function renderContent(tab, data) {
        if (tab === 'grades') renderGrades(data);
        if (tab === 'attendance') renderAttendance(data);
        if (tab === 'courses') renderCourses(data);
        if (tab === 'users') renderUsers(data);
    }

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
        }
    }

    function renderGrades(data) {
        document.getElementById('gr-avg').innerText = data.summary.avg_score;
        document.getElementById('gr-max').innerText = data.summary.max_score;
        document.getElementById('gr-min').innerText = data.summary.min_score;
        document.getElementById('gr-total').innerText = data.summary.total_graded;

        destroyChart('gr-dist-chart');
        charts['gr-dist-chart'] = new Chart(document.getElementById('gr-dist-chart'), {
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

        destroyChart('gr-trend-chart');
        charts['gr-trend-chart'] = new Chart(document.getElementById('gr-trend-chart'), {
            type: 'line',
            data: {
                labels: Object.keys(data.trend),
                datasets: [{
                    label: 'Điểm TB',
                    data: Object.values(data.trend),
                    borderColor: '#10b981',
                    tension: 0.3
                }]
            },
            options: { 
                scales: { y: { min: 0, max: 10 } },
                plugins: {
                    legend: { position: 'right' },
                    datalabels: { display: false }
                }
            }
        });
    }

    function renderAttendance(data) {
        document.getElementById('att-rate').innerText = data.summary.present_rate + '%';
        document.getElementById('att-p').innerText = data.summary.present_count;
        document.getElementById('att-a').innerText = data.summary.absent_count;
        document.getElementById('att-l').innerText = data.summary.late_count;

        destroyChart('att-pie-chart');
        charts['att-pie-chart'] = new Chart(document.getElementById('att-pie-chart'), {
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

        destroyChart('att-trend-chart');
        charts['att-trend-chart'] = new Chart(document.getElementById('att-trend-chart'), {
            type: 'line',
            data: {
                labels: Object.keys(data.trend),
                datasets: [{
                    label: 'Tỷ lệ đi học (%)',
                    data: Object.values(data.trend),
                    borderColor: '#6366f1',
                    tension: 0.3
                }]
            },
            options: { 
                scales: { y: { min: 0, max: 100 } },
                plugins: {
                    legend: { position: 'right' },
                    datalabels: { display: false }
                }
            }
        });
    }

    function renderCourses(data) {
        document.getElementById('crs-total').innerText = data.summary.total_classes;
        document.getElementById('crs-active').innerText = data.summary.active_classes;
        document.getElementById('crs-pending').innerText = data.summary.pending_classes;
        document.getElementById('crs-closed').innerText = data.summary.closed_classes;

        destroyChart('crs-status-chart');
        charts['crs-status-chart'] = new Chart(document.getElementById('crs-status-chart'), {
            type: 'doughnut',
            data: {
                labels: ['Đang học', 'Chờ mở', 'Đã đóng'],
                datasets: [{
                    data: [data.status_chart.active, data.status_chart.pending, data.status_chart.closed],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
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

    function renderUsers(data) {
        document.getElementById('usr-total').innerText = data.summary.total_users;
        document.getElementById('usr-sv').innerText = data.summary.total_students;
        document.getElementById('usr-gv').innerText = data.summary.total_teachers;
        document.getElementById('usr-ad').innerText = data.summary.total_admins;

        destroyChart('usr-role-chart');
        charts['usr-role-chart'] = new Chart(document.getElementById('usr-role-chart'), {
            type: 'pie',
            data: {
                labels: ['Sinh viên', 'Giảng viên', 'Admin'],
                datasets: [{
                    data: [data.summary.total_students, data.summary.total_teachers, data.summary.total_admins],
                    backgroundColor: ['#6366f1', '#10b981', '#f43f5e']
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

        destroyChart('usr-major-chart');
        charts['usr-major-chart'] = new Chart(document.getElementById('usr-major-chart'), {
            type: 'bar',
            data: {
                labels: Object.keys(data.major_chart),
                datasets: [{
                    label: 'Số SV',
                    data: Object.values(data.major_chart),
                    backgroundColor: '#8b5cf6'
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

    // Load data lần đầu
    loadData();
});
</script>
@endpush
