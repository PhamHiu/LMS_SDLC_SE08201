<div class="space-y-6">
    <div class="flex flex-nowrap overflow-x-auto gap-4 pb-3 mb-3 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-indigo-50 border border-indigo-100  p-4 rounded-2xl">
            <p class="text-sm text-indigo-600 font-medium">Chuyên cần trung bình</p>
            <p class="text-2xl font-bold text-indigo-900 mt-1" id="t-att-rate">--</p>
        </div>
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-emerald-50 border border-emerald-100  p-4 rounded-2xl">
            <p class="text-sm text-emerald-600 font-medium">Lượt Có mặt</p>
            <p class="text-2xl font-bold text-emerald-900 mt-1" id="t-att-p">--</p>
        </div>
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-rose-50 border border-rose-100  p-4 rounded-2xl">
            <p class="text-sm text-rose-600 font-medium">Lượt Vắng</p>
            <p class="text-2xl font-bold text-rose-900 mt-1" id="t-att-a">--</p>
        </div>
        <div class="flex-none w-[220px] md:w-auto md:flex-1 bg-amber-50 border border-amber-100  p-4 rounded-2xl">
            <p class="text-sm text-amber-600 font-medium">Lượt Muộn</p>
            <p class="text-2xl font-bold text-amber-900 mt-1" id="t-att-l">--</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-4">Tỷ lệ theo trạng thái</h3>
            <div class="relative h-64 flex justify-center">
                <canvas id="t-att-pie-chart"></canvas>
            </div>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-center items-center text-center">
            <div class="p-4 rounded-2xl bg-[#1558C9]/20 border border-[#1558C9]/40 shadow-[0_0_15px_rgba(21,88,201,0.4)] mb-3">
                <svg class="w-8 h-8 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-bold text-slate-800 mb-1">Theo dõi chuyên cần thường xuyên</h3>
            <p class="text-sm text-slate-500 max-w-sm">Giúp bạn nắm bắt mức độ chuyên cần của sinh viên và nhắc nhở kịp thời.</p>
        </div>
    </div>

    {{-- Preview Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-6">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Dữ liệu xem trước (Preview)</span>
            </h3>
            <span class="text-xs text-slate-500 bg-slate-200 px-2 py-1 rounded-md" id="t-att-preview-count">0 bản ghi</span>
        </div>
        <div class="overflow-x-auto overflow-y-auto" style="max-height: 400px;">
            <table class="w-full text-left text-sm text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3">Sinh viên</th>
                        <th class="px-4 py-3">Môn học</th>
                        <th class="px-4 py-3">Lớp học</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3">Ngày điểm danh</th>
                    </tr>
                </thead>
                <tbody id="t-att-preview-tbody" class="divide-y divide-slate-100">
                    <!-- Dữ liệu sẽ được chèn qua JS -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end gap-3 mt-4">
        <a href="#" data-base-url="{{ route('teacher.reports.attendance.export') }}" class="export-link bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Xuất Excel Chuyên Cần</span>
        </a>
    </div>
</div>

