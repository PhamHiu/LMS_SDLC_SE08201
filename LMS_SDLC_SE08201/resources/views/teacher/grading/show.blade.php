@extends('layouts.app')

@section('title', 'Chấm điểm — ' . $submission->student->full_name)

@push('styles')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-container {
            min-height: 150px;
            font-size: 14px;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
        .ql-toolbar {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            background: #f8fafc;
        }
        /* Fix triệt để xung đột Tailwind và Quill.js (lỗi chữ A, icon tam giác đen) */
        .ql-toolbar svg {
            display: inline-block !important;
            vertical-align: baseline !important;
            width: 18px !important;
            height: 18px !important;
            max-width: none !important;
        }
        .ql-snow .ql-picker-label {
            display: flex !important;
            align-items: center !important;
        }
        .ql-snow .ql-picker-label::before,
        .ql-snow .ql-picker-item::before {
            display: inline-block;
            line-height: normal;
        }
        .ql-snow .ql-color-picker .ql-picker-label svg, 
        .ql-snow .ql-icon-picker .ql-picker-label svg {
            width: 18px !important;
            height: 18px !important;
            right: 0 !important;
        }
        .ql-snow .ql-picker-options {
            border-color: #e2e8f0 !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
        }
        .ql-editor ul, .ql-editor ol {
            padding-left: 1.5rem !important;
        }
        .ql-editor li {
            list-style-type: inherit !important;
        }
        .ql-editor {
            font-family: inherit;
        }
        /* Custom scrollbar cho khu vực xem bài */
        #viewer-container::-webkit-scrollbar { width: 8px; height: 8px; }
        #viewer-container::-webkit-scrollbar-track { background: #030407; border-radius: 4px; }
        #viewer-container::-webkit-scrollbar-thumb { background: #1558C9; border-radius: 4px; }
        #viewer-container::-webkit-scrollbar-thumb:hover { background: #D4FF24; }
        
        .tool-btn {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
            border: 1px solid rgba(255, 255, 255, 0.16) !important;
            border-radius: 0.5rem !important;
            padding: 0.35rem 0.65rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            cursor: pointer !important;
            user-select: none !important;
        }
        .tool-btn:hover {
            background-color: rgba(21, 88, 201, 0.35) !important;
            color: #ffffff !important;
            border-color: #D4FF24 !important;
            transform: translateY(-1px) !important;
        }
        .tool-btn.active {
            background-color: #1558C9 !important;
            color: #D4FF24 !important;
            border-color: #D4FF24 !important;
            box-shadow: 0 0 14px rgba(212, 255, 36, 0.45), 0 0 6px rgba(21, 88, 201, 0.8) !important;
            font-weight: 800 !important;
            transform: scale(1.05) !important;
        }
        .tool-btn.active svg, .tool-btn.active span {
            color: #D4FF24 !important;
            filter: drop-shadow(0 0 6px rgba(212, 255, 36, 0.6));
        }
        .color-btn.active {
            transform: scale(1.25);
            box-shadow: 0 0 0 2px #030407, 0 0 0 4px #D4FF24;
        }
    </style>
@endpush

@section('content')
<div class="mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-slate-800">Bài làm của {{ $submission->student->full_name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $assignment->title }} · Lớp {{ $assignment->classRoom?->class_name }}</p>
    </div>
    <a href="{{ route('teacher.grading.index', $assignment) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-800">
        ← Quay lại
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-140px)] min-h-[600px]">
    <!-- CỘT TRÁI: Khu vực xem và vẽ -->
    <div class="lg:col-span-3 flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <!-- Toolbar Vẽ Mới (Giống thiết kế) -->
        <div class="flex flex-col border-b border-slate-200 bg-slate-50 text-sm">
            <!-- Row 1 -->
            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-200">
                <div class="flex items-center gap-2 text-slate-600 font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    File bài nộp
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-view-original" onclick="switchView('original')" class="tool-btn !bg-white/5 hover:!bg-white/10 !text-slate-300">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Bản gốc học sinh</span>
                    </button>
                    <button type="button" id="btn-view-graded" onclick="switchView('graded')" class="tool-btn !bg-[#1558C9] !text-[#D4FF24] !border-[#D4FF24]/60 shadow-[0_0_12px_rgba(21,88,201,0.5)]">
                        <svg class="w-3.5 h-3.5 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Chấm bài & nhận xét</span>
                    </button>
                    @if($submission->submit_file_path)
                    <button type="button" id="btn-view-graded-result" onclick="switchView('graded-result')" class="tool-btn !bg-emerald-500/20 !text-emerald-300 !border-emerald-500/40">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Bài đã chấm</span>
                    </button>
                    @endif
                    <a href="{{ route('teacher.grading.download', $submission) }}" class="tool-btn !bg-white/5 hover:!bg-white/10 !text-slate-300">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Tải xuống gốc</span>
                    </a>
                    @if($submission->submit_file_path)
                    <a href="{{ asset('storage/' . $submission->submit_file_path) }}" download class="tool-btn !bg-white/5 hover:!bg-white/10 !text-slate-300">
                        <svg class="w-3.5 h-3.5 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Tải bài đã chấm</span>
                    </a>
                    @endif
                    @if($submission->other_file_path)
                    <button type="button" id="btn-view-other" onclick="switchView('other')" class="tool-btn !bg-white/5 hover:!bg-white/10 !text-slate-300">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span>File tải thêm</span>
                    </button>
                    @endif
                </div>
            </div>
            
            <!-- Row 2 -->
            <div id="drawing-toolbar" class="flex flex-wrap items-center justify-between px-3 py-2.5 gap-2 border-t border-white/10 bg-[#030407]/60">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" class="tool-btn active" data-tool="select">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        <span>Chọn chữ</span>
                    </button>
                    <button type="button" class="tool-btn" data-tool="draw">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Vẽ</span>
                    </button>
                    <button type="button" class="tool-btn" data-tool="line">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 19L20 5"/></svg>
                        <span>Thẳng</span>
                    </button>
                    <button type="button" class="tool-btn" data-tool="rect">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
                        <span>Ô vuông</span>
                    </button>
                    <button type="button" class="tool-btn" data-tool="arrow">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 17L17 7m0 0H8m9 0v9"/></svg>
                        <span>Mũi tên</span>
                    </button>
                    <button type="button" class="tool-btn" data-tool="highlight">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Đánh dấu</span>
                    </button>
                    <button type="button" class="tool-btn" data-tool="text" title="Click vào bài để viết nhận xét">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M12 6v14"/></svg>
                        <span>T Viết chữ</span>
                    </button>
                    <div class="h-5 w-px bg-white/20 mx-1"></div>
                    <button type="button" onclick="canvasObj.undo()" class="tool-btn hover:text-white" title="Hoàn tác nét vẽ cuối (Ctrl+Z)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span>Hoàn tác</span>
                    </button>
                    <button type="button" onclick="canvasObj.clearDrawings()" class="tool-btn !text-rose-400 !border-rose-500/30 hover:!bg-rose-500/20 hover:!text-white" title="Xóa toàn bộ nhận xét">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Xóa hết</span>
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1 text-xs">
                        <span class="text-slate-500 uppercase">Cỡ:</span>
                        <select id="brush-size" class="border border-slate-300 rounded px-1 py-0.5 text-xs outline-none">
                            <option value="2">Nhỏ (2px)</option>
                            <option value="4" selected>Vừa (4px)</option>
                            <option value="8">Lớn (8px)</option>
                            <option value="18">Rất lớn (18px)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <span class="text-slate-500 uppercase">Màu:</span>
                        <button type="button" class="color-btn active h-4 w-4 rounded-full bg-[#ef4444] text-[#ef4444] border border-transparent" data-color="#ef4444"></button>
                        <button type="button" class="color-btn h-4 w-4 rounded-full bg-[#3b82f6] text-[#3b82f6] border border-transparent" data-color="#3b82f6"></button>
                        <button type="button" class="color-btn h-4 w-4 rounded-full bg-[#22c55e] text-[#22c55e] border border-transparent" data-color="#22c55e"></button>
                        <button type="button" class="color-btn h-4 w-4 rounded-full bg-[#0f172a] text-[#0f172a] border border-transparent" data-color="#0f172a"></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Canvas Container -->
        <div id="viewer-container" class="flex-1 overflow-auto bg-slate-100 p-4 relative">
            @php
                // DB mới: bài gốc học sinh nộp nằm ở cột student_submit
                $studentFile = $submission->student_submit ?? $submission->submit_file_path;
                $ext = strtolower(pathinfo($studentFile ?? '', PATHINFO_EXTENSION));
                $supported = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
            @endphp

            @if(!$studentFile)
                <div class="flex h-full flex-col items-center justify-center text-slate-400">
                    <svg class="mb-3 h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p>Học sinh chưa nộp file đính kèm.</p>
                </div>
            @elseif(!$supported)
                <div class="flex h-full flex-col items-center justify-center text-slate-500 text-center">
                    <div class="mb-4 h-16 w-16 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-500">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-lg text-slate-700">Định dạng file .{{ $ext }}</h3>
                    <p class="mt-2 text-sm text-slate-500 max-w-sm">Hệ thống chỉ hỗ trợ xem trước và chấm trực tiếp trên file PDF hoặc Ảnh. Vui lòng tải file này về máy để chấm.</p>
                    <a href="{{ route('teacher.grading.download', $submission) }}" class="mt-4 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        ⬇ Tải bài về máy
                    </a>
                </div>
            @else
                <div id="canvas-wrapper" class="relative shadow-md bg-white transition-all mx-auto" style="width: fit-content;">
                    <!-- File URL để JS nạp -->
                    <input type="hidden" id="file-url" value="{{ asset('storage/' . $studentFile) }}">
                    <input type="hidden" id="file-ext" value="{{ $ext }}">
                    <canvas id="grading-canvas"></canvas>
                </div>
                
                <!-- Viewer cho file tải thêm (other_file) -->
                @if($submission->other_file_path)
                <iframe id="other-file-iframe" class="hidden w-full h-full border-0 bg-white shadow-md rounded-lg" data-src="{{ asset('storage/' . $submission->other_file_path) }}"></iframe>
                @endif

                <div id="loading-indicator" class="absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-sm z-50">
                    <div class="flex flex-col items-center">
                        <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
                        <p class="mt-3 font-medium text-slate-600">Đang tải tài liệu...</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- CỘT PHẢI: Form chấm điểm -->
    <div class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-4">
            <h3 class="font-bold text-slate-800 uppercase tracking-wider text-sm flex items-center gap-2">
                <span class="text-[#ef4444]">🖍</span> NHẬP ĐIỂM & ĐÁNH GIÁ
            </h3>
        </div>
        <div class="flex-1 overflow-auto p-5">
            <form id="gradingForm" method="POST" action="{{ route('teacher.grading.grade', $submission) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <!-- Nút ẩn chứa base64 của canvas -->
                <input type="hidden" name="canvas_image_data" id="canvas_image_data">
                
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Điểm số (tối đa {{ $assignment->max_score }})</label>
                    <input type="number" name="score" value="{{ $submission->score }}" min="0" max="{{ $assignment->max_score }}" step="0.25" required
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-lg font-bold text-indigo-700 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Nhận xét chung</label>
                    <!-- Quill Editor container -->
                    <div id="quill-editor">{!! $submission->feedback !!}</div>
                    <input type="hidden" name="feedback" id="feedback-input">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tải file sửa khác (nếu có)</label>
                    <input type="file" name="other_file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 outline-none file:mr-4 file:rounded file:border-0 file:bg-indigo-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-indigo-600">
                    @if($submission->other_file_path)
                    <div class="mt-2 text-sm text-slate-600">
                        File hiện tại: <a href="{{ asset('storage/' . $submission->other_file_path) }}" target="_blank" class="text-blue-600 hover:underline break-all">{{ basename($submission->other_file_path) }}</a>
                    </div>
                    @endif
                </div>
            </form>
        </div>
        <div class="border-t border-slate-100 bg-slate-50 p-5">
            <button type="button" onclick="submitGrading()" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#059669] px-5 py-3 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition-colors">
                💾 Lưu điểm & Ghi chú
            </button>
        </div>
    </div>
