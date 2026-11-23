@extends('layouts.app')

@section('title', 'MR. BIẾT TUỐT — Trợ giảng AI Học tập')

@push('styles')
<style>
    .chat-bubble-user {
        background: #09090b;
        color: #ffffff;
        border: 1px solid #18181b;
        border-bottom-right-radius: 4px;
    }
    .chat-bubble-ai {
        background: #ffffff;
        color: #09090b;
        border: 1px solid #e4e4e7;
        border-bottom-left-radius: 4px;
    }
    .chat-container {
        height: calc(100vh - 210px);
        min-height: 540px;
    }
    .typing-dot {
        animation: typing 1.4s infinite ease-in-out both;
    }
    .typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .typing-dot:nth-child(2) { animation-delay: -0.16s; }
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    pre {
        background: #0f172a;
        color: #f8fafc;
        padding: 14px;
        border-radius: 10px;
        overflow-x: auto;
        font-size: 13px;
        line-height: 1.5;
        margin: 10px 0;
        border: 1px solid #1e293b;
    }
    code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .keyword-highlight {
        font-weight: 700;
        color: #0f172a;
    }
</style>
@endpush

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="flex items-center gap-2.5">
            <div class="w-12 h-12 rounded-lg overflow-hidden border border-slate-200 shrink-0 p-0.5 bg-white shadow-sm">
                <img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">MR. BIẾT TUỐT</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-600 px-3.5 py-1 text-xs font-bold text-slate-950 shadow-sm border border-amber-300">
                        <svg class="w-3.5 h-3.5 text-slate-950 fill-current" viewBox="0 0 24 24"><path d="M12 2l2.4 7.2h7.6l-6 4.8 2.4 7.2-6.4-4.8-6.4 4.8 2.4-7.2-6-4.8h7.6z"/></svg>
                        <span>Thầy Giáo Sư AI</span>
                    </span>
                </div>
                <p class="text-xs text-slate-600 mt-1">
                    Gia sư AI thông thái: xưng <strong>thầy</strong> gọi <strong>trò</strong>, giải thích bản chất kiến thức, hướng dẫn bài tập Socratic và hỗ trợ tải ảnh/tệp đến <strong>5MB</strong>.
                </p>
            </div>
        </div>
    </div>
    
    <form action="{{ route('student.ai-tutor.store') }}" method="POST">
        @csrf
        <input type="hidden" name="mode" value="EXPLAIN">
        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-[#1e4f8f] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#163c6d] transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Cuộc trò chuyện mới</span>
        </button>
    </form>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-4 chat-container">
    {{-- CỘT TRÁI: DANH SÁCH CUỘC TRÒ CHUYỆN --}}
    <div class="lg:col-span-1 flex flex-col rounded-lg border border-slate-200 bg-slate-50 p-4 border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Lịch sử hỏi Thầy</span>
            </span>
            <span class="rounded-full bg-blue-50 text-[#1e4f8f] px-2 py-0.5 text-xs font-bold">{{ $conversations->count() }}</span>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 pr-1">
            @forelse($conversations as $c)
                @php($isActive = $activeConversation && $activeConversation->conversation_id === $c->conversation_id)
                <div class="group relative flex items-center justify-between rounded-md p-3 transition {{ $isActive ? 'bg-blue-50/80 border border-blue-200 text-[#1e4f8f] shadow-sm' : 'hover:bg-slate-100 text-slate-700 border border-transparent' }}">
                    <a href="{{ route('student.ai-tutor.show', $c) }}" class="flex-1 min-w-0 pr-2">
                        <p class="truncate text-sm font-semibold {{ $isActive ? 'text-[#1e4f8f] font-bold' : '' }}">
                            {{ $c->title }}
                        </p>
                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500">
                            <span>{{ $c->updated_at->diffForHumans() }}</span>
                        </div>
                    </a>

                    <form action="{{ route('student.ai-tutor.destroy', $c) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa cuộc trò chuyện này với thầy Biết Tuốt?');" class="opacity-0 group-hover:opacity-100 transition">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded p-1 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Xóa">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-48 text-center text-zinc-500">
                    <div class="w-12 h-12 rounded-md bg-white border border-zinc-200 p-1 flex items-center justify-center mb-2.5 shrink-0"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                    <p class="text-xs">Chưa có cuộc trò chuyện nào.<br>Bấm nút trên để bắt đầu hỏi Thầy!</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- CỘT PHẢI: KHUNG TRÒ CHUYỆN TRỢ GIẢNG AI --}}
    <div class="lg:col-span-3 flex flex-col rounded-lg border border-zinc-200 bg-white overflow-hidden">
        @if($activeConversation)
            {{-- Header hội thoại --}}
            <div class="flex items-center justify-between border-b border-zinc-200 bg-white px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-md bg-white border border-zinc-200 p-1 flex items-center justify-center shrink-0"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-bold text-slate-800">{{ $activeConversation->title }}</h2>
                            <span id="activeModeBadge" class="rounded bg-blue-100 px-2 py-0.5 text-[11px] font-bold text-[#1e4f8f]">{{ $activeConversation->modeLabel() }}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                            <span>Gia sư: <strong>MR. BIẾT TUỐT</strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-800 border border-emerald-200 shadow-xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        Thầy Biết Tuốt sẵn sàng 
                    </span>
                </div>
            </div>

            {{-- Danh sách tin nhắn --}}
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-5 space-y-4 bg-zinc-50/70">
                @forelse($activeConversation->messages as $msg)
                    <div class="flex gap-3 {{ $msg->isUser() ? 'justify-end' : 'justify-start' }}">
                        @if($msg->isAssistant())
                            <div class="w-9 h-9 rounded-md bg-white border border-zinc-200 p-0.5 flex items-center justify-center shrink-0 mt-0.5"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                        @endif
                        
                        <div class="max-w-[85%] rounded-lg px-4 py-3 text-sm leading-relaxed {{ $msg->isUser() ? 'chat-bubble-user' : 'chat-bubble-ai' }}">
                            {{-- Hiển thị tệp đính kèm nếu có --}}
                            @if($msg->hasAttachment())
                                @php($att = $msg->attachment)
                                <div class="mb-3">
                                    @if(!empty($att['is_image']))
                                        <a href="{{ $att['url'] }}" target="_blank" class="block group relative overflow-hidden rounded-md border border-blue-200 shadow-sm max-w-sm">
                                            <img src="{{ $att['url'] }}" alt="{{ $att['name'] ?? 'Ảnh' }}" class="max-h-56 w-auto object-cover rounded-md transition">
                                            <span class="absolute bottom-1 right-1 rounded-md bg-black/60 px-2 py-0.5 text-[10px] text-white">Phóng to</span>
                                        </a>
                                    @else
                                        <div class="flex items-center gap-3 rounded-md {{ $msg->isUser() ? 'bg-slate-50 border border-slate-200 text-slate-900' : 'bg-slate-100 border border-slate-200 text-slate-800' }} p-3">
                                            <span class="text-2xl"></span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-xs font-bold">{{ $att['name'] ?? 'Tệp tin' }}</p>
                                                <p class="text-[10px] opacity-80">{{ $att['size_formatted'] ?? '' }}</p>
                                            </div>
                                            <a href="{{ $att['url'] }}" download="{{ $att['name'] ?? 'file' }}" class="rounded-lg bg-[#1e4f8f] px-2.5 py-1 text-[11px] font-bold text-white shadow-xs hover:bg-[#163c6d]">Tải về</a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Nội dung tin nhắn text --}}
                            <div class="prose-message">
                                @if($msg->isAssistant())
                                    {!! App\Services\AI\AiTutorViewHelper::renderMarkdown($msg->content) !!}
                                @else
                                    {!! nl2br(\App\Services\ContentModerationService::renderHtml($msg->content)) !!}
                                @endif
                            </div>

                            <div class="mt-2 text-[10px] {{ $msg->isUser() ? 'text-blue-700' : 'text-slate-500' }} text-right">
                                {{ $msg->created_at->format('H:i d/m') }}
                            </div>
                        </div>

                        @if($msg->isUser())
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-blue-100 text-[#1e4f8f] text-xs font-bold">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-center text-zinc-500 py-12">
                        <div class="w-20 h-20 rounded-xl bg-white border border-zinc-200 p-1.5 flex items-center justify-center mb-3 shadow-xs"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                        <h3 class="text-base font-semibold text-zinc-900">Xin chào {{ auth()->user()->full_name }}</h3>
                        <p class="text-sm max-w-lg mt-1 text-zinc-500 leading-relaxed">
                            Thầy là <strong class="text-zinc-900">MR. BIẾT TUỐT (Trợ giảng AI)</strong>, hỗ trợ giải thích kiến thức, phân tích đề bài, rà soát code và ôn luyện cho mọi môn học.
                            Em có thể đặt câu hỏi hoặc <strong class="text-zinc-900">đính kèm ảnh / tài liệu (tối đa 5MB)</strong>.
                        </p>

                        {{-- Gợi ý câu hỏi nhanh --}}
                        <div class="mt-6 flex flex-wrap justify-center gap-2 max-w-xl">
                            <button type="button" onclick="sendQuickPrompt('Tóm tắt giúp em các kiến thức trọng tâm của môn học này bằng sơ đồ trực quan nhé.')" class="rounded-md border border-zinc-200 bg-white px-3.5 py-2 text-xs font-medium text-zinc-700 hover:border-zinc-900 hover:text-zinc-950 transition">
                                Tóm tắt trọng tâm môn học
                            </button>
                            <button type="button" onclick="sendQuickPrompt('Hướng dẫn em từng bước cách phân tích đề và lập dàn ý cho bài tập lớn nhé.')" class="rounded-md border border-zinc-200 bg-white px-3.5 py-2 text-xs font-medium text-zinc-700 hover:border-zinc-900 hover:text-zinc-950 transition">
                                Cách lập dàn ý bài tập lớn
                            </button>
                            <button type="button" onclick="sendQuickPrompt('Cho em 3 câu hỏi trắc nghiệm ôn tập về môn học này kèm giải thích chi tiết nhé.')" class="rounded-md border border-zinc-200 bg-white px-3.5 py-2 text-xs font-medium text-zinc-700 hover:border-zinc-900 hover:text-zinc-950 transition">
                                3 câu hỏi trắc nghiệm ôn tập
                            </button>
                        </div>
                    </div>
                @endforelse

                {{-- Trạng thái đang xử lý --}}
                <div id="aiTypingIndicator" class="hidden flex gap-3 justify-start">
                    <div class="w-9 h-9 rounded-md bg-white border border-zinc-200 p-0.5 flex items-center justify-center shrink-0 mt-0.5"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                    <div class="chat-bubble-ai rounded-lg px-4 py-3 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-zinc-400 typing-dot"></span>
                        <span class="h-2 w-2 rounded-full bg-zinc-600 typing-dot"></span>
                        <span class="h-2 w-2 rounded-full bg-zinc-900 typing-dot"></span>
                        <span class="text-xs text-zinc-600 font-medium ml-1.5">Thầy Biết Tuốt đang phân tích và soạn câu trả lời...</span>
                    </div>
                </div>
            </div>

            {{-- Ô nhập tin nhắn & Tải tệp đính kèm --}}
            <div class="border-t border-slate-200 bg-slate-50 p-4 border border-slate-200">
                {{-- Thanh xem trước tệp đã chọn --}}
                <div id="filePreviewBar" class="hidden mb-3 items-center justify-between rounded-md border border-blue-200 bg-blue-50/70 p-2.5 text-xs text-[#1e4f8f] shadow-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span id="previewFileIcon" class="text-xl"></span>
                        <img id="previewImageThumb" class="hidden h-9 w-9 rounded-lg object-cover border border-blue-300" alt="thumb">
                        <div class="min-w-0">
                            <p id="previewFileName" class="truncate font-bold text-slate-800">ten_tep.png</p>
                            <p id="previewFileSize" class="text-[10px] text-slate-500">1.2 MB</p>
                        </div>
                    </div>
                    <button type="button" onclick="cancelSelectedFile()" class="rounded-lg p-1.5 text-rose-500 hover:bg-rose-100 transition" title="Gỡ tệp này">
                        ✕ Gỡ tệp
                    </button>
                </div>

                <form id="chatForm" onsubmit="handleSendMessage(event)" class="flex items-end gap-2">
                    {{-- Nút chọn tệp đính kèm --}}
                    <div>
                        <input type="file" id="fileAttachmentInput" class="hidden" accept="image/*,.pdf,.doc,.docx,.txt,.java,.py,.php,.js,.html,.css,.sql,.json,.c,.cpp,.cs,.ts,.zip,.rar" onchange="handleFilePicked(event)">
                        <label for="fileAttachmentInput" class="cursor-pointer flex h-11 w-11 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 hover:border-slate-300 transition shadow-xs" title="Đính kèm tệp tin hoặc ảnh đề bài (tối đa 5MB)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </label>
                    </div>

                    {{-- Ô nhập nội dung tin nhắn --}}
                    <div class="flex-1 min-w-0">
                        <textarea id="messageInput" rows="1" placeholder="Hỏi Thầy Biết Tuốt về kiến thức, bài tập hoặc gửi ảnh/tài liệu... (Nhấn Enter để gửi)" class="w-full resize-none rounded-md border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-[#1e4f8f] focus:outline-none focus:ring-2 focus:ring-blue-100 max-h-32" onkeydown="handleInputKey(event)"></textarea>
                    </div>

                    {{-- Chế độ đồng hành cùng Thầy (ngay cạnh nút Gửi đi) --}}
                    <div class="shrink-0">
                        <select id="tutorModeSelect" title="Chế độ đồng hành cùng Thầy" class="h-11 rounded-md border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 focus:border-[#1e4f8f] focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer shadow-xs">
                            <option value="EXPLAIN" {{ $activeConversation->mode === 'EXPLAIN' ? 'selected' : '' }}>Giải thích kiến thức</option>
                            <option value="GUIDANCE" {{ $activeConversation->mode === 'GUIDANCE' ? 'selected' : '' }}>Hướng dẫn bài tập</option>
                            <option value="REVIEW" {{ $activeConversation->mode === 'REVIEW' ? 'selected' : '' }}>Soi lỗi bài làm</option>
                            <option value="PRACTICE" {{ $activeConversation->mode === 'PRACTICE' ? 'selected' : '' }}>Luyện tập câu hỏi</option>
                        </select>
                    </div>

                    {{-- Nút gửi với icon chuẩn --}}
                    <button type="submit" id="btnSend" class="flex h-11 items-center gap-1.5 rounded-md bg-[#1e4f8f] px-4 text-sm font-bold text-white shadow-sm hover:bg-[#163c6d] transition disabled:opacity-50 shrink-0" title="Gửi tin nhắn">
                        <span>Gửi</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                        </svg>
                    </button>
                </form>

                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span><strong>MR. BIẾT TUỐT</strong> hướng dẫn tư duy sư phạm, không giải hộ bài thi chính thức.</span></span>
                    <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg> <span>Hỗ trợ ảnh & file mã nguồn/tài liệu (tối đa <strong>5MB</strong>)</span></span>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-full text-center text-zinc-500 p-8">
                <div class="w-20 h-20 rounded-xl bg-white border border-zinc-200 p-1.5 flex items-center justify-center mb-3 shadow-xs"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                <h3 class="text-base font-semibold text-zinc-900">MR. BIẾT TUỐT — Trợ giảng AI</h3>
                <p class="text-sm max-w-md mt-1 text-zinc-500 mb-6">Chọn một phiên ở cột bên trái hoặc tạo phiên mới để bắt đầu hỏi bài cùng Thầy.</p>
                <form action="{{ route('student.ai-tutor.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="mode" value="EXPLAIN">
                    <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800 transition">
                        + Tạo phiên hỏi đáp mới
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    // Đồng bộ nhãn chế độ trên header ngay khi người dùng đổi chế độ cạnh nút Gửi
    document.addEventListener('DOMContentLoaded', () => {
        const modeSelect = document.getElementById('tutorModeSelect');
        const modeBadge = document.getElementById('activeModeBadge');
        if (modeSelect && modeBadge) {
            modeSelect.addEventListener('change', () => {
                const selectedOption = modeSelect.options[modeSelect.selectedIndex];
                if (selectedOption) {
                    modeBadge.textContent = selectedOption.text;
                }
            });
        }
    });

    function scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    document.addEventListener('DOMContentLoaded', scrollToBottom);

    function sendQuickPrompt(text) {
        const input = document.getElementById('messageInput');
        if (input) {
            input.value = text;
            document.getElementById('chatForm').dispatchEvent(new Event('submit', { cancelable: true }));
        }
    }

    function handleInputKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').dispatchEvent(new Event('submit', { cancelable: true }));
        }
    }

    // Xử lý khi chọn file đính kèm
    function handleFilePicked(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > MAX_FILE_SIZE) {
            const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
            alert(`⚠️ Dung lượng tệp vượt quá giới hạn 5MB (${sizeInMb}MB). Vui lòng chọn tệp nhỏ hơn 5MB!`);
            cancelSelectedFile();
            return;
        }

        const previewBar = document.getElementById('filePreviewBar');
        const fileNameEl = document.getElementById('previewFileName');
        const fileSizeEl = document.getElementById('previewFileSize');
        const thumbEl = document.getElementById('previewImageThumb');
        const iconEl = document.getElementById('previewFileIcon');

        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = (file.size >= 1048576) ? (file.size / 1048576).toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB';

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                thumbEl.src = evt.target.result;
                thumbEl.classList.remove('hidden');
                iconEl.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            thumbEl.classList.add('hidden');
            iconEl.classList.remove('hidden');
            iconEl.textContent = file.name.endsWith('.pdf') ? '' : '';
        }

        previewBar.classList.remove('hidden');
        previewBar.classList.add('flex');
    }

    function cancelSelectedFile() {
        const fileInput = document.getElementById('fileAttachmentInput');
        if (fileInput) fileInput.value = '';

        const previewBar = document.getElementById('filePreviewBar');
        if (previewBar) {
            previewBar.classList.add('hidden');
            previewBar.classList.remove('flex');
        }
    }

    async function handleSendMessage(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const fileInput = document.getElementById('fileAttachmentInput');
        const btn = document.getElementById('btnSend');
        const container = document.getElementById('messagesContainer');
        const typing = document.getElementById('aiTypingIndicator');

        const message = input.value.trim();
        const file = fileInput.files[0] || null;

        if (!message && !file) return;

        // Chuẩn bị preview tin nhắn User
        let userAttachmentHtml = '';
        if (file) {
            if (file.type.startsWith('image/')) {
                const objectUrl = URL.createObjectURL(file);
                userAttachmentHtml = `
                    <div class="mb-2">
                        <img src="${objectUrl}" class="max-h-52 rounded-md border border-blue-300 object-cover" alt="${escapeHtml(file.name)}">
                    </div>
                `;
            } else {
                userAttachmentHtml = `
                    <div class="mb-2 flex items-center gap-2 rounded-md bg-slate-50 p-2 text-xs text-slate-900 border border-slate-200">
                                                <span class="font-bold underline truncate max-w-xs">${escapeHtml(file.name)}</span>
                    </div>
                `;
            }
        }

        const userMsgId = 'user-msg-' + Date.now();
        const userHtml = `
            <div class="flex gap-3 justify-end">
                <div class="max-w-[85%] rounded-lg px-4 py-3 text-sm leading-relaxed chat-bubble-user">
                    ${userAttachmentHtml}
                    <div id="${userMsgId}-text">${message ? escapeHtml(message).replace(/\n/g, '<br>') : ''}</div>
                    <div class="mt-1.5 text-[10px] text-blue-700 text-right">Vừa xong</div>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-blue-100 text-[#1e4f8f] text-xs font-bold">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', userHtml);
        const userMsgTextEl = document.getElementById(`${userMsgId}-text`);

        // Reset input UI
        input.value = '';
        input.disabled = true;
        btn.disabled = true;

        // Ẩn typing indicator cũ (chỉ dùng duy nhất 1 bong bóng "Thầy Biết Tuốt đang suy nghĩ..." bên dưới)
        if (typing) typing.classList.add('hidden');

        // Chuẩn bị FormData gửi lên server (kèm chế độ đồng hành đang chọn)
        const formData = new FormData();
        const modeSelect = document.getElementById('tutorModeSelect');
        if (modeSelect && modeSelect.value) formData.append('mode', modeSelect.value);
        if (message) formData.append('message', message);
        if (file) formData.append('file', file);

        try {
            @if($activeConversation)
            const streamUrl = "{{ route('student.ai-tutor.stream', $activeConversation) }}";
            const fallbackUrl = "{{ route('student.ai-tutor.message', $activeConversation) }}";

            // Tạo ngay khung bong bóng AI với hiệu ứng con trỏ nhấp nháy tức thời
            const aiBubbleId = 'ai-msg-' + Date.now();
            const aiHtml = `
                <div class="flex gap-3 justify-start" id="${aiBubbleId}">
                    <div class="w-9 h-9 rounded-md bg-white border border-zinc-200 p-0.5 flex items-center justify-center shrink-0 mt-0.5"><img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-full h-full object-contain"></div>
                    <div class="max-w-[85%] rounded-lg px-4 py-3 text-sm leading-relaxed chat-bubble-ai">
                        <div class="prose-message" id="${aiBubbleId}-content">
                            <span class="inline-flex items-center gap-2 text-xs text-zinc-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-zinc-400 typing-dot"></span>
                                <span class="h-1.5 w-1.5 rounded-full bg-zinc-600 typing-dot"></span>
                                <span class="h-1.5 w-1.5 rounded-full bg-zinc-900 typing-dot"></span>
                                <span>Thầy Biết Tuốt đang suy nghĩ...</span>
                            </span>
                        </div>
                        <div class="mt-2 text-[10px] text-slate-500 text-right" id="${aiBubbleId}-time">Đang phản hồi...</div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', aiHtml);
            const aiEl = document.getElementById(aiBubbleId);
            const contentEl = document.getElementById(`${aiBubbleId}-content`);
            const timeEl = document.getElementById(`${aiBubbleId}-time`);

            // Neo khung nhìn đúng 1 lần vào đầu câu trả lời của AI để người dùng đọc từ trên xuống, không bị giật nhảy
            if (aiEl) {
                container.scrollTo({ top: Math.max(0, aiEl.offsetTop - 16), behavior: 'smooth' });
            }

            let fullText = '';
            let usedStreaming = false;
            let pendingImageMessageId = null;

            try {
                const response = await fetch(streamUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });

                if (response.ok && response.body) {
                    usedStreaming = true;
                    typing.classList.add('hidden');
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder('utf-8');
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n\n');
                        buffer = lines.pop();

                        for (const line of lines) {
                            const trimmed = line.trim();
                            if (trimmed.startsWith('data: ')) {
                                try {
                                    const json = JSON.parse(trimmed.substring(6));
                                    if (json.chunk) {
                                        fullText += json.chunk;
                                        contentEl.innerHTML = formatAiResponse(fullText) + '<span class="inline-block w-2 h-4 bg-amber-600 align-middle ml-1"></span>';
                                    }
                                    if (json.done) {
                                        if (json.masked_user_message && userMsgTextEl) {
                                            userMsgTextEl.innerHTML = escapeHtml(json.masked_user_message)
                                                .replace(/\[\[BLUR\]\]([\s\S]*?)\[\[\/BLUR\]\]/g, '<span title="Nội dung đã bị làm mờ do ngôn từ không phù hợp" style="filter: blur(5px); user-select: none; -webkit-user-select: none; pointer-events: none; display: inline-block; padding: 0 3px;">$1</span>')
                                                .replace(/\n/g, '<br>');
                                        }
                                        if (json.content) fullText = json.content;
                                        if (json.pending_image_prompt && json.message_id) {
                                            pendingImageMessageId = json.message_id;
                                        }
                                        timeEl.textContent = json.created_at || 'Vừa xong';
                                    }
                                } catch (e) {}
                            }
                        }
                    }
                    contentEl.innerHTML = formatAiResponse(fullText);
                    if (window.renderMermaidDiagrams) window.renderMermaidDiagrams(contentEl);

                    // Nếu có thẻ ảnh chờ vẽ -> gọi vẽ ngầm (Async) không khóa ô chat, tự thay vào đúng vị trí skeleton
                    if (pendingImageMessageId) {
                        fetchDeferredAiImage(pendingImageMessageId, contentEl);
                    }
                }
            } catch (streamErr) {
                console.warn('Streaming glitch, using standard AJAX fallback:', streamErr);
            }

            // Fallback an toàn nếu stream bị ngắt
            if (!usedStreaming || !fullText) {
                const response = await fetch(fallbackUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });
                const resData = await response.json();
                typing.classList.add('hidden');
                if (resData.success) {
                    contentEl.innerHTML = formatAiResponse(resData.data.content);
                    if (window.renderMermaidDiagrams) window.renderMermaidDiagrams(contentEl);
                    timeEl.textContent = resData.data.created_at;
                    if (resData.data.pending_image_prompt && resData.data.message_id) {
                        fetchDeferredAiImage(resData.data.message_id, contentEl);
                    }
                } else {
                    contentEl.innerHTML = '<span class="text-rose-600">⚠️ ' + escapeHtml(resData.message || 'Không thể kết nối') + '</span>';
                }
            }
            @endif
        } catch (err) {
            typing.classList.add('hidden');
            if (contentEl) {
                contentEl.innerHTML = '<span class="text-rose-600 font-semibold">Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!</span>';
            }
            if (window.showOfflineBanner) {
                window.showOfflineBanner('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
            }
        } finally {
            cancelSelectedFile();
            input.disabled = false;
            btn.disabled = false;
            input.focus({ preventScroll: true });
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatAiResponse(text) {
        if (!text) return '';

        // 1. Tách và bảo vệ các khối Code Blocks ```...``` (và dựng card sơ đồ UML/Mermaid)
        const codeBlocks = [];
        let formatted = text.replace(/```([a-zA-Z0-9_-]*)\r?\n([\s\S]*?)```/g, function(match, lang, code) {
            const placeholder = `___CODE_BLOCK_${codeBlocks.length}___`;
            const normLang = (lang || 'code').trim().toLowerCase();
            if (normLang === 'mermaid' && typeof window.buildMermaidCardHtml === 'function') {
                codeBlocks.push(window.buildMermaidCardHtml(code.trim()));
            } else if (typeof window.buildVsCodeBlockHtml === 'function') {
                codeBlocks.push(window.buildVsCodeBlockHtml(normLang, code.replace(/^\n+|\n+$/g, '')));
            } else {
                codeBlocks.push(`<pre><code class="language-${escapeHtml(normLang)}">${escapeHtml(code)}</code></pre>`);
            }
            return placeholder;
        });

        // 1b. Khi đang stream dở khối ```mermaid chưa đóng, hiện khung chờ gọn gàng
        formatted = formatted.replace(/```mermaid\r?\n[\s\S]*$/i, function() {
            const placeholder = `___CODE_BLOCK_${codeBlocks.length}___`;
            codeBlocks.push('<div class="my-3 rounded-lg border border-blue-200 bg-blue-50/60 px-3.5 py-2.5 text-xs font-semibold text-[#1e4f8f]">Đang vẽ sơ đồ minh họa (UML / Mermaid)...</div>');
            return placeholder;
        });

        // 1c. Khi đang stream dở khối code ```sql/php/java... chưa kịp đóng ``` -> vẫn render ngay trong khung VS Code
        formatted = formatted.replace(/```([a-zA-Z0-9_-]*)\r?\n([\s\S]+)$/, function(match, lang, code) {
            const placeholder = `___CODE_BLOCK_${codeBlocks.length}___`;
            const normLang = (lang || 'code').trim().toLowerCase();
            if (typeof window.buildVsCodeBlockHtml === 'function') {
                codeBlocks.push(window.buildVsCodeBlockHtml(normLang || 'code', code.replace(/^\n+|\n+$/g, '')));
            } else {
                codeBlocks.push(`<pre><code class="language-${escapeHtml(normLang)}">${escapeHtml(code)}</code></pre>`);
            }
            return placeholder;
        });

        // 2. Tách và Parse Markdown Tables thành giao diện Bảng đẹp mắt
        const tableBlocks = [];
        const tablePattern = /((?:^[ \t]*\|.+?\|[ \t]*\n)+)/gm;
        formatted = formatted.replace(tablePattern, function(match) {
            const lines = match.trim().split('\n').map(l => l.trim()).filter(l => l.length > 0);
            if (lines.length < 2) return match;

            const headerLine = lines[0];
            const separatorLine = lines[1];
            if (!/^\|[\s\-:|]+\|$/.test(separatorLine)) return match;

            const headerCells = headerLine.split('|').map(c => c.trim()).filter(c => c !== '');
            let tableHtml = '<div class="my-4 overflow-x-auto rounded-md border border-blue-200 bg-white shadow-xs">';
            tableHtml += '<table class="w-full text-left text-xs border-collapse">';
            tableHtml += '<thead class="bg-blue-50 text-[#1e4f8f] font-bold tracking-wide border-b border-blue-200">';
            tableHtml += '<tr>';
            headerCells.forEach(h => {
                let hFmt = escapeHtml(h).replace(/\*\*(.*?)\*\*/g, '<strong class="keyword-highlight">$1</strong>');
                tableHtml += `<th class="px-4 py-3 border-r border-blue-200 last:border-r-0 whitespace-nowrap">${hFmt}</th>`;
            });
            tableHtml += '</tr></thead><tbody class="divide-y divide-slate-100 text-slate-700">';

            for (let i = 2; i < lines.length; i++) {
                if (!lines[i].includes('|')) continue;
                const cells = lines[i].split('|').map(c => c.trim()).filter(c => c !== '');
                tableHtml += '<tr class="hover:bg-blue-50/40 transition">';
                cells.forEach(c => {
                    let cFmt = escapeHtml(c).replace(/\*\*(.*?)\*\*/g, '<strong class="keyword-highlight">$1</strong>');
                    tableHtml += `<td class="px-4 py-2.5 border-r border-slate-100 last:border-r-0 align-top leading-relaxed">${cFmt}</td>`;
                });
                tableHtml += '</tr>';
            }
            tableHtml += '</tbody></table></div>';

            const placeholder = `___TABLE_BLOCK_${tableBlocks.length}___`;
            tableBlocks.push(tableHtml);
            return placeholder;
        });

        // 2. Chuyển đổi headings '### Tiêu đề' hoặc '## Tiêu đề' thành khối tiêu đề đẹp
        formatted = formatted.replace(/^#{1,6}\s*(.+)$/gm, '<div class="text-sm font-bold text-slate-800 mt-3 mb-1.5 flex items-center gap-1.5">$1</div>');

        // 3. Chuyển đổi đường kẻ ngang: ---
        formatted = formatted.replace(/^---+$/gm, '<hr class="my-3 border-slate-200">');

        // 4. In đậm từ khóa cần nhớ: **từ khóa**
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong class="keyword-highlight">$1</strong>');

        // 5. Chuyển đổi bullet points đầu dòng '* ' hoặc '- ' thành biểu tượng danh sách đẹp
        formatted = formatted.replace(/^\s*[\*\-]\s+(.+)$/gm, '<div class="flex items-start gap-2 my-1 text-slate-700"><span class="text-[#1e4f8f] font-bold">•</span><span>$1</span></div>');

        // 6. Loại bỏ dấu * lẻ loi còn sót lại
        formatted = formatted.replace(/(?<!\d)\*(?!\d)/g, '');

        // 7. Loại bỏ dấu ### còn sót lại nếu có
        formatted = formatted.replace(/#{2,}/g, '');

        // 8. Inline code: `code` (font & màu kiểu VS Code)
        formatted = formatted.replace(/`([^`]+)`/g, '<code class="vscode-inline-code">$1</code>');

        // 8b. Ảnh minh họa Markdown: ![alt](url)
        formatted = formatted.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, function(m, alt, url) {
            const safeAlt = escapeHtml(alt || 'Hình ảnh minh họa AI');
            const safeUrl = escapeHtml((url || '').trim());
            return `<div class="my-3 rounded-lg border border-slate-200 bg-white p-2 shadow-xs max-w-md"><a href="${safeUrl}" target="_blank" class="block overflow-hidden rounded"><img src="${safeUrl}" alt="${safeAlt}" class="w-full h-auto rounded object-contain"></a><p class="mt-1.5 text-[11px] text-slate-500 text-center">${safeAlt} (bấm để xem kích thước gốc)</p></div>`;
        });

        // 8c. Khung chờ ảnh bất đồng bộ [IMAGE: ...] ngay tại đúng vị trí AI đặt ảnh
        formatted = formatted.replace(/\[IMAGE:\s*[^\]]+\]/gi, function() {
            return `<div class="ai-image-skeleton my-3 rounded-lg border border-slate-200 bg-slate-50 p-4 max-w-md flex items-center gap-3 animate-pulse">
                <div class="h-11 w-11 rounded-md bg-slate-200 shrink-0 flex items-center justify-center text-slate-400">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                </div>
                <div class="flex-1 space-y-1.5">
                    <div class="text-xs font-semibold text-slate-700">Đang vẽ hình minh họa AI...</div>
                    <div class="text-[11px] text-slate-500">Bạn cứ đọc nội dung phía trên, ảnh sẽ tự hiện vào đúng vị trí này.</div>
                </div>
            </div>`;
        });

        // 9. Xuống dòng
        formatted = formatted.replace(/\n/g, '<br>');

        // 10. Trả lại các khối Table đẹp mắt
        tableBlocks.forEach((tableHtml, idx) => {
            formatted = formatted.replace(`___TABLE_BLOCK_${idx}___`, tableHtml);
        });

        // 11. Trả lại các khối code blocks nguyên bản
        codeBlocks.forEach((codeHtml, idx) => {
            formatted = formatted.replace(`___CODE_BLOCK_${idx}___`, codeHtml);
        });

        return formatted;
    }

    async function fetchDeferredAiImage(messageId, contentEl) {
        if (!messageId || !contentEl) return;
        const url = `{{ url('/student/ai-tutor/messages') }}/${messageId}/generate-image`;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();
            const slot = contentEl.querySelector('.ai-image-skeleton');
            if (!slot) return;

            if (data.success && data.image_url) {
                const safeUrl = escapeHtml(data.image_url);
                slot.outerHTML = `<div class="my-3 rounded-lg border border-slate-200 bg-white p-2 shadow-xs max-w-md"><a href="${safeUrl}" target="_blank" class="block overflow-hidden rounded"><img src="${safeUrl}" alt="Hình ảnh minh họa AI" class="w-full h-auto rounded object-contain"></a><p class="mt-1.5 text-[11px] text-slate-500 text-center">Hình ảnh minh họa AI (bấm để xem kích thước gốc)</p></div>`;
            } else {
                slot.remove();
            }
        } catch (err) {
            const slot = contentEl.querySelector('.ai-image-skeleton');
            if (slot) slot.remove();
        }
    }
</script>
@endpush
