<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="flex flex-nowrap overflow-x-auto gap-4 pb-3 mb-3 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-indigo-50 border border-indigo-100  p-4 rounded-2xl">
            <p class="text-sm text-indigo-600 font-medium">Tổng số lớp</p>
            <p class="text-2xl font-bold text-indigo-900 mt-1" id="crs-total">--</p>
        </div>
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-emerald-50 border border-emerald-100  p-4 rounded-2xl">
            <p class="text-sm text-emerald-600 font-medium">Đang học</p>
            <p class="text-2xl font-bold text-emerald-900 mt-1" id="crs-active">--</p>
        </div>
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-amber-50 border border-amber-100  p-4 rounded-2xl">
            <p class="text-sm text-amber-600 font-medium">Chờ mở</p>
            <p class="text-2xl font-bold text-amber-900 mt-1" id="crs-pending">--</p>
        </div>
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-rose-50 border border-rose-100  p-4 rounded-2xl">
            <p class="text-sm text-rose-600 font-medium">Đã đóng</p>
            <p class="text-2xl font-bold text-rose-900 mt-1" id="crs-closed">--</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-4">Trạng thái lớp học</h3>
            <div class="relative h-64 flex justify-center">
                <canvas id="crs-status-chart"></canvas>
            </div>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-center items-center text-center">
            <div class="p-4 rounded-full bg-white shadow-sm mb-3">
                <span class="text-3xl">🏫</span>
            </div>
            <h3 class="font-bold text-slate-800 mb-1">Quản lý lớp học hiệu quả</h3>
            <p class="text-sm text-slate-500 max-w-sm">Dữ liệu trạng thái lớp học giúp phân bổ tài nguyên và giảng viên hợp lý hơn.</p>
        </div>
    </div>

    {{-- Preview Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-6">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800">📋 Dữ liệu xem trước (Preview)</h3>
            <span class="text-xs text-slate-500 bg-slate-200 px-2 py-1 rounded-md" id="crs-preview-count">0 bản ghi</span>
        </div>
        <div class="overflow-x-auto overflow-y-auto" style="max-height: 400px;">
            <table class="w-full text-left text-sm text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3">Mã lớp</th>
                        <th class="px-4 py-3">Tên lớp</th>
                        <th class="px-4 py-3">Môn học</th>
                        <th class="px-4 py-3">Chuyên ngành</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3">Ngày tạo</th>
                    </tr>
                </thead>
                <tbody id="crs-preview-tbody" class="divide-y divide-slate-100">
                    <!-- Dữ liệu sẽ được chèn qua JS -->
                </tbody>
            </table>
        </div>
    </div>

    {{-- Export --}}
    <div class="flex justify-end gap-3 mt-4">
        <a href="#" data-base-url="{{ route('admin.reports.courses.export') }}" class="export-link bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📥 Excel</span>
        </a>
        <a href="#" data-base-url="{{ route('admin.reports.courses.export') }}?format=pdf" class="export-link bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <span>📄 PDF</span>
        </a>
    </div>
</div>