</div>
@include('components.toast')
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
    // 1. Khởi tạo Quill Editor
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Nhập nhận xét cho bài làm của sinh viên...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }]
            ]
        }
    });

    // 2. Logic Canvas & PDF
    const fileUrlObj = document.getElementById('file-url');
    const isSupported = !!fileUrlObj;
    
    let canvasObj = null;
    let fabricCanvas = null;
    let drawingHistory = [];
    
    // Hàm chuyển đổi chế độ xem (Bản gốc / Chấm bài)
    window.switchView = function(view) {
        if (!fabricCanvas) return;
        const isGraded = view === 'graded';
        const isOther = view === 'other';
        const isGradedResult = view === 'graded-result';
        
        // Cập nhật giao diện nút bấm
        const btnOriginal = document.getElementById('btn-view-original');
        const btnGraded = document.getElementById('btn-view-graded');
        const btnGradedResult = document.getElementById('btn-view-graded-result');
        const btnOther = document.getElementById('btn-view-other');
        
        const activeClass = "flex items-center gap-1 rounded bg-blue-500 px-3 py-1 text-white hover:bg-blue-600 font-medium text-xs border border-transparent";
        const inactiveClass = "flex items-center gap-1 rounded bg-slate-200 px-3 py-1 text-slate-700 hover:bg-slate-300 font-medium text-xs border border-transparent";
        const gradedActiveClass = "flex items-center gap-1 rounded bg-orange-500 px-3 py-1 text-white hover:bg-orange-600 font-medium text-xs border border-transparent";

        if(btnOriginal) btnOriginal.className = (view === 'original') ? activeClass : inactiveClass;
        if(btnGraded) btnGraded.className = isGraded ? gradedActiveClass : inactiveClass;
        if(btnGradedResult) btnGradedResult.className = isGradedResult ? activeClass : inactiveClass;
        if(btnOther) btnOther.className = isOther ? activeClass : inactiveClass;
        
        // Ẩn hiện các màn hình
        const canvasWrapper = document.getElementById('canvas-wrapper');
        const iframeViewer = document.getElementById('other-file-iframe');
        
        if ((isOther || isGradedResult) && iframeViewer) {
            canvasWrapper.style.display = 'none';
            iframeViewer.classList.remove('hidden');
            
            // Lấy URL tương ứng
            let targetUrl = '';
            if (isOther) targetUrl = "{{ $submission->other_file_path ? asset('storage/' . $submission->other_file_path) : '' }}";
            if (isGradedResult) targetUrl = "{{ $submission->submit_file_path ? asset('storage/' . $submission->submit_file_path) : '' }}";
            
            iframeViewer.src = targetUrl;
            
            // Ẩn thanh công cụ
            const toolbar = document.getElementById('drawing-toolbar');
            if (toolbar) toolbar.style.display = 'none';
            return; // Dừng tại đây cho mode dùng iframe
        } else {
            canvasWrapper.style.display = 'block';
            if(iframeViewer) {
                iframeViewer.classList.add('hidden');
                iframeViewer.src = ''; // Clear bộ nhớ
            }
        }
        
        // Logic cho Original / Graded
        const toolbar = document.getElementById('drawing-toolbar');
        if (toolbar) toolbar.style.display = isGraded ? 'flex' : 'none';
        
        // Ẩn/hiện các nét vẽ trên canvas (tất cả các object foreground)
        fabricCanvas.getObjects().forEach(obj => {
            obj.set('visible', isGraded);
        });
        
        // Cập nhật chế độ vẽ
        if (isGraded) {
            const activeTool = document.querySelector('.tool-btn.active');
            if (activeTool && (activeTool.dataset.tool === 'draw' || activeTool.dataset.tool === 'highlight')) {
                fabricCanvas.isDrawingMode = true;
            }
        } else {
            fabricCanvas.isDrawingMode = false;
        }
        
        fabricCanvas.renderAll();
    };

    if (isSupported) {
        const fileUrl = fileUrlObj.value;
        const fileExt = document.getElementById('file-ext').value;
        const loadingDiv = document.getElementById('loading-indicator');
        
        // Khởi tạo Fabric
        fabricCanvas = new fabric.Canvas('grading-canvas', {
            isDrawingMode: false
        });
        
        // Cấu hình bút vẽ mặc định
        fabricCanvas.freeDrawingBrush.color = '#ef4444';
        fabricCanvas.freeDrawingBrush.width = 3;

        // Bắt sự kiện lưu lịch sử để Undo
        fabricCanvas.on('path:created', function(e) {
            drawingHistory.push(e.path);
        });
        fabricCanvas.on('object:added', function(e) {
            if (e.target.type === 'i-text') drawingHistory.push(e.target);
        });

        let isDrawingShape = false;
        let origX, origY;
        let currentShape;

        fabricCanvas.on('mouse:down', function(o) {
            const toolbar = document.getElementById('drawing-toolbar');
            if (toolbar && toolbar.style.display === 'none') return; // Không cho vẽ ở chế độ Bản gốc

            const activeBtn = document.querySelector('.tool-btn.active');
            if (!activeBtn) return;
            const tool = activeBtn.dataset.tool;

            // Xử lý viết chữ / note trực tiếp tại vị trí click trên bài nộp
            if (tool === 'text') {
                if (o.target && (o.target.type === 'i-text' || o.target.type === 'textbox')) {
                    o.target.enterEditing();
                    setTimeout(() => o.target.hiddenTextarea && o.target.hiddenTextarea.focus(), 50);
                    fabricCanvas.renderAll();
                    return;
                }
                const pointer = fabricCanvas.getPointer(o.e);
                const color = document.querySelector('.color-btn.active')?.dataset.color || '#ef4444';
                const strokeSize = parseInt(document.getElementById('brush-size').value);
                const fontSize = Math.max(16, strokeSize * 3 + 10);

                const text = new fabric.IText('Ghi chú nhận xét...', {
                    left: pointer.x,
                    top: pointer.y,
                    fontFamily: 'Be Vietnam Pro, Arial, sans-serif',
                    fill: color,
                    fontSize: fontSize,
                    editingBorderColor: '#1558C9',
                    cursorColor: color,
                    selectable: true,
                    evented: true,
                    hasControls: true
                });

                fabricCanvas.add(text);
                fabricCanvas.setActiveObject(text);
                text.enterEditing();
                text.selectAll();
                setTimeout(() => text.hiddenTextarea && text.hiddenTextarea.focus(), 50);
                fabricCanvas.renderAll();
                drawingHistory.push(text);
                return;
            }

            if (!['line', 'rect', 'arrow', 'highlight'].includes(tool)) return;
            
            isDrawingShape = true;
            const pointer = fabricCanvas.getPointer(o.e);
            origX = pointer.x;
            origY = pointer.y;
            let color = document.querySelector('.color-btn.active').dataset.color;
            const strokeWidth = parseInt(document.getElementById('brush-size').value) * (tool === 'rect' ? 1 : 1.5);

            if (tool === 'line' || tool === 'arrow') {
                currentShape = new fabric.Line([origX, origY, origX, origY], {
                    stroke: color,
                    strokeWidth: strokeWidth,
                    selectable: false,
                    evented: false
                });
                fabricCanvas.add(currentShape);
            } else if (tool === 'rect') {
                currentShape = new fabric.Rect({
                    left: origX,
                    top: origY,
                    originX: 'left',
                    originY: 'top',
                    width: 0,
                    height: 0,
                    stroke: color,
                    strokeWidth: strokeWidth,
                    fill: 'transparent',
                    selectable: false,
                    evented: false
                });
                fabricCanvas.add(currentShape);
            } else if (tool === 'highlight') {
                // Đánh dấu kiểu ô vuông với màu trong suốt
                // Convert hex color to rgba for opacity
                let r = parseInt(color.slice(1, 3), 16),
                    g = parseInt(color.slice(3, 5), 16),
                    b = parseInt(color.slice(5, 7), 16);
                let rgbaColor = `rgba(${r}, ${g}, ${b}, 0.3)`;

                currentShape = new fabric.Rect({
                    left: origX,
                    top: origY,
                    originX: 'left',
                    originY: 'top',
                    width: 0,
                    height: 0,
                    strokeWidth: 0, // Không có viền
                    fill: rgbaColor, // Nền mờ 30%
                    selectable: false,
                    evented: false
                });
                fabricCanvas.add(currentShape);
            }
        });

        fabricCanvas.on('mouse:move', function(o) {
            if (!isDrawingShape) return;
            const pointer = fabricCanvas.getPointer(o.e);
            const tool = document.querySelector('.tool-btn.active').dataset.tool;

            if (tool === 'line' || tool === 'arrow') {
                currentShape.set({ x2: pointer.x, y2: pointer.y });
            } else if (tool === 'rect' || tool === 'highlight') {
                if(origX > pointer.x) { currentShape.set({ left: Math.abs(pointer.x) }); }
                if(origY > pointer.y) { currentShape.set({ top: Math.abs(pointer.y) }); }
                currentShape.set({ width: Math.abs(origX - pointer.x) });
                currentShape.set({ height: Math.abs(origY - pointer.y) });
            }
            fabricCanvas.renderAll();
        });

        fabricCanvas.on('mouse:up', function(o) {
            if (!isDrawingShape) return;
            isDrawingShape = false;
            
            const tool = document.querySelector('.tool-btn.active').dataset.tool;
            const color = document.querySelector('.color-btn.active').dataset.color;
            const strokeWidth = parseInt(document.getElementById('brush-size').value) * 1.5;
            
            if (tool === 'arrow') {
                const pointer = fabricCanvas.getPointer(o.e);
                const angle = Math.atan2(pointer.y - origY, pointer.x - origX) * 180 / Math.PI;
                const head = new fabric.Triangle({
                    width: strokeWidth * 4, 
                    height: strokeWidth * 4, 
                    fill: color, 
                    left: pointer.x, 
                    top: pointer.y,
                    originX: 'center',
                    originY: 'center',
                    angle: angle + 90,
                    selectable: false,
                    evented: false
                });
                
                const group = new fabric.Group([currentShape, head], {
                    selectable: false,
                    evented: false
                });
                fabricCanvas.remove(currentShape);
                fabricCanvas.add(group);
                currentShape = group;
            }

            if (currentShape) {
                currentShape.setCoords();
                drawingHistory.push(currentShape);
            }
        });

        const loadCanvasBg = (imgUrl, width, height, visualWidth = null) => {
            fabric.Image.fromURL(imgUrl, function(img) {
                fabricCanvas.setWidth(width);
                fabricCanvas.setHeight(height);
                fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas));
                
                // Nếu có visualWidth (ảnh bị thu nhỏ do quá dài), ta stretch CSS ra cho to để dễ nhìn
                if (visualWidth && visualWidth > width) {
                    const visualHeight = height * (visualWidth / width);
                    fabricCanvas.setDimensions({
                        width: visualWidth + 'px',
                        height: visualHeight + 'px'
                    }, { cssOnly: true });
                }
                
                loadingDiv.style.display = 'none';
            });
        };

        if (fileExt === 'pdf') {
            // Dùng PDF.js để render trang đầu tiên thành ảnh
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            
            pdfjsLib.getDocument(fileUrl).promise.then(async function(pdf) {
                try {
                    const numPages = pdf.numPages;
                    const containerWidth = document.getElementById('viewer-container').clientWidth - 40;
                    
                    // Lấy scale từ trang 1
                    const firstPage = await pdf.getPage(1);
                    const unscaledViewport = firstPage.getViewport({scale: 1.0});
                    const scale = containerWidth / unscaledViewport.width;
                    
                    // Tạo một canvas tổng chứa tất cả các trang
                    const combinedCanvas = document.createElement('canvas');
                    const combinedCtx = combinedCanvas.getContext('2d');
                    
                    // Tính tổng chiều cao
                    let totalHeight = 0;
                    let viewports = [];
                    for(let i = 1; i <= numPages; i++) {
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({scale: scale});
                        viewports.push({page, viewport});
                        totalHeight += viewport.height;
                    }
                    
                    // FIX: Nếu PDF quá dài (> 32000px), thu nhỏ scale để canvas không bị quá giới hạn trình duyệt
                    const MAX_CANVAS_HEIGHT = 32000;
                    let finalWidth = containerWidth;
                    if (totalHeight > MAX_CANVAS_HEIGHT) {
                        const finalScale = scale * (MAX_CANVAS_HEIGHT / totalHeight);
                        totalHeight = 0;
                        for(let i = 0; i < viewports.length; i++) {
                            const newViewport = viewports[i].page.getViewport({scale: finalScale});
                            viewports[i].viewport = newViewport;
                            totalHeight += newViewport.height;
                        }
                        finalWidth = viewports[0].viewport.width;
                    }
                    
                    combinedCanvas.width = finalWidth;
                    combinedCanvas.height = totalHeight;
                    
                    // Render từng trang và vẽ lên canvas tổng
                    let currentY = 0;
                    for(let i = 0; i < viewports.length; i++) {
                        const {page, viewport} = viewports[i];
                        
                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = viewport.width;
                        tempCanvas.height = viewport.height;
                        const ctx = tempCanvas.getContext('2d');
                        
                        await page.render({canvasContext: ctx, viewport: viewport}).promise;
                        
                        combinedCtx.drawImage(tempCanvas, 0, currentY);
                        // Thêm 1 đường kẻ xám nhạt phân cách giữa các trang (nếu có nhiều trang)
                        if (i > 0) {
                            combinedCtx.fillStyle = '#e2e8f0'; // slate-200
                            combinedCtx.fillRect(0, currentY - 2, finalWidth, 4);
                        }
                        
                        currentY += viewport.height;
                    }
                    
                    const bgImg = combinedCanvas.toDataURL('image/png');
                    loadCanvasBg(bgImg, combinedCanvas.width, combinedCanvas.height, containerWidth);
                } catch(err) {
                    console.error("Lỗi render các trang PDF:", err);
                    loadingDiv.innerHTML = "<p class='text-red-500'>Lỗi khi hiển thị nội dung PDF.</p>";
                }
            }).catch(err => {
                console.error("Lỗi đọc PDF:", err);
                loadingDiv.innerHTML = "<p class='text-red-500'>Lỗi đọc file PDF gốc.</p>";
            });
        } else {
            // Là file ảnh thông thường
            const imgObj = new Image();
            imgObj.onload = function() {
                // Căn chỉnh Ảnh vừa khít chiều rộng (Fit to Width)
                const containerWidth = document.getElementById('viewer-container').clientWidth - 40;
                let w = this.width;
                let h = this.height;
                
                // Scale ảnh để fit-width nếu nó lớn hơn hoặc nhỏ hơn khung
                const scale = containerWidth / w;
                w = containerWidth;
                h = h * scale;
                
                // Create temporary canvas to resize image
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = w;
                tempCanvas.height = h;
                tempCanvas.getContext('2d').drawImage(this, 0, 0, w, h);
                loadCanvasBg(tempCanvas.toDataURL('image/png'), w, h);
            };
            imgObj.src = fileUrl;
        }

        // Logic thanh công cụ (Toolbar)
        const toolBtns = document.querySelectorAll('.tool-btn');
        const brushSizeSelect = document.getElementById('brush-size');
        
        const updateBrush = () => {
            const size = parseInt(brushSizeSelect.value);
            const activeTool = document.querySelector('.tool-btn.active').dataset.tool;
            
            if (activeTool === 'highlight') {
                fabricCanvas.freeDrawingBrush.width = size * 3; // Highlighter to hơn
                fabricCanvas.freeDrawingBrush.color = document.querySelector('.color-btn.active').dataset.color + '80'; // 50% opacity
            } else {
                fabricCanvas.freeDrawingBrush.width = size;
                fabricCanvas.freeDrawingBrush.color = document.querySelector('.color-btn.active').dataset.color;
            }
        };

        brushSizeSelect.addEventListener('change', updateBrush);

        toolBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                toolBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const tool = btn.dataset.tool;
                
                const isShapeTool = ['line', 'rect', 'arrow', 'highlight'].includes(tool);
                fabricCanvas.isDrawingMode = (tool === 'draw');
                fabricCanvas.selection = !isShapeTool; 
                fabricCanvas.forEachObject(o => { o.selectable = !isShapeTool; o.evented = !isShapeTool; });
                
                if (fabricCanvas.isDrawingMode) updateBrush();
                
                if (tool === 'text') {
                    fabricCanvas.defaultCursor = 'text';
                    fabricCanvas.selection = true;
                    fabricCanvas.forEachObject(o => {
                        if (o.type === 'i-text' || o.type === 'textbox') {
                            o.selectable = true;
                            o.evented = true;
                        }
                    });
                } else {
                    fabricCanvas.defaultCursor = 'default';
                }
            });
        });

        // Double click để sửa bất kỳ đoạn chữ nào
        fabricCanvas.on('mouse:dblclick', function(o) {
            if (o.target && (o.target.type === 'i-text' || o.target.type === 'textbox')) {
                o.target.enterEditing();
                o.target.selectAll();
                setTimeout(() => o.target.hiddenTextarea && o.target.hiddenTextarea.focus(), 50);
                fabricCanvas.renderAll();
            }
        });

        // Bắt sự kiện bàn phím (Delete/Backspace để xóa text/chữ)
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Delete' || e.key === 'Backspace') {
                const activeObject = fabricCanvas.getActiveObject();
                // Chỉ xóa nếu không phải đang gõ chữ
                if (activeObject && (!activeObject.isEditing)) {
                    fabricCanvas.remove(activeObject);
                }
            }
            if (e.ctrlKey && e.key === 'z') {
                canvasObj.undo();
            }
        });

        // Logic đổi màu
        const colorBtns = document.querySelectorAll('.color-btn');
        colorBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                colorBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                updateBrush();
                
                const activeObj = fabricCanvas.getActiveObject();
                if (activeObj) {
                    activeObj.set('fill', btn.dataset.color);
                    activeObj.set('stroke', btn.dataset.color);
                    fabricCanvas.requestRenderAll();
                }
            });
        });

        canvasObj = {
            undo: () => {
                if(drawingHistory.length > 0) {
                    const lastObj = drawingHistory.pop();
                    fabricCanvas.remove(lastObj);
                }
            },
            clearDrawings: () => {
                if(confirm('Bạn có chắc chắn muốn xóa toàn bộ nét vẽ và ghi chú?')) {
                    const objects = fabricCanvas.getObjects();
                    objects.forEach(obj => {
                        fabricCanvas.remove(obj);
                    });
                    drawingHistory = [];
                }
            }
        };
    }

    // 3. Submit Form
    function submitGrading() {
        // Đưa nội dung HTML của Quill vào input ẩn
        document.getElementById('feedback-input').value = quill.root.innerHTML;

        // Nếu có canvas, xuất dữ liệu canvas ra base64
        if (fabricCanvas) {
            // Lấy toàn bộ nội dung canvas (bao gồm cả background bài nộp của học sinh và nét vẽ)
            if (fabricCanvas.getObjects().length > 0) {
                const base64Data = fabricCanvas.toDataURL({
                    format: 'png',
                    multiplier: 1 // chất lượng gốc
                });
                document.getElementById('canvas_image_data').value = base64Data;
            }
        }

        document.getElementById('gradingForm').submit();
    }
</script>
@endpush
