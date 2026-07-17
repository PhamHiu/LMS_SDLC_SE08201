@extends('layouts.app')

@section('title', 'Chấm điểm — ' . $submission->student->full_name)

@section('content')
<div class="mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-bold text-slate-800">Bài làm của {{ $submission->student->full_name }}</h2>
        <p class="text-sm text-slate-500">{{ $assignment->title }} · Lớp {{ $assignment->classRoom?->class_name }}</p>
    </div>
    <a href="{{ route('teacher.grading.index', $assignment) }}" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200">← Quay lại</a>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css">
<style>
    .textLayer {
        opacity: 0.15 !important;
        mix-blend-mode: multiply;
    }
    .textLayer ::selection {
        background: #bfdbfe !important;
    }
    .page-wrapper {
        position: relative;
        background-color: white;
        border: 1px solid #cbd5e1;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        margin-bottom: 1.5rem;
        transition: transform 0.2s ease;
    }
    .pdf-page-canvas {
        display: block;
        width: 100%;
        height: 100%;
    }
    .annotation-canvas {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 10;
        width: 100%;
        height: 100%;
        background-color: transparent;
    }
    .color-picker-dot.active {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #4f46e5;
    }
    /* CKEditor Custom Styles to prevent Tailwind overrides */
    .ck-content ul, .prose ul {
        list-style-type: disc !important;
        padding-left: 1.5rem !important;
        margin-bottom: 1rem !important;
    }
    .ck-content ol, .prose ol {
        list-style-type: decimal !important;
        padding-left: 1.5rem !important;
        margin-bottom: 1rem !important;
    }
    .ck-editor__editable_inline {
        min-height: 220px;
        background-color: white !important;
        color: #1e293b !important;
    }
</style>

