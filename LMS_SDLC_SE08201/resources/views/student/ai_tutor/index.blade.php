@extends('layouts.app')

@section('title', 'MR. BIẾT TUỐT — Thầy Giáo Sư AI Thông Thái')

@push('styles')
<style>
    .chat-bubble-user {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border-bottom-right-radius: 4px;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
    }
    .chat-bubble-ai {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        color: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-bottom-left-radius: 4px;
        box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(226, 232, 240, 0.8);
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
        background-color: #fef3c7;
        padding: 1px 5px;
        border-radius: 4px;
        border: 1px solid #fde68a;
    }
</style>
@endpush

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="flex items-center gap-2.5">
            <div class="w-12 h-12 rounded-2xl overflow-hidden border-2 border-[#D4FF24]/70 shadow-lg shadow-[#1558C9]/40 shrink-0 p-0.5" style="background: linear-gradient(135deg, #1558C9 0%, #0a0d17 100%);">
                <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                    <rect x="8" y="6" width="20" height="4" rx="2" fill="#D4FF24" opacity="0.95"/>
                    <rect x="13" y="2" width="10" height="6" rx="2" fill="#D4FF24" opacity="0.9"/>
                    <rect x="14" y="2" width="8" height="2" rx="1" fill="#c2ee12"/>
                    <circle cx="18" cy="20" r="9" fill="#FFD6A5" stroke="#D4FF24" stroke-width="0.8"/>
                    <ellipse cx="14" cy="19" rx="2.2" ry="2.5" fill="#1558C9"/>
                    <ellipse cx="22" cy="19" rx="2.2" ry="2.5" fill="#1558C9"/>
                    <circle cx="14.9" cy="18.2" r="0.9" fill="white"/>
                    <circle cx="22.9" cy="18.2" r="0.9" fill="white"/>
                    <ellipse cx="11" cy="22.5" rx="2" ry="1.1" fill="#ff9eb5" opacity="0.5"/>
                    <ellipse cx="25" cy="22.5" rx="2" ry="1.1" fill="#ff9eb5" opacity="0.5"/>
                    <path d="M14.5 23.5 Q18 26.5 21.5 23.5" stroke="#c0763a" stroke-width="1.3" stroke-linecap="round" fill="none"/>
                    <rect x="11.5" y="17.5" width="5" height="3.5" rx="1.8" fill="none" stroke="#D4FF24" stroke-width="1" opacity="0.9"/>
                    <rect x="19.5" y="17.5" width="5" height="3.5" rx="1.8" fill="none" stroke="#D4FF24" stroke-width="1" opacity="0.9"/>
                    <line x1="16.5" y1="19.3" x2="19.5" y2="19.3" stroke="#D4FF24" stroke-width="0.8" opacity="0.8"/>
                    <path d="M29 10 L29.6 12 L31.5 12 L30 13.2 L30.6 15 L29 13.9 L27.4 15 L28 13.2 L26.5 12 L28.4 12 Z" fill="#D4FF24" opacity="0.9"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-orange-300 to-amber-200 drop-shadow-md">MR. BIẾT TUỐT</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 px-3.5 py-1 text-xs font-black text-slate-950 shadow-lg shadow-amber-400/25 border border-amber-300">
                        <svg class="w-3.5 h-3.5 text-slate-950 fill-current" viewBox="0 0 24 24"><path d="M12 2l2.4 7.2h7.6l-6 4.8 2.4 7.2-6.4-4.8-6.4 4.8 2.4-7.2-6-4.8h7.6z"/></svg>
                        <span>Thầy Giáo Sư AI</span>
                    </span>
                </div>
                <p class="text-xs text-slate-300 mt-1">
                    Gia sư AI thông thái: xưng <strong>thầy</strong> gọi <strong>trò</strong>, giải thích bản chất kiến thức, hướng dẫn bài tập Socratic và hỗ trợ tải ảnh/tệp đến <strong>5MB</strong>.
                </p>
            </div>
        </div>
    </div>
    
    <button type="button" onclick="openNewChatModal()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-md hover:bg-indigo-700 transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        <span>Cuộc trò chuyện mới</span>
    </button>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-4 chat-container">
    {{-- CỘT TRÁI: DANH SÁCH CUỘC TRÒ CHUYỆN --}}
    <div class="lg:col-span-1 flex flex-col rounded-2xl border border-white/10 bg-slate-900/80 p-4 border border-white/10 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Lịch sử hỏi Thầy</span>
            </span>
            <span class="rounded-full bg-indigo-50 text-indigo-700 px-2 py-0.5 text-xs font-bold">{{ $conversations->count() }}</span>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 pr-1">
            @forelse($conversations as $c)
                @php($isActive = $activeConversation && $activeConversation->conversation_id === $c->conversation_id)
                <div class="group relative flex items-center justify-between rounded-xl p-3 transition {{ $isActive ? 'bg-indigo-50/80 border border-indigo-200 text-indigo-900 shadow-sm' : 'hover:bg-slate-900/60 text-slate-200 border border-transparent' }}">
                    <a href="{{ route('student.ai-tutor.show', $c) }}" class="flex-1 min-w-0 pr-2">
                        <p class="truncate text-sm font-semibold {{ $isActive ? 'text-indigo-950 font-bold' : '' }}">
                            {{ $c->title }}
                        </p>
                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400">
                            @if($c->subject)
                                <span class="rounded bg-indigo-100/70 px-1.5 py-0.5 font-bold text-indigo-700">{{ $c->subject->subject_code }}</span>
                            @endif
                            <span>{{ $c->updated_at->diffForHumans() }}</span>
                        </div>
                    </a>

                    <form action="{{ route('student.ai-tutor.destroy', $c) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa cuộc trò chuyện này với thầy Biết Tuốt?');" class="opacity-0 group-hover:opacity-100 transition">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Xóa">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-48 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-amber-400/40 shadow-md mb-2 shrink-0"><img src="/images/mr_biet_tuot.jpg" class="w-full h-full object-cover" alt="Thầy Biết Tuốt"></div>
                    <p class="text-xs">Chưa có cuộc trò chuyện nào.<br>Bấm nút trên để bắt đầu hỏi thầy!</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- CỘT PHẢI: KHUNG TRÒ CHUYỆN VỚI THẦY BIẾT TUỐT --}}
    <div class="lg:col-span-3 flex flex-col rounded-2xl border border-white/10 bg-slate-900/80 shadow-2xl overflow-hidden backdrop-blur-md">
        @if($activeConversation)
            {{-- Header hội thoại --}}
            <div class="flex items-center justify-between border-b border-white/10 bg-slate-900/60/70 px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden border border-amber-400/60 shadow-md shrink-0"><img src="/images/mr_biet_tuot.jpg" alt="MR. BIẾT TUỐT" class="w-full h-full object-cover"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-100">{{ $activeConversation->title }}</h2>
                            <span class="rounded bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-800">{{ $activeConversation->modeLabel() }}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400">
                            <span>Gia sư: <strong>MR. BIẾT TUỐT</strong></span>
                            @if($activeConversation->subject)
                                <span>· Môn: <strong class="text-slate-200">{{ $activeConversation->subject->subject_name }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-800 border border-emerald-200 shadow-xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Thầy Biết Tuốt sẵn sàng ☕
                    </span>
                </div>
            </div>

            {{-- Danh sách tin nhắn --}}
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-5 space-y-4 bg-slate-900/60/40">
                @forelse($activeConversation->messages as $msg)
                    <div class="flex gap-3 {{ $msg->isUser() ? 'justify-end' : 'justify-start' }}">
                        @if($msg->isAssistant())
                            <div class="w-9 h-9 rounded-xl overflow-hidden border border-amber-400/50 shadow shrink-0"><img src="/images/mr_biet_tuot.jpg" alt="Thầy Biết Tuốt" class="w-full h-full object-cover"></div>
                        @endif
                        
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed {{ $msg->isUser() ? 'chat-bubble-user' : 'chat-bubble-ai' }}">
                            {{-- Hiển thị tệp đính kèm nếu có --}}
                            @if($msg->hasAttachment())
                                @php($att = $msg->attachment)
                                <div class="mb-3">
                                    @if(!empty($att['is_image']))
                                        <a href="{{ $att['url'] }}" target="_blank" class="block group relative overflow-hidden rounded-xl border border-indigo-200/50 shadow-sm max-w-sm">
                                            <img src="{{ $att['url'] }}" alt="{{ $att['name'] ?? 'Ảnh' }}" class="max-h-56 w-auto object-cover rounded-xl transition group-hover:scale-105">
                                            <span class="absolute bottom-1 right-1 rounded-md bg-black/60 px-2 py-0.5 text-[10px] text-white backdrop-blur-xs">🔍 Phóng to</span>
                                        </a>
                                    @else
                                        <div class="flex items-center gap-3 rounded-xl {{ $msg->isUser() ? 'bg-white/15 border border-white/20 text-white' : 'bg-slate-100 border border-white/10 text-slate-100' }} p-3">
                                            <span class="text-2xl">📄</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-xs font-bold">{{ $att['name'] ?? 'Tệp tin' }}</p>
                                                <p class="text-[10px] opacity-80">{{ $att['size_formatted'] ?? '' }}</p>
                                            </div>
                                            <a href="{{ $att['url'] }}" download="{{ $att['name'] ?? 'file' }}" class="rounded-lg bg-indigo-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-xs hover:bg-indigo-700">Tải về</a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Nội dung tin nhắn text --}}
                            <div class="prose-message">
                                @if($msg->isAssistant())
                                    {!! App\Services\AI\AiTutorViewHelper::renderMarkdown($msg->content) !!}
                                @else
                                    {!! nl2br(e($msg->content)) !!}
                                @endif
                            </div>

                            <div class="mt-2 text-[10px] {{ $msg->isUser() ? 'text-indigo-200' : 'text-slate-400' }} text-right">
                                {{ $msg->created_at->format('H:i d/m') }}
                            </div>
                        </div>

                        @if($msg->isUser())
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-800 text-xs font-bold">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-center text-slate-400 py-12">
                        <div class="w-24 h-24 rounded-3xl overflow-hidden border-2 border-amber-400/70 shadow-2xl shadow-amber-500/30 mb-4 p-1 bg-gradient-to-tr from-amber-500 to-orange-500"><img src="/images/mr_biet_tuot.jpg" alt="Thầy Biết Tuốt" class="w-full h-full object-cover rounded-2xl"></div>
                        <h3 class="text-lg font-black text-slate-100">Chào học trò {{ auth()->user()->full_name }}!</h3>
                        <p class="text-sm max-w-lg mt-1 text-slate-400 leading-relaxed">
                            Thầy là <strong>MR. BIẾT TUỐT</strong>. Thầy luôn sẵn sàng giúp em thấu hiểu bản chất bài học, gỡ lỗi code và rèn luyện tư duy lập trình 24/7.
                            Em có thể đặt câu hỏi hoặc <strong>tải ảnh đề bài/tệp code (tối đa 5MB)</strong> lên để thầy xem nhé! 💡🚀
                        </p>
                        
                        {{-- Gợi ý câu hỏi nhanh --}}
                        <div class="mt-6 flex flex-wrap justify-center gap-2 max-w-xl">
                            <button type="button" onclick="sendQuickPrompt('Thầy giải thích giúp em khái niệm 4 tính chất OOP bằng ví dụ đời thực với ạ?')" class="rounded-full border border-indigo-200 bg-white px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 shadow-xs transition">
                                💡 Giải thích 4 tính chất OOP
                            </button>
                            <button type="button" onclick="sendQuickPrompt('Thầy hướng dẫn từng bước cách thiết kế khóa chính và khóa ngoại trong MySQL chuẩn ạ?')" class="rounded-full border border-indigo-200 bg-white px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 shadow-xs transition">
                                🧭 Thiết kế Khóa chính & Khóa ngoại
                            </button>
                            <button type="button" onclick="sendQuickPrompt('Thầy cho em 3 câu hỏi trắc nghiệm tự luyện về môn học này kèm giải thích chi tiết ạ?')" class="rounded-full border border-indigo-200 bg-white px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 shadow-xs transition">
                                ✍️ 3 câu hỏi trắc nghiệm tự luyện
                            </button>
                        </div>
                    </div>
                @endforelse

                {{-- Khung hiển thị khi Thầy đang suy nghĩ --}}
                <div id="aiTypingIndicator" class="hidden flex gap-3 justify-start">
                    <div class="w-9 h-9 rounded-xl overflow-hidden border border-amber-400/50 shadow shrink-0"><img src="/images/mr_biet_tuot.jpg" alt="Thầy Biết Tuốt" class="w-full h-full object-cover"></div>
                    <div class="chat-bubble-ai rounded-2xl px-4 py-3 flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500 typing-dot"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-indigo-500 typing-dot"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-cyan-500 typing-dot"></span>
                        <span class="text-xs text-slate-300 font-semibold ml-2">Thầy BIẾT TUỐT đang suy nghĩ và chuẩn bị lời giải thích cho em... ☕</span>
                    </div>
                </div>
            </div>

            {{-- Ô nhập tin nhắn & Tải tệp đính kèm --}}
            <div class="border-t border-white/10 bg-slate-900/80 p-4 border border-white/10">
                {{-- Thanh xem trước tệp đã chọn --}}
                <div id="filePreviewBar" class="hidden mb-3 items-center justify-between rounded-xl border border-indigo-200 bg-indigo-50/70 p-2.5 text-xs text-indigo-900 shadow-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span id="previewFileIcon" class="text-xl">📎</span>
                        <img id="previewImageThumb" class="hidden h-9 w-9 rounded-lg object-cover border border-indigo-300" alt="thumb">
                        <div class="min-w-0">
                            <p id="previewFileName" class="truncate font-bold text-slate-100">ten_tep.png</p>
                            <p id="previewFileSize" class="text-[10px] text-slate-400">1.2 MB</p>
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
                        <label for="fileAttachmentInput" class="cursor-pointer flex h-11 w-11 items-center justify-center rounded-xl border border-white/15 bg-slate-900/60 text-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-400 transition shadow-xs" title="Đính kèm tệp tin hoặc ảnh đề bài (tối đa 5MB)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </label>
                    </div>

                    {{-- Ô nhập nội dung tin nhắn --}}
                    <div class="flex-1">
                        <textarea id="messageInput" rows="1" placeholder="Hỏi thầy Biết Tuốt về bài học, bài tập hoặc gửi ảnh/code cần thầy sửa... (Nhấn Enter để gửi)" class="w-full resize-none rounded-xl border border-white/15 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 max-h-32" onkeydown="handleInputKey(event)"></textarea>
                    </div>

                    {{-- Nút gửi --}}
                    <button type="submit" id="btnSend" class="flex h-11 items-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 transition disabled:opacity-50">
                        <span>Gửi</span>
                        <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>

                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                    <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span><strong>MR. BIẾT TUỐT</strong> hướng dẫn tư duy sư phạm, không giải hộ bài thi chính thức.</span></span>
                    <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg> <span>Hỗ trợ ảnh & file mã nguồn/tài liệu (tối đa <strong>5MB</strong>)</span></span>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-full text-center text-slate-400 p-8">
                <div class="w-24 h-24 rounded-3xl overflow-hidden border-2 border-amber-400/70 shadow-2xl shadow-amber-500/30 mb-4 p-1 bg-gradient-to-tr from-amber-500 to-orange-500"><img src="/images/mr_biet_tuot.jpg" alt="Thầy Biết Tuốt" class="w-full h-full object-cover rounded-2xl"></div>
                <h3 class="text-lg font-bold text-slate-200">Chào mừng đến với MR. BIẾT TUỐT!</h3>
                <p class="text-sm max-w-md mt-1 text-slate-400 mb-6">Bạn chưa chọn hoặc chưa có cuộc trò chuyện nào. Hãy bắt đầu một phiên gia sư mới để nhận sự hướng dẫn tận tình từ thầy nhé!</p>
                <button type="button" onclick="openNewChatModal()" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-indigo-700 transition">
                    Bắt đầu cuộc trò chuyện mới với Thầy
                </button>
            </div>
        @endif
    </div>
