<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl">
            <p class="text-sm text-indigo-600 font-medium">Tổng người dùng</p>
            <p class="text-2xl font-bold text-indigo-900 mt-1" id="usr-total">--</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl">
            <p class="text-sm text-emerald-600 font-medium">Sinh viên</p>
            <p class="text-2xl font-bold text-emerald-900 mt-1" id="usr-sv">--</p>
        </div>
        <div class="bg-sky-50 border border-sky-100 p-4 rounded-2xl">
            <p class="text-sm text-sky-600 font-medium">Giảng viên</p>
            <p class="text-2xl font-bold text-sky-900 mt-1" id="usr-gv">--</p>
        </div>
        <div class="bg-rose-50 border border-rose-100 p-4 rounded-2xl">
            <p class="text-sm text-rose-600 font-medium">Quản trị viên</p>
            <p class="text-2xl font-bold text-rose-900 mt-1" id="usr-ad">--</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-4">Phân bổ Vai trò</h3>
            <div class="relative h-64 flex justify-center">
                <canvas id="usr-role-chart"></canvas>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-4">Sinh viên theo Chuyên ngành</h3>
            <div class="relative h-64 flex justify-center">
                <canvas id="usr-major-chart"></canvas>
            </div>
        </div>
    </div>

    {{-- Export --}}
    <div class="flex justify-end gap-3">
        <a href="#" data-base-url="{{ route('admin.reports.users.export') }}" class="export-link bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📥 Excel</span>
        </a>
        <a href="#" data-base-url="{{ route('admin.reports.users.export') }}?format=pdf" class="export-link bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📄 PDF</span>
        </a>
    </div>
</div>
