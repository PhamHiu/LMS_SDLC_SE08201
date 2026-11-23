@extends('layouts.app')

@section('title', 'Quản lý Dữ liệu RAG — AI Support')

@section('content')
<div class="space-y-6">
    <!-- Header & Nút đồng bộ Vector -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Kho tri thức RAG — BTEC AI Student Support</h1>
            <p class="text-xs text-slate-500 mt-1">Quản lý nội quy, quy chế BTEC (`kb_chunks.jsonl`) và đồng bộ chỉ mục Vector trong MySQL (`btec_kb_embeddings`).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.policies.index') }}" class="inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 hover:bg-blue-100 px-3.5 py-2 text-xs font-bold text-[#1e4f8f] shadow-xs transition">
                <span>Phiên bản Quy chế &amp; Công thức</span>
            </a>
            <button type="button" onclick="openRagModal('addChunkModal')" class="inline-flex items-center gap-1.5 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-3.5 py-2 text-xs font-bold text-white shadow-xs transition cursor-pointer">
                <span>+ Thêm đoạn tri thức</span>
            </button>
            <button type="button" onclick="openRagModal('importFileModal')" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs transition cursor-pointer">
                <span>Import file (.txt / .md / .jsonl)</span>
            </button>
            <form method="POST" action="{{ route('admin.rag.reindex') }}" onsubmit="return confirm('Nhúng lại toàn bộ Vector cho kho tri thức RAG? Quá trình này có thể mất 15-30 giây.')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-xs transition cursor-pointer">
                    <span>Đồng bộ & Nhúng lại Vector</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 3 Thẻ thống kê trạng thái RAG -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Tổng đoạn tri thức (KB Chunks)</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $totalChunks }} <span class="text-xs font-normal text-slate-500">đoạn</span></p>
            <p class="mt-1 text-[11px] text-slate-500">Nguồn file: <code>storage/btec_ai_kb/kb_chunks.jsonl</code></p>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-[#1e4f8f]">Vector đã đánh chỉ mục (MySQL)</p>
            <p class="mt-1 text-2xl font-extrabold text-[#1e4f8f]">{{ $indexedCount }}/{{ $totalChunks }} <span class="text-xs font-normal text-blue-700">vector</span></p>
            <p class="mt-1 text-[11px] text-slate-600 truncate" title="{{ $activeModel }}">Model: <strong>{{ $activeModel }}</strong> ({{ $activeDimension }} chiều)</p>
        </div>

        <div class="rounded-lg border {{ $indexedCount >= $totalChunks && $totalChunks > 0 ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/60' }} p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider {{ $indexedCount >= $totalChunks && $totalChunks > 0 ? 'text-emerald-700' : 'text-amber-800' }}">Trạng thái Hybrid RAG</p>
            <p class="mt-1 text-lg font-extrabold {{ $indexedCount >= $totalChunks && $totalChunks > 0 ? 'text-emerald-700' : 'text-amber-800' }}">
                {{ $indexedCount >= $totalChunks && $totalChunks > 0 ? 'Đã đồng bộ Vector' : 'Cần đồng bộ lại Vector' }}
            </p>
            <p class="mt-1 text-[11px] text-slate-500">
                Cập nhật gần nhất: {{ $lastIndexedAt ? $lastIndexedAt->format('H:i d/m/Y') : 'Chưa đánh chỉ mục' }}
            </p>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm -->
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.rag.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="w-full sm:w-48">
                <select name="topic_id" onchange="this.form.submit()" class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-[#1e4f8f] focus:outline-none">
                    <option value="">-- Tất cả chủ đề --</option>
                    @foreach ($topics as $t)
                        <option value="{{ $t }}" @selected($topicFilter === $t)>Chủ đề {{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[220px]">
                <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo mã chunk, tiêu đề, nội dung quy chế..."
                       class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 focus:border-[#1e4f8f] focus:outline-none">
            </div>
            <button type="submit" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2 text-xs font-bold text-white transition">Lọc</button>
            @if ($search !== '' || $topicFilter !== '')
                <a href="{{ route('admin.rag.index') }}" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 transition">Xóa lọc</a>
            @endif
        </form>
    </div>

    <!-- Bảng danh sách Chunks -->
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3 w-36">Mã / Chủ đề</th>
                    <th class="px-4 py-3">Tiêu đề & Nội dung tri thức</th>
                    <th class="px-4 py-3 w-28">Nguồn</th>
                    <th class="px-4 py-3 w-32 text-center">Vector DB</th>
                    <th class="px-4 py-3 w-36 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($chunks as $chunk)
                    <?php
                        $cid = $chunk['chunk_id'];
                        $vecInfo = $indexedMap->get($cid);
                        $refsStr = implode(', ', (array) ($chunk['source_refs'] ?? []));
                    ?>
                    <tr class="hover:bg-slate-50/70 transition align-top">
                        <td class="px-4 py-3.5">
                            <span class="block font-mono text-xs font-bold text-[#1e4f8f]">{{ $cid }}</span>
                            <span class="mt-1 inline-block rounded bg-slate-100 border border-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ $chunk['topic_id'] ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="font-bold text-slate-800 text-xs">{{ $chunk['title'] ?? 'Không tiêu đề' }}</p>
                            <p class="mt-1 text-xs text-slate-600 line-clamp-3 whitespace-pre-line">{{ $chunk['text'] ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-slate-600 font-mono">
                            {{ $refsStr ?: '—' }}
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @if ($vecInfo)
                                <span class="inline-flex items-center rounded-md bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                    ✓ {{ $vecInfo->dimension }}d
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-amber-50 border border-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                    Chưa nhúng
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                        data-chunk="{{ json_encode($chunk, JSON_UNESCAPED_UNICODE) }}"
                                        onclick="openEditChunkModal(this)"
                                        class="rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:text-[#1e4f8f] px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                                    Sửa
                                </button>
                                <form method="POST" action="{{ route('admin.rag.destroy', $cid) }}" onsubmit="return confirm('Xóa đoạn tri thức {{ $cid }} khỏi kho RAG?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-slate-200 bg-white hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 px-2.5 py-1 text-xs font-semibold text-slate-600 transition cursor-pointer">
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Không tìm thấy đoạn tri thức nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL 1: THÊM ĐOẠN TRI THỨC MỚI -->
<div id="addChunkModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold text-slate-900">Thêm đoạn tri thức mới cho AI Support (RAG)</h3>
            <button type="button" onclick="closeRagModal('addChunkModal')" class="text-slate-500 hover:text-slate-800">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.rag.store') }}" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mã Chunk (để trống tự sinh)</label>
                    <input type="text" name="chunk_id" placeholder="VD: KB-T23-001" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#1e4f8f] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mã Chủ đề *</label>
                    <input type="text" name="topic_id" value="T08" required placeholder="VD: T08, T09, T23" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#1e4f8f] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nguồn tham chiếu</label>
                    <input type="text" name="source_refs" value="S01" placeholder="VD: S01, S08" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#1e4f8f] focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tiêu đề quy định / hướng dẫn *</label>
                <input type="text" name="title" required placeholder="VD: QUY ĐỊNH ĐIỂM DANH & CẤM THI 2026" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-[#1e4f8f] focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nội dung chi tiết *</label>
                <textarea name="text" rows="6" required placeholder="Nhập nội dung chính xác để AI căn cứ trả lời sinh viên..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-[#1e4f8f] focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeRagModal('addChunkModal')" class="rounded-md border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2 text-xs font-bold text-white">Lưu & Nhúng Vector</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: IMPORT FILE HÀNG LOẠT -->
<div id="importFileModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold text-slate-900">Import tài liệu vào Kho RAG</h3>
            <button type="button" onclick="closeRagModal('importFileModal')" class="text-slate-500 hover:text-slate-800">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.rag.import') }}" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Chọn file (.txt, .md, .jsonl) *</label>
                <input type="file" name="kb_file" accept=".txt,.md,.jsonl,.json" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                <p class="mt-1 text-[11px] text-slate-500">File `.txt`/`.md` sẽ tự động cắt nhỏ thành các chunks ~1200 ký tự và nhúng Vector vào MySQL.</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mã Chủ đề mặc định</label>
                    <input type="text" name="topic_id" value="T23" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tiền tố tiêu đề</label>
                    <input type="text" name="title_prefix" placeholder="VD: Quy chế 2026" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeRagModal('importFileModal')" class="rounded-md border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2 text-xs font-bold text-white">Tải lên & Nhúng Vector</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: SỬA ĐOẠN TRI THỨC -->
<div id="editChunkModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold text-slate-900">Chỉnh sửa đoạn tri thức: <span id="editChunkIdLabel" class="font-mono text-[#1e4f8f]"></span></h3>
            <button type="button" onclick="closeRagModal('editChunkModal')" class="text-slate-500 hover:text-slate-800">✕</button>
        </div>
        <form id="editChunkForm" method="POST" action="" class="p-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mã Chủ đề *</label>
                    <input type="text" id="edit_topic_id" name="topic_id" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#1e4f8f] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nguồn tham chiếu</label>
                    <input type="text" id="edit_source_refs" name="source_refs" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#1e4f8f] focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tiêu đề *</label>
                <input type="text" id="edit_title" name="title" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-[#1e4f8f] focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nội dung chi tiết *</label>
                <textarea id="edit_text" name="text" rows="7" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-[#1e4f8f] focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeRagModal('editChunkModal')" class="rounded-md border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2 text-xs font-bold text-white">Lưu & Cập nhật Vector</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRagModal(id) {
        document.getElementById(id)?.classList.remove('hidden');
    }

    function closeRagModal(id) {
        document.getElementById(id)?.classList.add('hidden');
    }

    function openEditChunkModal(btn) {
        const chunk = JSON.parse(btn.getAttribute('data-chunk'));
        const form = document.getElementById('editChunkForm');
        form.action = '{{ url('admin/rag-kb') }}/' + encodeURIComponent(chunk.chunk_id);
        document.getElementById('editChunkIdLabel').textContent = chunk.chunk_id;
        document.getElementById('edit_topic_id').value = chunk.topic_id || '';
        document.getElementById('edit_source_refs').value = (chunk.source_refs || []).join(', ');
        document.getElementById('edit_title').value = chunk.title || '';
        document.getElementById('edit_text').value = chunk.text || '';
        openRagModal('editChunkModal');
    }
</script>
@include('components.toast')
@endsection