<div class="grid gap-6 lg:grid-cols-4">
    {{-- Cột trái: Preview Bài Làm & Bảng vẽ --}}
    <div class="lg:col-span-3 flex h-[90vh] flex-col rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-2 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-slate-700 text-xs">📄 File bài nộp</h3>
                @php
                    $mode = request('mode');
                    if (empty($mode)) {
                        $mode = $submission->result_file_path ? 'graded' : 'edit';
                    }
                @endphp
            </div>
            <div class="flex items-center gap-1">
                @if ($submission->result_file_path)
                    <a href="?" class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $mode === 'graded' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">👁️ Bản đã chấm</a>
                @endif
                <a href="?mode=clean" class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $mode === 'clean' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">📄 Bản gốc học sinh nộp</a>
                <a href="?mode=edit" class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $mode === 'edit' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">✏️ Chấm bài và nhận xét</a>
                @if ($submission->submit_file_path)
                    <a href="{{ route('teacher.grading.download', $submission) }}" class="rounded bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-300">⬇ Tải xuống gốc</a>
                @endif
            </div>
        </div>
        <div class="flex-1 bg-slate-500 relative overflow-hidden flex flex-col">
            @if (!$submission->submit_file_path)
                <div class="flex h-full items-center justify-center text-slate-400 bg-slate-100">Sinh viên chưa nộp file.</div>
            @else
                @php
                    $isViewingGraded = ($mode === 'graded') && $submission->result_file_path;
                    $targetPath = $isViewingGraded ? $submission->result_file_path : $submission->submit_file_path;
                    $ext = pathinfo($targetPath, PATHINFO_EXTENSION);
                    $url = asset(Storage::url($targetPath));
                @endphp
                
                @if (strtolower($ext) === 'pdf')
                    @if ($mode === 'edit')
                        <!-- PDF Annotator Toolbar -->
                        <div class="bg-white border-b border-slate-200 px-4 py-1 flex flex-wrap items-center justify-between gap-2 shadow-sm z-10">
                        <div class="flex items-center gap-1">
                            <button type="button" id="tool-select" class="tool-btn rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                🔍 Chọn chữ
                            </button>
                            <button type="button" id="tool-pen" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                🖌️ Vẽ
                            </button>
                            <button type="button" id="tool-line" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                ➖ Thẳng
                            </button>
                            <button type="button" id="tool-rect" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                🔲 Ô vuông
                            </button>
                            <button type="button" id="tool-arrow" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                ↗️ Mũi tên
                            </button>
                            <button type="button" id="tool-highlighter" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                🖍️ Đánh dấu
                            </button>
                            <button type="button" id="tool-text" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                🔤 Viết chữ
                            </button>
                            <button type="button" id="tool-eraser" class="tool-btn rounded-lg bg-white border border-slate-200 text-slate-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all">
                                🧹 Tẩy
                            </button>
                            <button type="button" id="tool-undo" disabled class="rounded-lg bg-white border border-slate-200 text-slate-400 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                ↩️ Hoàn tác
                            </button>
                            <button type="button" id="tool-clear" class="rounded-lg bg-rose-50 border border-rose-100 text-rose-600 px-2 py-0.5 text-[11px] font-semibold flex items-center gap-0.5 transition-all hover:bg-rose-100">
                                🗑️ Xóa hết
                            </button>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1">
                                <label class="text-[9px] font-bold text-slate-500 uppercase mr-0.5">Cỡ:</label>
                                <select id="tool-size" class="rounded border border-slate-300 px-1 py-0.5 text-[10px] bg-white outline-none font-medium">
                                    <option value="12">Nhỏ (12px)</option>
                                    <option value="18" selected>Vừa (18px)</option>
                                    <option value="24">Lớn (24px)</option>
                                    <option value="36">Rất lớn (36px)</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-1">
                                <label class="text-[9px] font-bold text-slate-500 uppercase mr-0.5">Màu:</label>
                                <div class="flex items-center gap-1">
                                    <span class="color-picker-dot w-4 h-4 rounded-full cursor-pointer border border-slate-300 bg-red-600 active" data-color="#dc2626"></span>
                                    <span class="color-picker-dot w-4 h-4 rounded-full cursor-pointer border border-slate-300 bg-blue-600" data-color="#2563eb"></span>
                                    <span class="color-picker-dot w-4 h-4 rounded-full cursor-pointer border border-slate-300 bg-emerald-600" data-color="#059669"></span>
                                    <span class="color-picker-dot w-4 h-4 rounded-full cursor-pointer border border-slate-300 bg-black" data-color="#000000"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- PDF Scroll Container -->
                    <div id="pdf-scroll-container" class="flex-1 overflow-y-auto bg-slate-100 p-6 select-none relative">
                        <div id="pdf-loading-message" class="absolute inset-0 flex items-center justify-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium">Đang tải tài liệu PDF và chuẩn bị bảng vẽ...</span>
                            </div>
                        </div>
                        <div class="relative mx-auto min-h-screen" style="max-width: 800px;">
                            <!-- Left: PDF Pages Column -->
                            <div id="pdf-pages-column" class="flex flex-col gap-6 items-center w-full"></div>
                            
                            <!-- Right: Floating Comments Lane -->
                            <div id="pdf-comments-lane" class="w-[280px] absolute" style="left: calc(100% + 24px); top: 0; height: 100%;"></div>
                        </div>
                    </div>
                @elseif (strtolower($ext) === 'docx')
                    <!-- DOCX Preview Space -->
                    <div id="docx-loader" class="flex flex-col items-center justify-center h-full text-slate-400 gap-2 bg-slate-50">
                        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">Đang tải tài liệu Word...</span>
                    </div>
                    <div id="docx-scroll-container" class="hidden flex-1 overflow-y-auto bg-slate-100 p-6 select-text relative">
                        <div class="relative mx-auto min-h-screen" style="max-width: 760px;">
                            <!-- Left: DOCX Document -->
                            <div id="docx-body" class="bg-white p-12 shadow-md border border-slate-200 w-full min-h-screen text-slate-800 rounded-lg relative"></div>
                            
                            <!-- Right: Comments Lane -->
                            <div id="docx-comments-lane" class="w-[280px] absolute" style="left: calc(100% + 24px); top: 0; height: 100%;"></div>
                        </div>
                    </div>
                @elseif (in_array(strtolower($ext), ['png', 'jpg', 'jpeg', 'webp', 'gif']))
                    <!-- Image Preview Space -->
                    <div class="flex-1 overflow-y-auto bg-slate-100 flex flex-col items-center p-6" id="image-scroll-container">
                        <img src="{{ $url }}" class="max-w-[800px] w-full h-auto rounded-lg shadow-lg border border-slate-300 bg-white" />
                    </div>
                @else
                    <!-- Download Placeholder -->
                    <div class="flex h-full flex-col items-center justify-center text-center bg-slate-100">
                        <span class="text-4xl mb-3">📁</span>
                        <p class="text-slate-600 font-medium mb-1">Định dạng .{{ strtoupper($ext) }} không hỗ trợ xem trực tiếp.</p>
                        <p class="text-sm text-slate-500 mb-4">Vui lòng tải file xuống để xem chi tiết.</p>
                        <a href="{{ route('teacher.grading.download', $submission) }}" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tải file xuống</a>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Cột phải: Form Chấm điểm --}}
    <div class="lg:col-span-1 flex h-[90vh] flex-col rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex-1 p-5 overflow-y-auto bg-white">
            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                <span>✏️</span> Nhập điểm & Đánh giá
            </h3>
            <form id="grading-form" method="POST" action="{{ route('teacher.grading.grade', $submission) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <!-- Hidden input for the base64 annotated image -->
                <input type="hidden" name="annotated_image" id="annotated-image-input" value="">
                <input type="hidden" name="page_annotations" id="page-annotations-input" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Điểm số (tối đa {{ $assignment->max_score }})</label>
                    <input type="number" name="score" value="{{ $submission->score }}" min="0" max="{{ $assignment->max_score }}" step="0.25" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white font-semibold text-slate-800">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nhận xét chung</label>
                    <textarea name="feedback" placeholder="Nhận xét bài làm của sinh viên..." rows="6"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white resize-none text-slate-700">{{ $submission->feedback }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tải file sửa khác (nếu có)</label>
                    <input type="file" name="result_file" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded file:border-0 file:bg-slate-200 file:px-2 file:py-1 file:text-xs">
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <button type="submit" id="btn-submit-grade" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 shadow-md transition-all duration-200 flex items-center justify-center gap-1.5">
                        <span>💾</span> Lưu điểm & Ghi chú
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden template blocks for comment cards and new floating input card (Google Docs style) -->
<div id="comments-source-templates" class="hidden">
    @foreach ($submission->comments as $comment)
        @php
            $commentData = json_decode($comment->content, true);
            $hasHighlight = is_array($commentData) && isset($commentData['text']);
            $highlightedText = $hasHighlight ? $commentData['text'] : null;
            $highlightColor = $hasHighlight ? ($commentData['color'] ?? 'yellow') : 'yellow';
            $commentText = $hasHighlight ? $commentData['comment'] : $comment->content;
            
            $isTeacher = $comment->user->isTeacher();
            
            // Map border class matching Google Docs comment styling
            $borderClass = match($highlightColor) {
                'green' => 'border-emerald-400 bg-emerald-50/5 hover:bg-emerald-50/10',
                'pink' => 'border-pink-400 bg-pink-50/5 hover:bg-pink-50/10',
                'blue' => 'border-blue-400 bg-blue-50/5 hover:bg-blue-50/10',
                'red' => 'border-rose-400 bg-rose-50/5 hover:bg-rose-50/10',
                default => 'border-yellow-400 bg-yellow-50/5 hover:bg-yellow-50/10',
            };
            
            $bgLabelClass = match($highlightColor) {
                'green' => 'bg-emerald-50 border-emerald-100 text-emerald-800',
                'pink' => 'bg-pink-50 border-pink-100 text-pink-800',
                'blue' => 'bg-blue-50 border-blue-100 text-blue-800',
                'red' => 'bg-rose-50 border-rose-100 text-rose-800',
                default => 'bg-amber-50 border-amber-100 text-amber-800',
            };
        @endphp
        <div class="comment-card bg-white p-4 rounded-xl border border-slate-200 border-l-4 {{ $borderClass }} shadow-sm hover:shadow-md transition-all duration-200 select-text" 
             data-comment-id="{{ $comment->comment_id }}"
             onmouseenter="hoverCommentCard({{ $comment->comment_id }}, true)"
             onmouseleave="hoverCommentCard({{ $comment->comment_id }}, false)">
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-[10px] font-bold text-blue-700 uppercase">
                        {{ substr($comment->user->full_name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 leading-tight">{{ $comment->user->full_name }}</h4>
                        <span class="text-[9px] text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    @if ($isTeacher)
                        <span class="text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded border {{ $bgLabelClass }}">
                            Giáo viên nhận xét
                        </span>
                    @endif
                    @if ($comment->user_id === Auth::user()->user_id)
                        <form method="POST" action="{{ route('teacher.grading.delete-comment', $comment) }}" onsubmit="return confirm('Bạn có chắc muốn xóa nhận xét này?')" class="inline-flex">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors p-0.5" title="Xóa nhận xét">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            
            @if ($highlightedText)
                <div class="mb-2 p-1.5 rounded text-[10px] italic border {{ $bgLabelClass }} truncate" title="{{ $highlightedText }}">
                    "{{ $highlightedText }}"
                </div>
            @endif
            
            <p class="text-xs text-slate-700 leading-normal whitespace-pre-wrap select-text">{{ $commentText }}</p>
        </div>
    @endforeach
</div>

<div id="comment-input-template" class="hidden">
    <div class="comment-input-card bg-white p-4 rounded-xl border border-slate-200 shadow-xl space-y-3 w-[280px] select-text">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-[10px] font-bold text-white uppercase">
                {{ substr(Auth::user()->full_name, 0, 1) }}
            </div>
            <h4 class="text-xs font-bold text-slate-800 leading-tight">{{ Auth::user()->full_name }}</h4>
        </div>
        
        <form id="floating-comment-form" method="POST" action="{{ route('teacher.grading.comment', $submission) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="selected_text" id="floating-selected-text" value="">
            <input type="hidden" name="highlight_color" id="floating-highlight-color" value="yellow">
            <input type="hidden" name="content" id="floating-comment-content" value="">
            
            <div class="p-1.5 rounded bg-amber-50 border border-amber-200 text-[10px] text-amber-800 italic line-clamp-2" id="floating-selection-preview"></div>
            
            <!-- Highlight color selector -->
            <div class="flex items-center justify-between pt-1">
                <span class="text-[9px] font-semibold text-slate-500">Màu highlight:</span>
                <div class="flex items-center gap-1" id="floating-color-picker">
                    <button type="button" onclick="setFloatingHighlightColor('yellow')" class="w-3.5 h-3.5 rounded-full bg-yellow-200 border border-yellow-400 ring-2 ring-blue-600 ring-offset-1 transition-all" data-color="yellow"></button>
                    <button type="button" onclick="setFloatingHighlightColor('green')" class="w-3.5 h-3.5 rounded-full bg-emerald-200 border border-emerald-400 ring-2 ring-transparent transition-all" data-color="green"></button>
                    <button type="button" onclick="setFloatingHighlightColor('pink')" class="w-3.5 h-3.5 rounded-full bg-pink-200 border border-pink-400 ring-2 ring-transparent transition-all" data-color="pink"></button>
                    <button type="button" onclick="setFloatingHighlightColor('blue')" class="w-3.5 h-3.5 rounded-full bg-blue-200 border border-blue-400 ring-2 ring-transparent transition-all" data-color="blue"></button>
                    <button type="button" onclick="setFloatingHighlightColor('red')" class="w-3.5 h-3.5 rounded-full bg-red-200 border border-red-400 ring-2 ring-transparent transition-all" data-color="red"></button>
                </div>
            </div>

            <textarea id="floating-comment-textarea" placeholder="Nhập nhận xét..." rows="2" required
                      class="w-full rounded-lg border border-slate-300 p-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none bg-white resize-none text-slate-700"></textarea>
            
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="cancelFloatingComment()" class="text-slate-500 hover:bg-slate-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">Hủy</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition-all">Nhận xét</button>
            </div>
        </form>
    </div>
</div>
    </div>
</div>

@if (isset($ext) && strtolower($ext) === 'pdf')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    document.addEventListener('DOMContentLoaded', function () {
        const pdfUrl = "{{ $url }}";
        const container = document.getElementById('pdf-scroll-container');
        const loadingMessage = document.getElementById('pdf-loading-message');
        const form = document.getElementById('grading-form');
        const annotatedInput = document.getElementById('annotated-image-input');

        if (!container || !pdfUrl) return;

        const savedAnnotations = @json($savedAnnotations ?? (object)[]);

        let pdfDoc = null;
        let currentTool = 'select'; // 'select', 'pen', 'line', 'rect', 'arrow', 'highlighter', 'text', 'eraser'
        let currentColor = '#dc2626'; // default red
        let currentSize = 18; // default size
        let pagesData = []; // store each page's canvas, context, etc.

        // Active tool state UI
        const toolSelect = document.getElementById('tool-select');
        const toolPen = document.getElementById('tool-pen');
        const toolLine = document.getElementById('tool-line');
        const toolRect = document.getElementById('tool-rect');
        const toolArrow = document.getElementById('tool-arrow');
        const toolHighlighter = document.getElementById('tool-highlighter');
        const toolText = document.getElementById('tool-text');
        const toolEraser = document.getElementById('tool-eraser');
        const toolUndo = document.getElementById('tool-undo');
        const toolClear = document.getElementById('tool-clear');
        const toolSize = document.getElementById('tool-size');
        const colorDots = document.querySelectorAll('.color-picker-dot');

        window.undoHistory = [];

        window.updateUndoButtonState = function() {
            if (toolUndo) {
                if (window.undoHistory.length > 0) {
                    toolUndo.disabled = false;
                    toolUndo.classList.remove('text-slate-400');
                    toolUndo.classList.add('text-slate-700', 'hover:bg-slate-100');
                } else {
                    toolUndo.disabled = true;
                    toolUndo.classList.add('text-slate-400');
                    toolUndo.classList.remove('text-slate-700', 'hover:bg-slate-100');
                }
            }
        };

        if (toolUndo) {
            toolUndo.addEventListener('click', () => {
                if (window.undoHistory.length > 0) {
                    const lastAction = window.undoHistory.pop();
                    const targetPage = pagesData.find(p => p.pageNum === lastAction.pageNum);
                    if (targetPage) {
                        targetPage.annotCtx.putImageData(lastAction.state, 0, 0);
                    }
                    window.updateUndoButtonState();
                }
            });
        }

        function setActiveTool(tool) {
            currentTool = tool;
            
            // Toggle pointer events helper
            const setTextLayersSelectable = (selectable) => {
                const layers = document.querySelectorAll('.textLayer');
                layers.forEach(layer => {
                    layer.style.pointerEvents = selectable ? 'auto' : 'none';
                    layer.style.userSelect = selectable ? 'text' : 'none';
                });
                
                const canvases = document.querySelectorAll('.annotation-canvas');
                canvases.forEach(canvas => {
                    canvas.style.pointerEvents = selectable ? 'none' : 'auto';
                });
            };

            // Reset all buttons styles
            [toolSelect, toolPen, toolLine, toolRect, toolArrow, toolHighlighter, toolText, toolEraser].forEach(btn => {
                if (btn) {
                    btn.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
                    btn.classList.remove('bg-indigo-50', 'border-indigo-200', 'text-indigo-700');
                }
            });

            if (tool === 'select') {
                if (toolSelect) {
                    toolSelect.classList.add('bg-indigo-50', 'border-indigo-200', 'text-indigo-700');
                    toolSelect.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
                }
                setTextLayersSelectable(true);
                container.style.cursor = 'text';
            } else {
                let targetBtn = null;
                if (tool === 'pen') targetBtn = toolPen;
                else if (tool === 'line') targetBtn = toolLine;
                else if (tool === 'rect') targetBtn = toolRect;
                else if (tool === 'arrow') targetBtn = toolArrow;
                else if (tool === 'highlighter') targetBtn = toolHighlighter;
                else if (tool === 'text') targetBtn = toolText;
                else if (tool === 'eraser') targetBtn = toolEraser;

                if (targetBtn) {
                    targetBtn.classList.add('bg-indigo-50', 'border-indigo-200', 'text-indigo-700');
                    targetBtn.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
                }
                
                setTextLayersSelectable(false);
                container.style.cursor = (tool === 'text') ? 'text' : 'crosshair';
            }
        }

        if (toolSelect) toolSelect.addEventListener('click', () => setActiveTool('select'));
        if (toolPen) toolPen.addEventListener('click', () => setActiveTool('pen'));
        if (toolLine) toolLine.addEventListener('click', () => setActiveTool('line'));
        if (toolRect) toolRect.addEventListener('click', () => setActiveTool('rect'));
        if (toolArrow) toolArrow.addEventListener('click', () => setActiveTool('arrow'));
        if (toolHighlighter) toolHighlighter.addEventListener('click', () => setActiveTool('highlighter'));
        if (toolText) toolText.addEventListener('click', () => setActiveTool('text'));
        if (toolEraser) toolEraser.addEventListener('click', () => setActiveTool('eraser'));

        if (toolClear) {
            toolClear.addEventListener('click', () => {
                if (confirm('Bạn có chắc muốn xóa tất cả ghi chú đã vẽ/viết trên bài này?')) {
                    // Save states before clearing to support undoing a full clear
                    pagesData.forEach(page => {
                        const oldState = page.annotCtx.getImageData(0, 0, page.annotCanvas.width, page.annotCanvas.height);
                        window.undoHistory.push({ pageNum: page.pageNum, state: oldState });
                    });
                    pagesData.forEach(page => {
                        page.annotCtx.clearRect(0, 0, page.annotCanvas.width, page.annotCanvas.height);
                    });
                    window.updateUndoButtonState();
                }
            });
        }

        if (toolSize) {
            toolSize.addEventListener('change', (e) => {
                currentSize = parseInt(e.target.value);
            });
        }

        colorDots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                colorDots.forEach(d => {
                    d.classList.remove('ring-2', 'ring-indigo-600', 'active');
                });
                dot.classList.add('ring-2', 'ring-indigo-600', 'active');
                currentColor = dot.dataset.color;
            });
        });

        // Load and render PDF
        pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
            pdfDoc = pdf;
            loadingMessage.remove();
            renderAllPages();
        }).catch(err => {
            console.error('Lỗi khi load PDF:', err);
            loadingMessage.innerHTML = `<span class="text-rose-400 font-bold">Không thể tải tài liệu PDF. Lỗi: ${err.message}</span>`;
        });

        function renderAllPages() {
            const numPages = pdfDoc.numPages;
            const pagesColumn = document.getElementById('pdf-pages-column');
            const appendTarget = pagesColumn || container;
            
            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                const pageWrapper = document.createElement('div');
                pageWrapper.className = 'page-wrapper relative bg-white border border-slate-300 shadow-lg';
                pageWrapper.dataset.pageNum = pageNum;
                appendTarget.appendChild(pageWrapper);

                pdfDoc.getPage(pageNum).then(page => {
                    // Determine viewport scale (we want a comfortable width, e.g. 800px)
                    const containerWidth = container.clientWidth - 48; // padding
                    const baseViewport = page.getViewport({ scale: 1.0 });
                    const scale = containerWidth > 0 ? (containerWidth / baseViewport.width) : 1.2;
                    const viewport = page.getViewport({ scale: Math.min(scale, 1.5) });

                    // Create PDF Render Canvas
                    const pdfCanvas = document.createElement('canvas');
                    pdfCanvas.width = viewport.width;
                    pdfCanvas.height = viewport.height;
                    pdfCanvas.className = 'pdf-page-canvas block';
                    pageWrapper.appendChild(pdfCanvas);

                    // Create Transparent Annotation Canvas
                    const annotCanvas = document.createElement('canvas');
                    annotCanvas.width = viewport.width;
                    annotCanvas.height = viewport.height;
                    annotCanvas.className = 'annotation-canvas absolute top-0 left-0 z-10';
                    pageWrapper.appendChild(annotCanvas);

                    pageWrapper.style.width = viewport.width + 'px';
                    pageWrapper.style.height = viewport.height + 'px';

                    const pdfCtx = pdfCanvas.getContext('2d');
                    const annotCtx = annotCanvas.getContext('2d');

                    // Render page content
                    const renderContext = {
                        canvasContext: pdfCtx,
                        viewport: viewport
                    };
                    const renderTask = page.render(renderContext);

                    // Create Text Layer container for text selection/highlighting
                    const textLayerDiv = document.createElement('div');
                    textLayerDiv.className = 'textLayer absolute top-0 left-0 z-20';
                    textLayerDiv.style.width = viewport.width + 'px';
                    textLayerDiv.style.height = viewport.height + 'px';
                    pageWrapper.appendChild(textLayerDiv);

                    // Render text content
                    page.getTextContent().then(textContent => {
                        pdfjsLib.renderTextLayer({
                            textContentSource: textContent,
                            container: textLayerDiv,
                            viewport: viewport,
                            textDivs: []
                        }).promise.then(() => {
                            // Trigger comments highlighting after rendering
                            applyAllDocumentHighlights();
                        });
                    });

                    // Save page information
                    const pageInfo = {
                        pageNum,
                        pdfCanvas,
                        annotCanvas,
                        annotCtx
                    };
                    pagesData.push(pageInfo);

                    const mode = "{{ $mode }}";
                    if (mode === 'edit') {
                        // Setup Canvas Event Listeners for drawing
                        setupDrawingEvents(pageInfo);

                        // Load saved annotations drawing for this page if it exists
                        if (savedAnnotations && savedAnnotations[pageNum]) {
                            const img = new Image();
                            img.onload = function() {
                                annotCtx.drawImage(img, 0, 0);
                            };
                            img.src = savedAnnotations[pageNum];
                        }
                    }
                });
            }

            // Set initial tool to text selection & comments
            setActiveTool('select');
        }

        function setupDrawingEvents(page) {
            const canvas = page.annotCanvas;
            const ctx = page.annotCtx;
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;

            let startX = 0;
            let startY = 0;
            let savedSnapshot = null;

            // Drawing Event Handlers
            canvas.addEventListener('mousedown', (e) => {
                if (['pen', 'line', 'rect', 'arrow', 'highlighter', 'eraser'].indexOf(currentTool) === -1) return;
                
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                startX = e.clientX - rect.left;
                startY = e.clientY - rect.top;
                lastX = startX;
                lastY = startY;

                // Save canvas state before drawing starts to support Undo
                const oldState = ctx.getImageData(0, 0, canvas.width, canvas.height);
                window.undoHistory.push({ pageNum: page.pageNum, state: oldState });
                window.updateUndoButtonState();
                
                if (currentTool === 'eraser') {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                } else if (currentTool === 'pen') {
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                } else {
                    savedSnapshot = oldState; // Reuse the saved state
                }
            });

            canvas.addEventListener('mousemove', (e) => {
                if (!isDrawing) return;
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                if (currentTool === 'pen') {
                    ctx.strokeStyle = currentColor;
                    ctx.lineWidth = Math.max(2, currentSize / 5);
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    lastX = x;
                    lastY = y;
                } else if (currentTool === 'eraser') {
                    ctx.strokeStyle = 'rgba(0,0,0,1)';
                    ctx.lineWidth = currentSize * 1.5;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    lastX = x;
                    lastY = y;
                } else if (currentTool === 'line') {
                    // Restore snapshot to clear previous preview line
                    ctx.putImageData(savedSnapshot, 0, 0);
                    
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.lineTo(x, y);
                    ctx.strokeStyle = currentColor;
                    ctx.lineWidth = Math.max(2, currentSize / 5);
                    ctx.lineCap = 'round';
                    ctx.stroke();
                } else if (currentTool === 'rect') {
                    ctx.putImageData(savedSnapshot, 0, 0);
                    
                    ctx.beginPath();
                    ctx.rect(startX, startY, x - startX, y - startY);
                    ctx.strokeStyle = currentColor;
                    ctx.lineWidth = Math.max(2, currentSize / 5);
                    ctx.stroke();
                } else if (currentTool === 'arrow') {
                    ctx.putImageData(savedSnapshot, 0, 0);
                    
                    const headLength = Math.max(10, currentSize * 0.8);
                    const dx = x - startX;
                    const dy = y - startY;
                    const angle = Math.atan2(dy, dx);
                    
                    ctx.strokeStyle = currentColor;
                    ctx.lineWidth = Math.max(2, currentSize / 5);
                    ctx.lineCap = 'round';
                    
                    // Draw line
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    
                    // Draw arrowhead pointing to (x, y)
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                    ctx.lineTo(x - headLength * Math.cos(angle - Math.PI / 6), y - headLength * Math.sin(angle - Math.PI / 6));
                    ctx.lineTo(x - headLength * Math.cos(angle + Math.PI / 6), y - headLength * Math.sin(angle + Math.PI / 6));
                    ctx.closePath();
                    ctx.fillStyle = currentColor;
                    ctx.fill();
                } else if (currentTool === 'highlighter') {
                    ctx.putImageData(savedSnapshot, 0, 0);
                    
                    ctx.fillStyle = currentColor;
                    ctx.globalAlpha = 0.35; // Draw transparently
                    ctx.fillRect(startX, startY, x - startX, y - startY);
                    ctx.globalAlpha = 1.0; // Restore opacity
                }
            });

            const stopDrawing = () => {
                if (isDrawing) {
                    isDrawing = false;
                    ctx.globalCompositeOperation = 'source-over';
                }
            };
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseleave', stopDrawing);

            // Text Typing Event Handler
            canvas.addEventListener('click', (e) => {
                if (currentTool !== 'text') return;
                
                // Check if there is already an active input to prevent multiple inputs
                if (document.getElementById('active-annot-input')) return;

                const rect = canvas.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const clickY = e.clientY - rect.top;

                // Create temporary textarea
                const input = document.createElement('textarea');
                input.id = 'active-annot-input';
                input.placeholder = 'Nhấp và gõ ghi chú...';
                input.className = 'absolute z-20 border border-indigo-400 bg-white/95 text-sm p-1.5 rounded shadow-lg outline-none resize-none';
                input.style.left = (e.clientX - container.getBoundingClientRect().left + container.scrollLeft) + 'px';
                input.style.top = (e.clientY - container.getBoundingClientRect().top + container.scrollTop) + 'px';
                input.style.color = currentColor;
                input.style.fontSize = currentSize + 'px';
                input.style.fontFamily = 'sans-serif';
                input.style.fontWeight = 'bold';
                input.style.lineHeight = '1.2';
                input.style.minWidth = '180px';
                input.style.height = 'auto';
                input.value = 'Giáo viên nhận xét: ';

                container.appendChild(input);
                input.focus();
                
                // Move cursor to the end of the text
                setTimeout(() => {
                    input.selectionStart = input.selectionEnd = input.value.length;
                }, 50);

                // Handle blur and key events
                const finishText = () => {
                    const text = input.value.trim();
                    if (text) {
                        // Save canvas state before writing text onto the canvas to support Undo
                        const oldState = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        window.undoHistory.push({ pageNum: page.pageNum, state: oldState });
                        window.updateUndoButtonState();

                        ctx.fillStyle = currentColor;
                        ctx.font = `bold ${currentSize}px sans-serif`;
                        ctx.textBaseline = 'top';
                        
                        // Handle multi-line drawing on canvas
                        const lines = text.split('\n');
                        let currentYOffset = clickY;
                        lines.forEach(line => {
                            ctx.fillText(line, clickX, currentYOffset);
                            currentYOffset += currentSize * 1.2;
                        });
                    }
                    input.remove();
                };

                input.addEventListener('blur', finishText);
                input.addEventListener('keydown', (evt) => {
                    if (evt.key === 'Enter' && !evt.shiftKey) {
                        evt.preventDefault();
                        input.blur();
                    }
                });
            });
        }

        // Intercept form submit to compile final image with annotations
        form.addEventListener('submit', function (e) {
            if (pagesData.length === 0) return; // Proceed normally if no PDF loaded

            e.preventDefault(); // Stop form submission temporarily

            // Show loading state on button
            const submitBtn = document.getElementById('btn-submit-grade');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `⏳ Đang xử lý ghi chú...`;

            setTimeout(() => {
                try {
                    // Stitch all pages vertically into a single canvas
                    let totalWidth = 0;
                    let totalHeight = 0;

                    pagesData.forEach(page => {
                        totalWidth = Math.max(totalWidth, page.pdfCanvas.width);
                        totalHeight += page.pdfCanvas.height;
                    });

                    const finalCanvas = document.createElement('canvas');
                    finalCanvas.width = totalWidth;
                    finalCanvas.height = totalHeight;
                    const finalCtx = finalCanvas.getContext('2d');

                    let currentY = 0;
                    pagesData.forEach(page => {
                        // Draw PDF content first
                        finalCtx.drawImage(page.pdfCanvas, 0, currentY);
                        // Draw Annotation layer on top
                        finalCtx.drawImage(page.annotCanvas, 0, currentY);
                        currentY += page.pdfCanvas.height;
                    });

                    // Get base64 string
                    const dataURL = finalCanvas.toDataURL('image/png');
                    annotatedInput.value = dataURL;

                    // Save standalone drawings annotations for each page to allow re-grading
                    const pageAnnots = {};
                    pagesData.forEach(page => {
                        pageAnnots[page.pageNum] = page.annotCanvas.toDataURL('image/png');
                    });
                    const pageAnnotsInput = document.getElementById('page-annotations-input');
                    if (pageAnnotsInput) {
                        pageAnnotsInput.value = JSON.stringify(pageAnnots);
                    }

                    // Resume form submission
                    form.submit();
                } catch (err) {
                    console.error('Lỗi khi lưu ảnh ghi chú:', err);
                    alert('Có lỗi xảy ra khi lưu ghi chú vẽ: ' + err.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }, 300);
        });
    });
