<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl">
            <p class="text-sm text-indigo-600 font-medium">Điểm trung bình</p>
            <p class="text-2xl font-bold text-indigo-900 mt-1" id="gr-avg">--</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl">
            <p class="text-sm text-emerald-600 font-medium">Điểm cao nhất</p>
            <p class="text-2xl font-bold text-emerald-900 mt-1" id="gr-max">--</p>
        </div>
        <div class="bg-rose-50 border border-rose-100 p-4 rounded-2xl">
            <p class="text-sm text-rose-600 font-medium">Điểm thấp nhất</p>
            <p class="text-2xl font-bold text-rose-900 mt-1" id="gr-min">--</p>
        </div>
        <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl">
            <p class="text-sm text-slate-600 font-medium">Tổng lượt chấm</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="gr-total">--</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-4">Phân bố điểm số</h3>
            <div class="relative h-64">
                <canvas id="gr-dist-chart"></canvas>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-4">Xu hướng điểm trung bình</h3>
            <div class="relative h-64">
                <canvas id="gr-trend-chart"></canvas>
            </div>
        </div>
    </div>

    {{-- Export --}}
    <div class="flex justify-end gap-3">
        <a href="#" data-base-url="{{ route('admin.reports.grades.export') }}" class="export-link bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📥 Excel</span>
        </a>
        <a href="#" data-base-url="{{ route('admin.reports.grades.export') }}?format=pdf" class="export-link bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📄 PDF</span>
        </a>
    </div>
</div>
