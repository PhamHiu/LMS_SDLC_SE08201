<div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl">
            <p class="text-sm text-indigo-600 font-medium">Điểm trung bình</p>
            <p class="text-2xl font-bold text-indigo-900 mt-1" id="t-gr-avg">--</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl">
            <p class="text-sm text-emerald-600 font-medium">Cao nhất</p>
            <p class="text-2xl font-bold text-emerald-900 mt-1" id="t-gr-max">--</p>
        </div>
        <div class="bg-rose-50 border border-rose-100 p-4 rounded-2xl">
            <p class="text-sm text-rose-600 font-medium">Thấp nhất</p>
            <p class="text-2xl font-bold text-rose-900 mt-1" id="t-gr-min">--</p>
        </div>
        <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl">
            <p class="text-sm text-slate-600 font-medium">Tổng bài chấm</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="t-gr-total">--</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm col-span-2">
            <h3 class="font-bold text-slate-800 mb-4">Phân bố điểm số sinh viên</h3>
            <div class="relative h-72">
                <canvas id="t-gr-dist-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="#" data-base-url="{{ route('teacher.reports.grades.export') }}" class="export-link bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📥 Xuất Excel Điểm</span>
        </a>
    </div>
</div>