</script>
@endif

<!-- CKEditor 5 Classic CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const feedbackTextarea = document.querySelector('textarea[name="feedback"]');
        if (feedbackTextarea) {
            ClassicEditor
                .create(feedbackTextarea, {
                    toolbar: [
                        'heading', '|', 
                        'bold', 'italic', 'link', 
                        'bulletedList', 'numberedList', 'blockQuote', '|', 
                        'insertTable', 'mediaEmbed', 'undo', 'redo'
                    ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                        ]
                    }
                })
                .then(editor => {
                    // Update original textarea on form submit
                    const form = document.getElementById('grading-form');
                    if (form) {
                        form.addEventListener('submit', () => {
                            feedbackTextarea.value = editor.getData();
                        });
                    }
                })
                .catch(error => {
                    console.error('Lỗi tải CKEditor 5:', error);
                });
        }
    });
</script>
<!-- JavaScript logic for comments tab, text selection, and Word document rendering -->
<script>
    // Tab switching function
    function switchSidebarTab(tab) {
        const gradeBtn = document.getElementById('tab-grade-btn');
        const commentsBtn = document.getElementById('tab-comments-btn');
        const gradeContent = document.getElementById('tab-grade-content');
        const commentsContent = document.getElementById('tab-comments-content');
        
        if (!gradeBtn || !commentsBtn) return;
        
        if (tab === 'grade') {
    }
</script>

<!-- JavaScript logic for Google Docs-style inline floating comments and selections -->
<script>
    // Set floating highlight color logic
    function setFloatingHighlightColor(color) {
        const input = document.getElementById('floating-highlight-color');
        if (input) input.value = color;
        
        // Change temp highlight span color live in the document
        const tempSpan = document.getElementById('active-temp-highlight-span');
        if (tempSpan) {
            tempSpan.className = `temp-highlight doc-highlight highlight-${color} rounded px-0.5`;
            tempSpan.style.backgroundColor = getHighlightHexColor(color);
        }
        
        // Update active class borders on the color picker buttons
        const buttons = document.querySelectorAll('#floating-color-picker button');
        buttons.forEach(btn => {
            const btnColor = btn.dataset.color;
            if (btnColor === color) {
                btn.className = btn.className.replace('ring-transparent', 'ring-blue-600 ring-offset-1');
            } else {
                btn.className = btn.className.replace(/ring-blue-600\s*ring-offset-1/g, 'ring-transparent');
            }
        });
    }

    // Text selection event handler
    function handleTextSelection() {
        setTimeout(() => {
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();
            
            const isDocx = !document.getElementById('docx-scroll-container').classList.contains('hidden');
            const docViewer = isDocx ? document.getElementById('docx-scroll-container') : document.getElementById('pdf-scroll-container');
            
            if (docViewer && selectedText !== '' && selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                
                // Verify selection is inside active document viewer
                let containerNode = range.commonAncestorContainer;
                let isInsideViewer = false;
                let temp = containerNode;
                while (temp) {
                    if (temp === docViewer) {
                        isInsideViewer = true;
                        break;
                    }
                    temp = temp.parentNode;
                }
                
                if (isInsideViewer) {
                    // Remove any previous unsaved temporary highlight
                    removeTempHighlight();
                    
                    // Wrap the selected range in a temporary highlight span
                    const tempSpan = wrapSelection(range, 'yellow');
                    tempSpan.id = 'active-temp-highlight-span';
                    
                    // Calculate position relative to container
                    const rect = tempSpan.getBoundingClientRect();
                    const containerRect = docViewer.getBoundingClientRect();
                    const relativeTop = rect.top - containerRect.top + docViewer.scrollTop;
                    
                    showFloatingCommentInput(selectedText, relativeTop);
                }
            }
        }, 80);
    }

    // Register mouseup listeners for stable selections
    document.addEventListener('DOMContentLoaded', function() {
        const docxContainer = document.getElementById('docx-scroll-container');
        const pdfContainer = document.getElementById('pdf-scroll-container');
        if (docxContainer) docxContainer.addEventListener('mouseup', handleTextSelection);
        if (pdfContainer) pdfContainer.addEventListener('mouseup', handleTextSelection);
    });

    // Helper to wrap ranges securely
    function wrapSelection(range, color) {
        const span = document.createElement('span');
        span.className = `temp-highlight doc-highlight highlight-${color} rounded px-0.5`;
        span.style.backgroundColor = getHighlightHexColor(color);
        span.style.color = '#1e293b';
        
        try {
            range.surroundContents(span);
        } catch (e) {
            const contents = range.extractContents();
            span.appendChild(contents);
            range.insertNode(span);
        }
        return span;
    }

    // Helper to remove temporary highlight
    function removeTempHighlight() {
        const span = document.getElementById('active-temp-highlight-span');
        if (span) {
            const parent = span.parentNode;
            if (parent) {
                while (span.firstChild) {
                    parent.insertBefore(span.firstChild, span);
                }
                parent.removeChild(span);
            }
        }
    }

    function showFloatingCommentInput(text, top) {
        const isDocx = !document.getElementById('docx-scroll-container').classList.contains('hidden');
        const activeLane = isDocx ? document.getElementById('docx-comments-lane') : document.getElementById('pdf-comments-lane');
        
        if (!activeLane) return;
        
        let inputCard = document.getElementById('active-floating-comment-card');
        if (!inputCard) {
            const template = document.querySelector('#comment-input-template .comment-input-card');
            inputCard = template.cloneNode(true);
            inputCard.id = 'active-floating-comment-card';
            activeLane.appendChild(inputCard);
            
            // Handle form submit intercept
            const form = inputCard.querySelector('#floating-comment-form');
            form.addEventListener('submit', function(e) {
                const commentTextarea = inputCard.querySelector('#floating-comment-textarea');
                const selectedTextInput = inputCard.querySelector('#floating-selected-text');
                const highlightColorInput = inputCard.querySelector('#floating-highlight-color');
                const hiddenContent = inputCard.querySelector('#floating-comment-content');
                
                const payload = {
                    text: selectedTextInput.value,
                    color: highlightColorInput.value,
                    comment: commentTextarea.value
                };
                hiddenContent.value = JSON.stringify(payload);
            });
        } else {
            if (inputCard.parentNode !== activeLane) {
                activeLane.appendChild(inputCard);
            }
        }
        
        // Set values
        inputCard.querySelector('#floating-selected-text').value = text;
        inputCard.querySelector('#floating-selection-preview').textContent = `"${text}"`;
        inputCard.style.position = 'absolute';
        inputCard.style.top = `${top}px`;
        inputCard.style.left = '0';
        inputCard.style.zIndex = '30';
        
        // Clear textarea
        inputCard.querySelector('#floating-comment-textarea').value = '';
        
        // Reposition other comments to make room
        repositionAllComments();
    }

    function cancelFloatingComment() {
        const card = document.getElementById('active-floating-comment-card');
        if (card) card.remove();
        removeTempHighlight();
        window.getSelection().removeAllRanges();
        repositionAllComments();
    }

    // Highlight text in document node
    function getHighlightHexColor(color) {
        switch(color) {
            case 'green': return '#a7f3d0';
            case 'pink': return '#fbcfe8';
            case 'blue': return '#bfdbfe';
            case 'red': return '#fecaca';
            default: return '#fef08a';
        }
    }

    function highlightTextInElement(element, textToHighlight, color, commentId) {
        if (!element || !textToHighlight) return;
        
        const walk = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null, false);
        let textNode;
        const nodesToReplace = [];
        
        while (textNode = walk.nextNode()) {
            const text = textNode.nodeValue;
            const index = text.indexOf(textToHighlight);
            if (index !== -1) {
                nodesToReplace.push({ node: textNode, index: index, text: textToHighlight });
            }
        }
        
        nodesToReplace.forEach(match => {
            const parent = match.node.parentNode;
            if (parent && parent.className && parent.className.includes('doc-highlight')) return; // Avoid double wrapping
            
            const origText = match.node.nodeValue;
            const beforeText = origText.substring(0, match.index);
            const matchedText = origText.substring(match.index, match.index + match.text.length);
            const afterText = origText.substring(match.index + match.text.length);
            
            const fragment = document.createDocumentFragment();
            if (beforeText) fragment.appendChild(document.createTextNode(beforeText));
            
            const span = document.createElement('span');
            span.className = `doc-highlight highlight-${color} cursor-pointer rounded px-0.5 transition-all hover:opacity-80`;
            span.style.backgroundColor = getHighlightHexColor(color);
            span.style.color = '#1e293b';
            span.dataset.commentId = commentId;
            span.textContent = matchedText;
            
            span.onclick = function() {
                scrollToCommentCard(commentId);
            };
            
            fragment.appendChild(span);
            if (afterText) fragment.appendChild(document.createTextNode(afterText));
            
            parent.replaceChild(fragment, match.node);
        });
    }

    function scrollToCommentCard(commentId) {
        const card = document.getElementById(`comment-card-${commentId}`);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('ring-2', 'ring-indigo-600', 'ring-offset-2');
            setTimeout(() => {
                card.classList.remove('ring-2', 'ring-indigo-600', 'ring-offset-2');
            }, 2000);
        }
    }

    function hoverCommentCard(commentId, active) {
        const highlights = document.querySelectorAll(`.doc-highlight[data-comment-id="${commentId}"]`);
        highlights.forEach(span => {
            if (active) {
                span.style.filter = 'brightness(0.9)';
                span.style.boxShadow = '0 0 4px rgba(0,0,0,0.15)';
            } else {
                span.style.filter = 'none';
                span.style.boxShadow = 'none';
            }
        });
    }

    // Reposition all comments next to highlights
    function repositionAllComments() {
        const isDocx = !document.getElementById('docx-scroll-container').classList.contains('hidden');
        const container = isDocx ? document.getElementById('docx-scroll-container') : document.getElementById('pdf-scroll-container');
        const lane = isDocx ? document.getElementById('docx-comments-lane') : document.getElementById('pdf-comments-lane');
        
        if (!container || !lane) return;
        
        // Move templates to the active lane if they are not there
        const cards = Array.from(document.querySelectorAll('#comments-source-templates .comment-card'));
        cards.forEach(card => {
            if (card.parentNode.id === 'comments-source-templates') {
                lane.appendChild(card);
            }
        });
        
        const activeCards = Array.from(lane.querySelectorAll('.comment-card'));
        const containerRect = container.getBoundingClientRect();
        
        activeCards.forEach(card => {
            const commentId = card.dataset.commentId;
            const highlightSpan = document.querySelector(`.doc-highlight[data-comment-id="${commentId}"]`);
            
            if (highlightSpan) {
                const spanRect = highlightSpan.getBoundingClientRect();
                const relativeTop = spanRect.top - containerRect.top + container.scrollTop;
                
                card.style.position = 'absolute';
                card.style.top = `${relativeTop}px`;
                card.style.left = '0';
                card.style.width = '280px';
                card.classList.remove('hidden');
            } else {
                card.style.position = 'relative';
                card.style.top = '0';
                card.style.left = '0';
                card.style.width = '280px';
                card.classList.remove('absolute');
            }
        });
        
        // Resolve overlap collisions
        const positionedCards = Array.from(lane.querySelectorAll('.comment-card, #active-floating-comment-card'))
            .filter(c => c.style.position === 'absolute');
            
        positionedCards.sort((a, b) => parseFloat(a.style.top) - parseFloat(b.style.top));
        
        let lastBottom = 0;
        positionedCards.forEach(card => {
            const top = parseFloat(card.style.top);
            if (top < lastBottom) {
                card.style.top = `${lastBottom + 10}px`;
            }
            lastBottom = parseFloat(card.style.top) + card.offsetHeight;
        });
    }

    // Apply highlights from comments database
    function applyAllDocumentHighlights() {
        const mode = "{{ $mode }}";
        if (mode === 'clean') {
            const lane = document.getElementById('pdf-comments-lane') || document.getElementById('docx-comments-lane');
            if (lane) lane.innerHTML = '';
            return;
        }

        const docxBody = document.getElementById('docx-body');
        const comments = @json($submission->comments);
        
        comments.forEach(comment => {
            try {
                const commentData = JSON.parse(comment.content);
                if (commentData && commentData.text) {
                    // Highlight Word files
                    if (docxBody) {
                        highlightTextInElement(docxBody, commentData.text, commentData.color || 'yellow', comment.comment_id);
                    }
                    
                    // Highlight PDF text layers
                    const textLayers = document.querySelectorAll('.textLayer');
                    textLayers.forEach(layer => {
                        highlightTextInElement(layer, commentData.text, commentData.color || 'yellow', comment.comment_id);
                    });
                }
            } catch(e) {
                // Plain comment, skip
            }
        });
        
        // Position comments lane after applying highlights
        setTimeout(repositionAllComments, 100);
    }

    // Window resize handler
    window.addEventListener('resize', repositionAllComments);
</script>

@if (isset($ext) && strtolower($ext) === 'docx')
<script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
<script src="https://unpkg.com/docx-preview@0.1.15/dist/docx-preview.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const docxBody = document.getElementById('docx-body');
        const docxLoader = document.getElementById('docx-loader');
        const docxContainer = document.getElementById('docx-scroll-container');

        fetch("{{ $url }}")
            .then(response => {
                if (!response.ok) throw new Error('Không thể tải file.');
                return response.blob();
            })
            .then(blob => {
                docx.renderAsync(blob, docxBody)
                    .then(() => {
                        docxLoader.classList.add('hidden');
                        docxContainer.classList.remove('hidden');
                        
                        // Apply highlights after Word rendering finishes
                        setTimeout(applyAllDocumentHighlights, 500);
                    });
            })
            .catch(err => {
                console.error(err);
                docxLoader.innerHTML = '<div class="text-rose-600 text-center font-medium p-6">❌ Lỗi: Không thể xem trước tài liệu Word này. Vui lòng tải xuống để xem trực tiếp.</div>';
            });
    });
</script>
@endif
@endsection
