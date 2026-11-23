@extends('layouts.app')

@section('title', 'Quản lý Phiên bản Nội quy & Quy chế — Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs mb-1">
            <a href="{{ route('admin.rag.index') }}" class="font-semibold text-[#1e4f8f] hover:underline">← Quay lại Kho tri thức RAG</a>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Quản lý Phiên bản Nội quy &amp; Quy chế</h1>
        <p class="text-sm text-slate-500 mt-1">Tạo bản nháp quy định, chạy mô phỏng tác động (VD: 4/20 buổi với &gt; 20% vs &ge; 20%), duyệt và xuất bản có hiệu lực theo kỳ.</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
    {{-- CỘT TRÁI: TẠO BẢN NHÁP QUY ĐỊNH MỚI --}}
    <div class="lg:col-span-2 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-3">Tạo phiên bản quy định mới (Trạng thái: NHÁP)</h2>

        {{-- Công cụ AI đề xuất nháp từ văn bản (chỉ đọc dữ liệu, không tự xuất bản) --}}
        <div class="mb-4 rounded-md border border-blue-200 bg-blue-50/50 p-3.5">
            <p class="text-xs font-bold text-[#1e4f8f] mb-1.5">Phân tích văn bản quy định để gợi ý bản nháp (không tự xuất bản)</p>
            <div class="flex flex-col sm:flex-row gap-2 mb-2">
                <input type="text" id="proposeSourceDoc" placeholder="Tên văn bản nguồn (VD: Quyết định 120/QĐ-BTEC 2026)" class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs">
                <button type="button" onclick="proposePolicyDraft()" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-3.5 py-1.5 text-xs font-semibold text-white">Đề xuất bản nháp</button>
            </div>
            <textarea id="proposeDocText" rows="2" placeholder="Dán đoạn văn bản quy chế cần trích xuất..." class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs"></textarea>
            <p id="proposeStatus" class="text-[11px] text-slate-600 mt-1 hidden"></p>
        </div>

        <form method="POST" action="{{ route('admin.policies.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Mã quy tắc</label>
                <input type="text" name="rule_code" id="f_rule_code" value="ATTENDANCE_MAX_ABSENCE" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Tiêu đề phiên bản</label>
                <input type="text" name="title" id="f_title" required placeholder="VD: Quy chế chuyên cần Fall 2026 (từ 20% trở lên)" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Văn bản nguồn</label>
                <input type="text" name="source_document" id="f_source_document" required placeholder="VD: Sổ tay sinh viên 2026" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Điều khoản / Trang</label>
                <input type="text" name="clause_ref" id="f_clause_ref" placeholder="VD: Điều 4.2, trang 18" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1">Trích đoạn gốc</label>
                <textarea name="excerpt" id="f_excerpt" rows="2" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Phạm vi áp dụng</label>
                <select name="scope_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    <option value="GLOBAL">Toàn trường (GLOBAL)</option>
                    <option value="MAJOR">Theo Chuyên ngành (MAJOR)</option>
                    <option value="SUBJECT">Theo Môn học (SUBJECT)</option>
                    <option value="CLASS">Theo Lớp học (CLASS)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Mã học kỳ (tùy chọn)</label>
                <input type="text" name="term_code" placeholder="VD: FALL_2025, SPRING_2026" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Hiệu lực từ ngày</label>
                <input type="date" name="effective_from" value="{{ date('Y-m-d') }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Hiệu lực đến ngày (để trống nếu vô thời hạn)</label>
                <input type="date" name="effective_to" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Loại quy tắc</label>
                <select name="rule_type" id="f_rule_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    <option value="ATTENDANCE_THRESHOLD">Ngưỡng chuyên cần (có công thức)</option>
                    <option value="GENERAL_POLICY">Quy định văn bản chung</option>
                    <option value="UNSUPPORTED_CALCULATION">Công thức mới (cần bổ sung nghiệp vụ)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Độ ưu tiên (số lớn hơn ưu tiên hơn)</label>
                <input type="number" name="priority" value="100" min="1" max="1000" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Toán tử so sánh</label>
                <select name="operator" id="f_operator" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    <option value="GT">Vắng QUÁ (&gt;)</option>
                    <option value="GTE">Vắng TỪ TRỞ LÊN (&ge;)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Ngưỡng (%) &amp; Căn cứ</label>
                <div class="flex gap-2">
                    <input type="number" step="0.1" name="threshold_value" id="f_threshold_value" value="20" class="w-24 rounded-md border border-slate-300 px-3 py-2 text-xs">
                    <input type="hidden" name="unit" value="PERCENT">
                    <select name="basis" class="flex-1 rounded-md border border-slate-300 px-2 py-2 text-xs">
                        <option value="TOTAL_CLASS_SESSIONS">Tổng số buổi của lớp</option>
                        <option value="MARKED_SESSIONS">Số buổi đã điểm danh</option>
                    </select>
                </div>
            </div>
            <div class="sm:col-span-2 pt-2">
                <button type="submit" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-5 py-2 text-xs font-bold text-white">Lưu Bản Nháp</button>
            </div>
        </form>
    </div>

    {{-- CỘT PHẢI: HƯỚNG DẪN QUY TRÌNH DUYỆT --}}
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm text-xs text-slate-600 space-y-2.5">
        <h2 class="text-sm font-bold text-slate-900">Quy trình xuất bản an toàn</h2>
        <p>1. <strong>Tạo Nháp (DRAFT)</strong>: Ghi rõ nguồn văn bản, điều khoản, phạm vi và mốc hiệu lực.</p>
        <p>2. <strong>Xem mô phỏng tác động</strong>: Đối chiếu ngay kết quả trên các ca mẫu (VD: 4/20 buổi) để tránh nhầm giữa <code>&gt; 20%</code> và <code>&ge; 20%</code>.</p>
        <p>3. <strong>Duyệt (APPROVED)</strong> → <strong>Xuất bản (PUBLISHED)</strong>: Hệ thống tự động cập nhật quy chế cho AI Support và xóa cache FAQ.</p>
        <p class="text-amber-700 bg-amber-50 border border-amber-200 rounded p-2.5">Nếu có 2 quy định cùng phạm vi, cùng độ ưu tiên nhưng khác công thức, AI Support sẽ tự phát hiện xung đột và báo cả 2 nguồn thay vì tự suy diễn.</p>
    </div>
</div>

{{-- DANH SÁCH PHIÊN BẢN QUY ĐỊNH & KẾT QUẢ MÔ PHỎNG --}}
<div class="space-y-4">
    @foreach($policies as $p)
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-bold text-slate-500">#{{ $p->policy_id }}</span>
                        <h3 class="text-base font-bold text-slate-900">{{ $p->title }}</h3>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $p->status === 'PUBLISHED' ? 'bg-emerald-100 text-emerald-800' : ($p->status === 'APPROVED' ? 'bg-blue-100 text-blue-800' : ($p->status === 'ARCHIVED' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-800')) }}">
                            {{ $p->status }}
                        </span>
                        @if($p->requires_engineering_support)
                            <span class="rounded-full bg-rose-100 text-rose-800 px-2.5 py-0.5 text-[11px] font-bold">Cần bổ sung nghiệp vụ</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-600 mt-1">
                        Nguồn: <strong>{{ $p->source_document }}</strong> {{ $p->clause_ref ? "({$p->clause_ref})" : '' }}
                        · Phạm vi: <code>{{ $p->scope_type }}</code> {{ $p->term_code ? "· Kỳ: {$p->term_code}" : '' }}
                        · Hiệu lực: {{ $p->effective_from?->format('d/m/Y') }} → {{ $p->effective_to?->format('d/m/Y') ?? 'Vô thời hạn' }}
                        · Ưu tiên: {{ $p->priority }}
                    </p>
                    <p class="text-xs text-slate-700 mt-2 bg-slate-50 border border-slate-200 rounded p-2.5">"{{ $p->excerpt }}"</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if($p->status === 'DRAFT')
                        <form method="POST" action="{{ route('admin.policies.approve', $p) }}">@csrf
                            <button class="rounded-md bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-bold text-white">Duyệt (Approve)</button>
                        </form>
                    @endif
                    @if($p->status === 'APPROVED')
                        <form method="POST" action="{{ route('admin.policies.publish', $p) }}">@csrf
                            <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white">Xuất bản (Publish)</button>
                        </form>
                    @endif
                    @if($p->status === 'PUBLISHED')
                        <form method="POST" action="{{ route('admin.policies.archive', $p) }}">@csrf
                            <button class="rounded-md border border-slate-300 bg-white hover:bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700">Lưu trữ (Archive)</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- BẢNG TÍNH THỬ TÁC ĐỘNG --}}
            @if(!empty($simulations[$p->policy_id]))
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <p class="text-xs font-bold text-slate-700 mb-2">
                        Tính thử tác động (Công thức: {{ $p->operatorLabel() }} {{ $p->threshold_value }}%):
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                        @foreach($simulations[$p->policy_id] as $sim)
                            <div class="rounded border p-2 {{ $sim['violated'] ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}">
                                <div class="font-bold">{{ $sim['label'] }} ({{ $sim['display_percent'] }}%)</div>
                                <div class="text-[11px] mt-0.5">{{ $sim['conclusion'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>

<script>
async function proposePolicyDraft() {
    const src = document.getElementById('proposeSourceDoc').value.trim();
    const txt = document.getElementById('proposeDocText').value.trim();
    const statusEl = document.getElementById('proposeStatus');
    if (!src || !txt) { alert('Vui lòng nhập tên văn bản nguồn và đoạn nội dung quy chế.'); return; }

    const res = await fetch('{{ route("admin.policies.propose") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ source_document: src, document_text: txt })
    });
    const data = await res.json();
    if (data.success && data.proposal) {
        const p = data.proposal;
        document.getElementById('f_title').value = p.title || '';
        document.getElementById('f_source_document').value = p.source_document || src;
        document.getElementById('f_clause_ref').value = p.clause_ref || '';
        document.getElementById('f_excerpt').value = p.excerpt || '';
        if (p.rule_type) document.getElementById('f_rule_type').value = p.rule_type;
        if (p.operator) document.getElementById('f_operator').value = p.operator;
        if (p.threshold_value !== null && p.threshold_value !== undefined) document.getElementById('f_threshold_value').value = p.threshold_value;
        statusEl.textContent = data.notice;
        statusEl.classList.remove('hidden');
    }
}
</script>
@endsection
