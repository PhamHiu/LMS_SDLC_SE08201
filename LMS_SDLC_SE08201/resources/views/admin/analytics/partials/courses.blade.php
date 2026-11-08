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
            <div class="p-4 rounded-2xl bg-[#1558C9]/20 border border-[#1558C9]/40 shadow-[0_0_15px_rgba(21,88,201,0.4)] mb-3">
                <svg class="w-8 h-8 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 class="font-bold text-slate-800 mb-1">Quản lý lớp học hiệu quả</h3>
            <p class="text-sm text-slate-500 max-w-sm">Dữ liệu trạng thái lớp học giúp phân bổ tài nguyên và giảng viên hợp lý hơn.</p>
        </div>
    </div>

    {{-- Preview Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-6">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Dữ liệu xem trước (Preview)</span>
            </h3>
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
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Excel</span>
        </a>
        <a href="#" data-base-url="{{ route('admin.reports.courses.export') }}?format=pdf" class="export-link bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span>PDF</span>
        </a>
    </div>
</div>