</div>

{{-- MODAL TẠO CUỘC TRÒ CHUYỆN MỚI --}}
<div id="newChatModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/75 backdrop-blur-md p-4">
    <div class="w-full max-w-lg rounded-2xl glass-dark bg-slate-900/95 p-6 shadow-2xl border border-white/15 text-slate-100">
        <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg overflow-hidden border border-amber-400/50 shadow-sm shrink-0"><img src="/images/mr_biet_tuot.jpg" class="w-full h-full object-cover"></div>
                <span>Khởi tạo phiên hỏi đáp với MR. BIẾT TUỐT</span>
            </h3>
            <button type="button" onclick="closeNewChatModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white transition">✕</button>
        </div>

        <form action="{{ route('student.ai-tutor.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Môn học cần hỗ trợ</label>
                <select name="subject_id" class="w-full rounded-xl border border-white/15 bg-slate-800/90 text-white px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="" class="bg-slate-900 text-slate-300">-- Chọn môn học (Tùy chọn) --</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->subject_id }}" class="bg-slate-900 text-white">{{ $s->subject_code }} — {{ $s->subject_name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Khi chọn môn học, thầy sẽ ưu tiên bám sát giáo trình và tài liệu của môn đó.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Chế độ đồng hành cùng Thầy</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 p-3 cursor-pointer hover:bg-white/10 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-600/20 transition">
                        <input type="radio" name="mode" value="EXPLAIN" checked class="text-indigo-600">
                        <div class="text-xs">
                            <strong class="block text-white">Giải thích kiến thức</strong>
                            <span class="text-slate-400">Hiểu sâu bản chất</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 p-3 cursor-pointer hover:bg-white/10 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-600/20 transition">
                        <input type="radio" name="mode" value="GUIDANCE" class="text-indigo-600">
                        <div class="text-xs">
                            <strong class="block text-white">Hướng dẫn bài tập</strong>
                            <span class="text-slate-400">Gợi mở từng bước</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 p-3 cursor-pointer hover:bg-white/10 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-600/20 transition">
                        <input type="radio" name="mode" value="REVIEW" class="text-indigo-600">
                        <div class="text-xs">
                            <strong class="block text-white">Soi lỗi bài làm</strong>
                            <span class="text-slate-400">Bắt lỗi & tối ưu</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 p-3 cursor-pointer hover:bg-white/10 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-600/20 transition">
                        <input type="radio" name="mode" value="PRACTICE" class="text-indigo-600">
                        <div class="text-xs">
                            <strong class="block text-white">Luyện tập câu hỏi</strong>
                            <span class="text-slate-400">Thử thách phản xạ</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Lời nhắn đầu tiên gửi Thầy (Tùy chọn)</label>
                <textarea name="initial_message" rows="2" placeholder="Ví dụ: Thầy giúp em hiểu rõ về tính Đa hình (Polymorphism) trong Java với ạ..." class="w-full rounded-xl border border-white/15 bg-slate-800/90 text-white px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none placeholder-slate-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="closeNewChatModal()" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-300 hover:bg-white/10 transition">Hủy</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 transition">Bắt đầu trò chuyện</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    function openNewChatModal() {
        document.getElementById('newChatModal').classList.remove('hidden');
    }
    function closeNewChatModal() {
        document.getElementById('newChatModal').classList.add('hidden');
    }

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
            iconEl.textContent = file.name.endsWith('.pdf') ? '📕' : '📄';
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
                        <img src="${objectUrl}" class="max-h-52 rounded-xl border border-indigo-300 object-cover" alt="${escapeHtml(file.name)}">
                    </div>
                `;
            } else {
                userAttachmentHtml = `
                    <div class="mb-2 flex items-center gap-2 rounded-xl bg-white/20 p-2 text-xs text-white border border-white/20">
                        <span>📄</span>
                        <span class="font-bold underline truncate max-w-xs">${escapeHtml(file.name)}</span>
                    </div>
                `;
            }
        }

        const userHtml = `
            <div class="flex gap-3 justify-end">
                <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed chat-bubble-user">
                    ${userAttachmentHtml}
                    ${message ? escapeHtml(message).replace(/\n/g, '<br>') : ''}
                    <div class="mt-1.5 text-[10px] text-indigo-200 text-right">Vừa xong</div>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-800 text-xs font-bold">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', userHtml);

        // Reset input UI
        input.value = '';
        input.disabled = true;
        btn.disabled = true;

        // Hiện typing indicator & cuộn
        typing.classList.remove('hidden');
        container.appendChild(typing);
        scrollToBottom();

        // Chuẩn bị FormData gửi lên server
        const formData = new FormData();
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
                    <div class="w-9 h-9 rounded-xl overflow-hidden border border-amber-400/50 shadow shrink-0"><img src="/images/mr_biet_tuot.jpg" alt="Thầy Biết Tuốt" class="w-full h-full object-cover"></div>
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed chat-bubble-ai">
                        <div class="prose-message" id="${aiBubbleId}-content">
                            <span class="inline-block w-2 h-4 bg-amber-400 animate-pulse align-middle ml-1"></span>
                        </div>
                        <div class="mt-2 text-[10px] text-slate-400 text-right" id="${aiBubbleId}-time">Đang phản hồi...</div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', aiHtml);
            const contentEl = document.getElementById(`${aiBubbleId}-content`);
            const timeEl = document.getElementById(`${aiBubbleId}-time`);

            let fullText = '';
            let usedStreaming = false;

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
                                        contentEl.innerHTML = formatAiResponse(fullText) + '<span class="inline-block w-2 h-4 bg-amber-400 animate-pulse align-middle ml-1"></span>';
                                        scrollToBottom();
                                    }
                                    if (json.done) {
                                        timeEl.textContent = json.created_at || 'Vừa xong';
                                    }
                                } catch (e) {}
                            }
                        }
                    }
                    contentEl.innerHTML = formatAiResponse(fullText);
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
                    timeEl.textContent = resData.data.created_at;
                } else {
                    contentEl.innerHTML = '<span class="text-rose-400">⚠️ ' + escapeHtml(resData.message || 'Không thể kết nối') + '</span>';
                }
            }
            @endif
        } catch (err) {
            typing.classList.add('hidden');
            alert('Lỗi kết nối mạng: ' + err.message);
        } finally {
            cancelSelectedFile();
            input.disabled = false;
            btn.disabled = false;
            input.focus();
            scrollToBottom();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatAiResponse(text) {
        if (!text) return '';

        // 1. Tách và bảo vệ các khối Code Blocks ```...```
        const codeBlocks = [];
        let formatted = text.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, function(match, lang, code) {
            const placeholder = `___CODE_BLOCK_${codeBlocks.length}___`;
            codeBlocks.push(`<pre><code class="language-${escapeHtml(lang || 'code')}">${escapeHtml(code)}</code></pre>`);
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
            let tableHtml = '<div class="my-4 overflow-x-auto rounded-xl border border-indigo-200/80 bg-white shadow-xs">';
            tableHtml += '<table class="w-full text-left text-xs border-collapse">';
            tableHtml += '<thead class="bg-gradient-to-r from-indigo-50 to-indigo-100/70 text-indigo-950 font-black tracking-wide border-b border-indigo-200/80">';
            tableHtml += '<tr>';
            headerCells.forEach(h => {
                let hFmt = escapeHtml(h).replace(/\*\*(.*?)\*\*/g, '<strong class="keyword-highlight">$1</strong>');
                tableHtml += `<th class="px-4 py-3 border-r border-indigo-100/80 last:border-r-0 whitespace-nowrap">${hFmt}</th>`;
            });
            tableHtml += '</tr></thead><tbody class="divide-y divide-slate-100 text-slate-200">';

            for (let i = 2; i < lines.length; i++) {
                if (!lines[i].includes('|')) continue;
                const cells = lines[i].split('|').map(c => c.trim()).filter(c => c !== '');
                tableHtml += '<tr class="hover:bg-indigo-50/40 transition">';
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
        formatted = formatted.replace(/^#{1,6}\s*(.+)$/gm, '<div class="text-sm font-black text-slate-100 mt-3 mb-1.5 flex items-center gap-1.5">$1</div>');

        // 3. Chuyển đổi đường kẻ ngang: ---
        formatted = formatted.replace(/^---+$/gm, '<hr class="my-3 border-white/10">');

        // 4. In đậm từ khóa cần nhớ: **từ khóa**
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong class="keyword-highlight">$1</strong>');

        // 5. Chuyển đổi bullet points đầu dòng '* ' hoặc '- ' thành biểu tượng danh sách đẹp
        formatted = formatted.replace(/^\s*[\*\-]\s+(.+)$/gm, '<div class="flex items-start gap-2 my-1 text-slate-200"><span class="text-indigo-500 font-bold">•</span><span>$1</span></div>');

        // 6. Loại bỏ dấu * lẻ loi còn sót lại
        formatted = formatted.replace(/(?<!\d)\*(?!\d)/g, '');

        // 7. Loại bỏ dấu ### còn sót lại nếu có
        formatted = formatted.replace(/#{2,}/g, '');

        // 8. Inline code: `code`
        formatted = formatted.replace(/`([^`]+)`/g, '<code class="bg-slate-100 text-indigo-700 px-1.5 py-0.5 rounded text-xs">$1</code>');

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
</script>
@endpush
