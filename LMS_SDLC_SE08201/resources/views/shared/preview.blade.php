@extends('layouts.app')

@section('title', 'Xem trước tài liệu — ' . $filename)

@section('content')
<div class="flex flex-col h-[calc(100vh-140px)] bg-slate-900 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-slate-950 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <a href="javascript:history.back()" class="rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 p-2 text-sm transition-all">
                ← Quay lại
            </a>
            <div>
                <h2 class="text-sm font-semibold text-slate-200 truncate max-w-[300px] sm:max-w-md" title="{{ $filename }}">{{ $filename }}</h2>
                <p class="text-[11px] text-slate-500 uppercase font-bold tracking-wider">{{ $fileType }} preview</p>
            </div>
        </div>
        <div>
            <a href="{{ $downloadUrl }}" class="flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-md transition-all">
                📥 Tải xuống
            </a>
        </div>
    </div>

    <!-- Viewer Space -->
    <div class="flex-1 overflow-auto bg-slate-800 relative">
        @if ($fileType === 'pdf')
            <iframe src="{{ $fileUrl }}" class="w-full h-full border-none bg-slate-800"></iframe>
        @elseif ($fileType === 'image')
            <div class="flex flex-col items-center overflow-y-auto min-h-full p-8">
                <img src="{{ $fileUrl }}" alt="{{ $filename }}" class="max-w-[800px] w-full h-auto rounded-lg shadow-2xl border border-slate-700 bg-white" />
            </div>
        @elseif ($fileType === 'docx')
            <div id="docx-loader" class="flex flex-col items-center justify-center min-h-full text-slate-400 gap-3">
                <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Đang tải tài liệu Word...</span>
            </div>
            <div id="docx-wrapper" class="hidden py-8 px-4 flex justify-center overflow-y-auto min-h-full">
                <div id="docx-body" class="bg-white p-6 sm:p-16 shadow-2xl border border-slate-200 max-w-4xl w-full min-h-[1100px] text-slate-800 rounded-lg"></div>
            </div>

            <!-- Scripts for rendering DOCX client-side -->
            <script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
            <script src="https://unpkg.com/docx-preview@0.1.15/dist/docx-preview.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const docxBody = document.getElementById('docx-body');
                    const docxLoader = document.getElementById('docx-loader');
                    const docxWrapper = document.getElementById('docx-wrapper');

                    fetch("{{ $fileUrl }}")
                        .then(response => {
                            if (!response.ok) throw new Error('Không thể tải file.');
                            return response.blob();
                        })
                        .then(blob => {
                            docx.renderAsync(blob, docxBody)
                                .then(() => {
                                    docxLoader.classList.add('hidden');
                                    docxWrapper.classList.remove('hidden');
                                });
                        })
                        .catch(err => {
                            console.error(err);
                            docxLoader.innerHTML = '<div class="text-rose-400 text-center font-medium p-6">❌ Lỗi: Không thể xem trước tài liệu Word này.<br/><span class="text-xs text-slate-500">Vui lòng bấm nút Tải xuống ở góc trên bên phải để xem trực tiếp trên máy của bạn.</span></div>';
                        });
                });
            </script>
        @else
            <div class="flex flex-col items-center justify-center min-h-full text-center p-8">
                <span class="text-5xl mb-4">📦</span>
                <h3 class="text-slate-200 font-semibold text-base mb-1">Không hỗ trợ xem trước định dạng này</h3>
                <p class="text-sm text-slate-500 mb-6 max-w-md">Định dạng file không hỗ trợ hiển thị trực tiếp trên trình duyệt. Bạn có thể tải file về thiết bị của mình để xem.</p>
                <a href="{{ $downloadUrl }}" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 shadow-lg transition-all">
                    Tải file xuống
                </a>
            </div>
        @endif
    </div>
</div>

<style>
    /* Styling docx pages rendered in browser */
    .docx-wrapper {
        background-color: transparent !important;
        padding: 0 !important;
    }
    .docx {
        box-shadow: none !important;
        padding: 0 !important;
        margin-bottom: 0 !important;
    }
</style>
@endsection
