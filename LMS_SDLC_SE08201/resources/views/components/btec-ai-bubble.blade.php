{{-- BTEC FPT AI Student Support - Floating Bubble Widget (Draggable & Dismissible Edition) --}}
<div id="btec-ai-widget-container" class="fixed bottom-4 right-4 z-50 font-sans select-none touch-none">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- 1. FLOATING TRIGGER BUTTON (HIGH-TECH MINIMAL) --}}
    <div id="btec-ai-trigger" class="relative group cursor-grab active:cursor-grabbing">
        <button type="button"
                id="btec-ai-dismiss-btn"
                title="Ẩn trợ lý AI"
                class="absolute -top-1.5 -left-1.5 z-30 w-4 h-4 rounded-full bg-white border border-zinc-300 hover:border-zinc-900 text-zinc-500 hover:text-zinc-950 flex items-center justify-center text-[9px] font-bold transition opacity-0 group-hover:opacity-100">
            ✕
        </button>

        <div id="btec-ai-open-btn"
             class="relative flex items-center bg-zinc-950 hover:bg-zinc-900 text-white p-1.5 rounded-full border border-zinc-800 shadow-[0_4px_16px_rgba(9,9,11,0.18)] transition-all duration-200">

            <div class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white border border-zinc-700 shrink-0 pointer-events-none">
                <img src="{{ asset('images/btec_support_logo.png') }}" alt="Trợ lý Học vụ AI" class="w-full h-full rounded-full object-cover">
                <span class="absolute -top-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 border-2 border-zinc-950"></span>
            </div>

            <div class="text-left max-w-0 opacity-0 overflow-hidden group-hover:max-w-[155px] group-hover:opacity-100 group-hover:pl-2.5 group-hover:pr-3 transition-all duration-200 whitespace-nowrap pointer-events-none">
                <div class="text-[9px] font-mono uppercase tracking-widest text-zinc-400 leading-tight">
                    AI SUPPORT · 24/7
                </div>
                <div class="text-xs font-semibold text-white leading-tight">
                    Trợ lý Học vụ
                </div>
            </div>
        </div>
    </div>

    {{-- 2. CHAT WINDOW --}}
    <div id="btec-ai-window" class="hidden fixed bottom-4 right-4 sm:right-5 w-[92vw] sm:w-[400px] max-w-[94vw] h-[540px] max-h-[85vh] bg-white rounded-lg shadow-[0_12px_40px_rgba(9,9,11,0.14)] border border-zinc-200 flex flex-col overflow-hidden transition-all duration-200 z-50">

        {{-- Header --}}
        <div class="bg-zinc-950 text-white px-4 py-3 flex items-center justify-between border-b border-zinc-800 cursor-move" id="btec-window-header">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-white border border-zinc-700 overflow-hidden flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/btec_support_logo.png') }}" alt="Trợ lý Học vụ AI" class="w-full h-full object-cover">
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-sm tracking-tight text-white">Trợ lý Học vụ LMS</h3>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-mono uppercase bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Online</span>
                    </div>
                    <p class="text-[11px] text-zinc-400">Tra cứu lịch, điểm, chuyên cần & quy chế</p>
                </div>
            </div>
            <div class="flex items-center gap-1 text-zinc-400">
                <button type="button" id="btec-ai-expand-btn" title="Phóng to / Thu gọn" class="p-1.5 hover:text-white hover:bg-zinc-800 rounded transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                </button>
                <button type="button" id="btec-ai-clear-btn" title="Phiên mới" class="p-1.5 hover:text-white hover:bg-zinc-800 rounded transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                <button type="button" id="btec-ai-close-btn" title="Đóng" class="p-1.5 hover:text-white hover:bg-zinc-800 rounded transition text-sm">
                    ✕
                </button>
            </div>
        </div>

        {{-- Notice Banner --}}
        <div class="bg-zinc-50 border-b border-zinc-200 px-3.5 py-1.5 flex items-center gap-2 text-[11px] text-zinc-600">
            <svg class="w-3.5 h-3.5 text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Dữ liệu đối soát trực tiếp từ LMS & Sổ tay đào tạo.</span>
        </div>

        {{-- Messages Container --}}
        <div id="btec-ai-messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-white text-sm">
            {{-- Welcome Bot Message --}}
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-full bg-white border border-zinc-200 overflow-hidden flex items-center justify-center shrink-0 mt-0.5">
                    <img src="{{ asset('images/btec_support_logo.png') }}" alt="Trợ lý Học vụ AI" class="w-full h-full object-cover">
                </div>
                <div class="max-w-[88%] bg-zinc-50 p-3.5 rounded-lg rounded-tl-none border border-zinc-200 text-zinc-800 space-y-2">
                    <p class="font-semibold text-zinc-950">Xin chào {{ auth()->user()->full_name }}!</p>
                    <p class="text-sm leading-relaxed text-zinc-700">
                        Mình là <strong class="font-semibold text-zinc-950">Trợ lý Học vụ LMS</strong>, hỗ trợ tra cứu 24/7:
                    </p>
                    <ul class="text-sm space-y-1 text-zinc-600 list-disc list-inside leading-relaxed">
                        <li>Thời khóa biểu, phòng học, giảng viên phụ trách.</li>
                        <li>Bảng điểm, trạng thái chấm bài & chuyên cần (ngưỡng <strong class="font-semibold text-zinc-900">20%</strong>).</li>
                        <li>Quy chế đào tạo, thủ tục học vụ & gửi phiếu hỗ trợ.</li>
                    </ul>
                </div>
            </div>

            {{-- Quick Starter Chips --}}
            <div id="btec-quick-chips" class="pt-1 pb-2 space-y-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-1">Gợi ý nhanh:</p>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" class="btec-chip text-left text-xs bg-slate-50 hover:bg-slate-100 hover:border-[#1e4f8f] text-slate-600 hover:text-[#163c6d] px-3 py-1.5 rounded-md border border-slate-200 transition">
                        Nghỉ bao nhiêu buổi thì bị trượt môn?
                    </button>
                    <button type="button" class="btec-chip text-left text-xs bg-slate-50 hover:bg-slate-100 hover:border-[#1e4f8f] text-slate-600 hover:text-[#163c6d] px-3 py-1.5 rounded-md border border-slate-200 transition">
                        Quy định nộp trễ Assignment thế nào?
                    </button>
                    <button type="button" class="btec-chip text-left text-xs bg-slate-50 hover:bg-slate-100 hover:border-[#1e4f8f] text-slate-600 hover:text-[#163c6d] px-3 py-1.5 rounded-md border border-slate-200 transition">
                        Xem lịch học và điểm danh ở đâu trong app?
                    </button>
                    <button type="button" class="btec-chip text-left text-xs bg-slate-50 hover:bg-slate-100 hover:border-[#1e4f8f] text-slate-600 hover:text-[#163c6d] px-3 py-1.5 rounded-md border border-slate-200 transition">
                        Học phí đóng muộn có bị phạt không?
                    </button>
                </div>
            </div>
        </div>

        {{-- Typing indicator --}}
        <div id="btec-ai-typing" class="hidden px-4 py-2 bg-white flex items-center gap-2 text-xs text-[#1e4f8f] border-t border-slate-200">
            <div class="flex space-x-1.5">
                <span class="w-2 h-2 bg-[#1e4f8f] rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-[#1e4f8f] rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 bg-[#1e4f8f] rounded-full animate-bounce [animation-delay:0.4s]"></span>
            </div>
            <span class="text-slate-500">Đang suy nghĩ...</span>
        </div>

        {{-- Input Form --}}
        <div class="p-3.5 bg-white border-t border-slate-200">
            <form id="btec-ai-form" class="flex items-center gap-2.5">
                <textarea 
                    id="btec-ai-input" 
                    rows="1" 
                    placeholder="Hỏi quy chế hoặc chức năng hệ thống..." 
                    class="flex-1 resize-none bg-slate-50 text-slate-900 placeholder-slate-400 text-xs sm:text-sm rounded-md px-4 py-2.5 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1e4f8f] focus:bg-white transition"></textarea>
                <button 
                    type="submit" 
                    id="btec-ai-send-btn"
                    class="bg-[#1e4f8f] hover:bg-[#163c6d] text-white font-bold p-2.5 rounded-md shadow-sm transition transform disabled:opacity-50 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path>
                    </svg>
                </button>
            </form>
            <div class="flex items-center justify-between text-[10px] text-slate-500 mt-2 px-1">
                <span>Nhấn Enter để gửi (Shift+Enter xuống dòng)</span>
                <button type="button" id="btec-open-ticket-dialog" class="text-orange-600 hover:text-orange-700 font-bold transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span>Gửi Ticket cho Đào tạo / CTSV</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 3. TICKET CREATION MODAL OVERLAY --}}
    <div id="btec-ticket-modal" class="hidden fixed inset-0 z-60 bg-slate-50 flex items-center justify-center p-4">
        <div class="bg-slate-50 text-slate-900 rounded-lg max-w-md w-full p-6 shadow-sm border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h4 class="font-semibold text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span>Tiếp nhận Chuyển tiếp Hồ sơ Học vụ</span>
                </h4>
                <button type="button" id="btec-ticket-close" class="text-slate-500 hover:text-[#163c6d] text-lg">✕</button>
            </div>
            @php
                $ticketUser = auth()->user();
                $ticketClasses = ($ticketUser && $ticketUser->isStudent())
                    ? $ticketUser->enrolledClasses()->with('subject')->orderBy('class_name')->get()
                    : collect();
            @endphp
            <form id="btec-ticket-form" class="space-y-3.5">
                @if ($ticketUser)
                    <div class="rounded-md border border-slate-200 bg-slate-100/90 p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Thông tin người gửi (Đã khóa xác thực)
                            </span>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">Không thể sửa</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-0.5">ID người gửi</label>
                                <input type="text" id="ticket-sender-id" value="#{{ $ticketUser->user_id }}" readonly disabled
                                       class="w-full text-xs font-mono font-bold px-2.5 py-2 rounded-md bg-slate-200/80 border border-slate-300 text-slate-700 cursor-not-allowed select-none">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-500 mb-0.5">Họ tên người gửi</label>
                                <input type="text" id="ticket-sender-name" value="{{ $ticketUser->full_name }}" readonly disabled
                                       class="w-full text-xs font-semibold px-2.5 py-2 rounded-md bg-slate-200/80 border border-slate-300 text-slate-700 cursor-not-allowed select-none">
                            </div>
                        </div>
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Loại yêu cầu</label>
                    <select id="ticket-category" class="w-full text-xs p-2.5 rounded-md bg-white border border-slate-200 text-slate-900 focus:ring-2 focus:ring-orange-500">
                        <option value="Khiếu nại điểm danh / Vắng mặt">Khiếu nại điểm danh / Vắng mặt</option>
                        <option value="Xin khôi phục điểm danh (đến muộn - tối đa 1 lần/môn)">Xin khôi phục điểm danh (đến muộn - tối đa 1 lần/môn)</option>
                        <option value="Phúc khảo điểm Assignment / Thi cử">Phúc khảo điểm Assignment / Thi cử</option>
                        <option value="Thắc mắc học phí & gia hạn nộp">Thắc mắc học phí & gia hạn nộp</option>
                        <option value="Thủ tục bảo lưu / Xin thôi học">Thủ tục bảo lưu / Xin thôi học</option>
                        <option value="Báo cáo sự cố ứng dụng LMS">Báo cáo sự cố ứng dụng LMS</option>
                        <option value="Hỗ trợ học vụ khác">Hỗ trợ học vụ khác</option>
                    </select>
                </div>
                @if ($ticketClasses->isNotEmpty())
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Lớp / Môn học liên quan (nếu có)</label>
                        <select id="ticket-class-id" class="w-full text-xs p-2.5 rounded-md bg-white border border-slate-200 text-slate-900 focus:ring-2 focus:ring-orange-500">
                            <option value="">— Không chọn lớp cụ thể —</option>
                            @foreach ($ticketClasses as $tc)
                                <option value="{{ $tc->class_id }}">{{ $tc->class_name }} — {{ $tc->subject->subject_name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Mô tả chi tiết vấn đề</label>
                    <textarea id="ticket-desc" rows="3" required placeholder="Ghi rõ môn học, mã lớp, ngày học và tình huống bạn gặp phải..." class="w-full text-xs p-2.5 rounded-md bg-white border border-slate-200 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="flex justify-end gap-2.5 pt-2">
                    <button type="button" id="btec-ticket-cancel" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-[#163c6d] rounded-md">Hủy</button>
                    <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-400 rounded-md shadow-sm transition">Gửi Ticket ngay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const container = document.getElementById('btec-ai-widget-container');
    const triggerContainer = document.getElementById('btec-ai-trigger');
    const openBtn = document.getElementById('btec-ai-open-btn');
    const dismissBtn = document.getElementById('btec-ai-dismiss-btn');
    const closeBtn = document.getElementById('btec-ai-close-btn');
    const clearBtn = document.getElementById('btec-ai-clear-btn');
    const chatWindow = document.getElementById('btec-ai-window');
    const form = document.getElementById('btec-ai-form');
    const input = document.getElementById('btec-ai-input');
    const messagesBox = document.getElementById('btec-ai-messages');
    const typingIndicator = document.getElementById('btec-ai-typing');
    const chips = document.querySelectorAll('.btec-chip');
    
    // Ticket elements
    const ticketModal = document.getElementById('btec-ticket-modal');
    const openTicketBtn = document.getElementById('btec-open-ticket-dialog');
    const closeTicketBtn = document.getElementById('btec-ticket-close');
    const cancelTicketBtn = document.getElementById('btec-ticket-cancel');
    const ticketForm = document.getElementById('btec-ticket-form');

    let currentConversationId = null;

    // 1. TÍNH NĂNG TẮT BONG BÓNG (DISMISS)
    dismissBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (confirm('Bạn có muốn tạm ẩn trợ lý BTEC AI? (Tải lại trang F5 để mở lại bất kỳ lúc nào)')) {
            container.style.display = 'none';
        }
    });

    // 2. TÍNH NĂNG KÉO THẢ TỰ DO (DRAG AND DROP ANYWHERE)
    let isDragging = false;
    let hasMoved = false;
    let startX, startY;
    let initialLeft, initialTop;

    triggerContainer.addEventListener('mousedown', startDrag);
    triggerContainer.addEventListener('touchstart', startDrag, { passive: false });

    function startDrag(e) {
        // Không bắt sự kiện drag nếu bấm vào nút [X] đóng
        if (e.target.closest('#btec-ai-dismiss-btn')) return;

        isDragging = true;
        hasMoved = false;

        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0].clientY);

        startX = clientX;
        startY = clientY;

        const rect = container.getBoundingClientRect();
        initialLeft = rect.left;
        initialTop = rect.top;

        // Chuyển container từ bottom/right sang top/left cố định để di chuyển tự do
        container.style.bottom = 'auto';
        container.style.right = 'auto';
        container.style.left = initialLeft + 'px';
        container.style.top = initialTop + 'px';

        document.addEventListener('mousemove', onDrag);
        document.addEventListener('touchmove', onDrag, { passive: false });
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchend', stopDrag);
    }

    function onDrag(e) {
        if (!isDragging) return;
        if (e.cancelable) e.preventDefault();

        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0].clientY);

        const deltaX = clientX - startX;
        const deltaY = clientY - startY;

        // Nếu di chuyển hơn 5px thì đánh dấu là đang kéo (không phải click mở modal)
        if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) {
            hasMoved = true;
        }

        let newLeft = initialLeft + deltaX;
        let newTop = initialTop + deltaY;

        // Giới hạn trong khung hình màn hình (Viewport Boundary)
        const maxX = window.innerWidth - container.offsetWidth - 10;
        const maxY = window.innerHeight - container.offsetHeight - 10;

        newLeft = Math.max(10, Math.min(newLeft, maxX));
        newTop = Math.max(10, Math.min(newTop, maxY));

        container.style.left = newLeft + 'px';
        container.style.top = newTop + 'px';
    }

    function stopDrag(e) {
        isDragging = false;
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('touchmove', onDrag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchend', stopDrag);
    }

    // Toggle Chat Window
    function toggleChat(open) {
        if (open) {
            chatWindow.classList.remove('hidden');
            triggerContainer.classList.add('hidden');
            input.focus();
            scrollToBottom();
            initSession();
        } else {
            chatWindow.classList.add('hidden');
            triggerContainer.classList.remove('hidden');
        }
    }

    openBtn.addEventListener('click', function(e) {
        // Nếu vừa kéo chuột di chuyển thì không mở chat
        if (hasMoved) {
            hasMoved = false;
            return;
        }
        toggleChat(true);
    });

    closeBtn.addEventListener('click', () => toggleChat(false));

    const expandBtn = document.getElementById('btec-ai-expand-btn');
    let isWindowExpanded = false;
    if (expandBtn) {
        expandBtn.addEventListener('click', function () {
            isWindowExpanded = !isWindowExpanded;
            if (isWindowExpanded) {
                chatWindow.classList.remove('sm:w-[380px]', 'h-[520px]');
                chatWindow.classList.add('sm:w-[500px]', 'h-[690px]');
            } else {
                chatWindow.classList.remove('sm:w-[500px]', 'h-[690px]');
                chatWindow.classList.add('sm:w-[380px]', 'h-[520px]');
            }
        });
    }

    // Auto resize input
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });

    // Quick chips click
    chips.forEach(btn => {
        btn.addEventListener('click', function() {
            input.value = this.innerText.replace(/^[^\w\s\u00C0-\u1EF9]+/, '').trim();
            form.dispatchEvent(new Event('submit'));
        });
    });

    // Init Session
    async function initSession() {
        if (currentConversationId) return;
        try {
            const res = await fetch('{{ route("student.btec-ai.init") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            if (data.status === 'ok' || data.success) {
                currentConversationId = data.conversation_id;
                if (data.messages && data.messages.length > 0) {
                    renderHistory(data.messages);
                }
            }
        } catch (e) {
            console.error('Lỗi khởi tạo BTEC AI:', e);
        }
    }

    // Clear Conversation
    clearBtn.addEventListener('click', async () => {
        if (confirm('Bạn có muốn bắt đầu một phiên hỏi đáp mới không?')) {
            currentConversationId = null;
            messagesBox.innerHTML = `
                <div class="flex items-start gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-white border border-zinc-200 overflow-hidden flex items-center justify-center shrink-0 mt-0.5"><img src="{{ asset('images/btec_support_logo.png') }}" alt="Trợ lý Học vụ AI" class="w-full h-full object-cover"></div>
                    <div class="max-w-[88%] bg-zinc-50 p-3.5 rounded-lg rounded-tl-none border border-zinc-200 text-zinc-700 space-y-1">
                        <p class="font-semibold text-zinc-950">Đã tạo phiên hỗ trợ mới</p>
                        <p class="text-xs text-zinc-600 leading-relaxed">Mời bạn đặt câu hỏi về lịch học, điểm số, chuyên cần hoặc quy chế.</p>
                    </div>
                </div>
            `;
        }
    });

    function scrollToBottom() {
        setTimeout(() => {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }, 50);
    }

    function scrollToReplyTop(el) {
        if (!el) return;
        setTimeout(() => {
            const targetTop = Math.max(0, el.offsetTop - messagesBox.offsetTop - 12);
            messagesBox.scrollTop = targetTop;
        }, 40);
    }

    // Submit Chat - ULTRA FAST SSE STREAMING & INSTANT PREVIEW
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        // 1. Render User Message ngay lập tức (lưu lại tham chiếu để làm mờ tức thì nếu vi phạm ngôn từ)
        const userBubbleEl = appendMessage('user', text);
        input.value = '';
        input.style.height = 'auto';

        // 2. Tạo khung tin nhắn AI ngay trong 10ms (Không để người dùng chờ)
        const bubbleId = 'btec-stream-' + Date.now();
        const aiWrap = document.createElement('div');
        aiWrap.className = 'flex items-start gap-2.5';
        aiWrap.innerHTML = `
            <div class="w-7 h-7 rounded-full bg-white border border-zinc-200 overflow-hidden flex items-center justify-center shrink-0 mt-0.5"><img src="{{ asset('images/btec_support_logo.png') }}" alt="Trợ lý Học vụ AI" class="w-full h-full object-cover"></div>
            <div class="max-w-[88%] bg-zinc-50 p-3.5 rounded-lg rounded-tl-none border border-zinc-200 text-zinc-800 text-sm leading-relaxed space-y-1.5">
                <div class="btec-ai-content" id="${bubbleId}"><span class="inline-flex items-center gap-2 text-xs text-slate-500 italic"><span class="inline-flex gap-1"><span class="w-1.5 h-1.5 bg-[#1e4f8f] rounded-full animate-bounce"></span><span class="w-1.5 h-1.5 bg-[#1e4f8f] rounded-full animate-bounce [animation-delay:0.15s]"></span><span class="w-1.5 h-1.5 bg-[#1e4f8f] rounded-full animate-bounce [animation-delay:0.3s]"></span></span>Đang suy nghĩ...</span></div>
                <div id="${bubbleId}-meta" class="hidden"></div>
            </div>
        `;
        messagesBox.appendChild(aiWrap);
        scrollToReplyTop(aiWrap);

        const contentEl = document.getElementById(bubbleId);
        const metaEl = document.getElementById(bubbleId + '-meta');

        let fullText = '';
        let streamSuccess = false;
        const streamUrl = '{{ route("student.btec-ai.stream") }}';
        const fallbackUrl = '{{ route("student.btec-ai.chat") }}';

        try {
            const res = await fetch(streamUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: currentConversationId
                })
            });

            if (res.ok && res.body) {
                streamSuccess = true;
                const reader = res.body.getReader();
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
                                    contentEl.innerHTML = formatMarkdown(fullText) + '<span class="inline-block w-1.5 h-3.5 bg-amber-600 ml-0.5 align-middle"></span>';
                                }
                                if (json.done) {
                                    currentConversationId = json.conversation_id;
                                    if (json.masked_user_message && userBubbleEl) {
                                        userBubbleEl.innerHTML = renderUserText(json.masked_user_message);
                                    }
                                    contentEl.innerHTML = formatMarkdown(fullText);
                                    if (window.renderMermaidDiagrams) window.renderMermaidDiagrams(contentEl);

                                    let metaHtml = '';
                                    if (json.sources && json.sources.length > 0) {
                                        metaHtml += `
                                            <div class="mt-2.5 pt-2 border-t border-slate-200">
                                                <div class="text-[9px] font-bold uppercase tracking-wider text-orange-600 mb-1 flex items-center gap-1">
                                                    <span>Nguồn quy định tham chiếu:</span>
                                                </div>
                                                <div class="space-y-1">
                                                    ${json.sources.map(s => `
                                                        <div class="text-[11px] bg-slate-50 p-2 rounded-md border border-slate-200 text-slate-600">
                                                            <span class="font-semibold text-orange-600">[${s.source_id}]</span> ${escapeHtml(s.title || s.name || '')}
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            </div>
                                        `;
                                    }
                                    if (json.app_actions && json.app_actions.length > 0) {
                                        metaHtml += `
                                            <div class="mt-2.5 pt-2 border-t border-slate-200 flex flex-wrap gap-1.5">
                                                ${json.app_actions.map(act => `
                                                    <a href="${act.url}" class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 hover:text-[#163c6d] px-3 py-1.5 rounded-md border border-blue-200 transition shadow-xs">
                                                        <span>${escapeHtml(act.label)}</span>
                                                    </a>
                                                `).join('')}
                                            </div>
                                        `;
                                    }
                                    if (json.suggest_ticket) {
                                        metaHtml += `
                                            <div class="mt-2.5 p-2.5 bg-amber-50 rounded-md border border-amber-200 flex items-center justify-between text-xs">
                                                <span class="text-amber-700 text-[11px]">Cần cán bộ kiểm tra thủ công?</span>
                                                <button type="button" onclick="document.getElementById('btec-ticket-modal').classList.remove('hidden')" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-400 text-white font-bold rounded-lg text-[10px] shadow-sm">
                                                    Tạo Ticket
                                                </button>
                                            </div>
                                        `;
                                    }
                                    if (metaHtml) {
                                        metaEl.innerHTML = metaHtml;
                                        metaEl.classList.remove('hidden');
                                    }
                                    scrollToReplyTop(aiWrap);
                                }
                            } catch (e) {}
                        }
                    }
                }
            }
        } catch (streamErr) {
            console.warn('BTEC AI Streaming error, falling back:', streamErr);
        }

        // Fallback an toàn nếu stream bị gián đoạn
        if (!streamSuccess || !fullText) {
            try {
                const res = await fetch(fallbackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: text,
                        conversation_id: currentConversationId
                    })
                });
                const data = await res.json();
                if (data.status === 'ok' || data.success) {
                    currentConversationId = data.conversation_id;
                    if (data.masked_user_message && userBubbleEl) {
                        userBubbleEl.innerHTML = renderUserText(data.masked_user_message);
                    }
                    contentEl.innerHTML = formatMarkdown(data.answer || data.message);
                } else {
                    contentEl.innerHTML = '⚠️ ' + escapeHtml(data.message || 'Hệ thống đang bận, vui lòng thử lại sau giây lát.');
                }
            } catch (fallbackErr) {
                contentEl.innerHTML = '<span class="text-rose-600 font-semibold">Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!</span>';
            }
        }
    });

    // Render Append Message
    function appendMessage(role, content, sources = [], actions = [], confidence = null, suggestTicket = false, animate = false) {
        const wrap = document.createElement('div');
        wrap.className = 'flex items-start gap-2.5' + (role === 'user' ? ' justify-end' : '');

        if (role === 'user') {
            wrap.innerHTML = `
                <div class="btec-user-bubble max-w-[85%] bg-zinc-900 text-white px-3.5 py-2.5 rounded-lg rounded-tr-none text-sm leading-relaxed">
                    ${renderUserText(content)}
                </div>
                <div class="w-7 h-7 rounded-md bg-zinc-100 text-zinc-600 border border-zinc-200 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
            `;
            messagesBox.appendChild(wrap);
            scrollToBottom();
            return wrap.querySelector('.btec-user-bubble');
        } else {
            let sourcesHtml = '';
            if (sources && sources.length > 0) {
                sourcesHtml = `
                    <div class="mt-2.5 pt-2 border-t border-slate-200">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-orange-600 mb-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span>Nguồn quy định tham chiếu:</span>
                        </div>
                        <div class="space-y-1">
                            ${sources.map(s => `
                                <div class="text-[11px] bg-slate-50 p-2 rounded-md border border-slate-200 text-slate-600">
                                    <span class="font-semibold text-orange-600">[${s.source_id}]</span> ${escapeHtml(s.title || s.name || '')}
                                    ${s.effective_date ? `<span class="text-slate-500 text-[10px]">(${s.effective_date})</span>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            let actionsHtml = '';
            if (actions && actions.length > 0) {
                actionsHtml = `
                    <div class="mt-2.5 pt-2 border-t border-slate-200 flex flex-wrap gap-1.5">
                        ${actions.map(act => `
                            <a href="${act.url}" class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 hover:text-[#163c6d] px-3 py-1.5 rounded-md border border-blue-200 transition shadow-xs">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>${escapeHtml(act.label)}</span>
                            </a>
                        `).join('')}
                    </div>
                `;
            }

            let ticketSuggestionHtml = '';
            if (suggestTicket) {
                ticketSuggestionHtml = `
                    <div class="mt-2.5 p-2.5 bg-amber-50 rounded-md border border-amber-200 flex items-center justify-between text-xs">
                        <span class="text-amber-700 text-[11px]">Cần cán bộ kiểm tra thủ công?</span>
                        <button type="button" onclick="document.getElementById('btec-ticket-modal').classList.remove('hidden')" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-400 text-white font-bold rounded-lg text-[10px] shadow-sm">
                            Tạo Ticket
                        </button>
                    </div>
                `;
            }

            const contentId = 'bubble-msg-' + Date.now();
            wrap.innerHTML = `
                <div class="w-7 h-7 rounded-full bg-white border border-zinc-200 overflow-hidden flex items-center justify-center shrink-0 mt-0.5"><img src="{{ asset('images/btec_support_logo.png') }}" alt="Trợ lý Học vụ AI" class="w-full h-full object-cover"></div>
                <div class="max-w-[88%] bg-zinc-50 p-3.5 rounded-lg rounded-tl-none border border-zinc-200 text-zinc-800 text-sm leading-relaxed space-y-1.5">
                    <div class="btec-ai-content" id="${contentId}">${animate ? '<span class="inline-block w-1.5 h-3.5 bg-zinc-900 ml-0.5 align-middle"></span>' : formatMarkdown(content)}</div>
                    <div id="${contentId}-meta" class="${animate ? 'hidden' : ''}">
                        ${sourcesHtml}
                        ${actionsHtml}
                        ${ticketSuggestionHtml}
                    </div>
                </div>
            `;
            if (animate && content) {
                setTimeout(() => {
                    const el = document.getElementById(contentId);
                    const metaEl = document.getElementById(contentId + '-meta');
                    let i = 0;
                    const stepSize = Math.max(2, Math.floor(content.length / 45));
                    function typeNext() {
                        if (i < content.length) {
                            i += stepSize;
                            const slice = content.substr(0, i);
                            if (el) el.innerHTML = formatMarkdown(slice) + '<span class="inline-block w-1.5 h-3.5 bg-amber-600 ml-0.5 align-middle"></span>';
                            setTimeout(typeNext, 12);
                        } else {
                            if (el) {
                                el.innerHTML = formatMarkdown(content);
                                if (window.renderMermaidDiagrams) window.renderMermaidDiagrams(el);
                            }
                            if (metaEl) metaEl.classList.remove('hidden');
                        }
                    }
                    typeNext();
                }, 50);
            }
        }

        messagesBox.appendChild(wrap);
        if (!animate && window.renderMermaidDiagrams) {
            window.renderMermaidDiagrams(wrap);
        }
        if (role === 'assistant') {
            scrollToReplyTop(wrap);
        } else {
            scrollToBottom();
        }
    }

    function renderHistory(msgs) {
        msgs.forEach(m => {
            appendMessage(m.role, m.message, m.citations, m.actions, null, false);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderUserText(text) {
        return escapeHtml(text || '')
            .replace(/\[\[BLUR\]\]([\s\S]*?)\[\[\/BLUR\]\]/g, '<span title="Nội dung đã bị làm mờ do ngôn từ không phù hợp" style="filter: blur(5px); user-select: none; -webkit-user-select: none; pointer-events: none; display: inline-block; padding: 0 3px;">$1</span>')
            .replace(/\n/g, '<br>');
    }

    function formatMarkdown(text) {
        if (!text) return '';
        const blocks = [];
        let work = text.replace(/```([a-zA-Z0-9_-]*)\r?\n([\s\S]*?)```/g, function(m, lang, code) {
            const key = `___BTEC_BLK_${blocks.length}___`;
            const normLang = (lang || '').trim().toLowerCase();
            if (normLang === 'mermaid' && typeof window.buildMermaidCardHtml === 'function') {
                blocks.push(window.buildMermaidCardHtml(code.trim()));
            } else if (typeof window.buildVsCodeBlockHtml === 'function') {
                blocks.push(window.buildVsCodeBlockHtml(normLang || 'code', code.trim()));
            } else {
                blocks.push(`<pre class="my-2 bg-slate-900 text-slate-100 p-2.5 rounded text-[11px] overflow-x-auto"><code>${escapeHtml(code.trim())}</code></pre>`);
            }
            return key;
        });

        work = work.replace(/```mermaid\r?\n[\s\S]*$/i, function() {
            const key = `___BTEC_BLK_${blocks.length}___`;
            blocks.push('<div class="my-2 rounded border border-blue-200 bg-blue-50/70 px-2.5 py-2 text-[11px] font-semibold text-[#1e4f8f]">Đang vẽ sơ đồ quy trình...</div>');
            return key;
        });

        let formatted = escapeHtml(work)
            .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<span class="inline-block rounded bg-slate-100 border border-slate-200 px-1.5 py-0.5 font-mono text-[12px] font-semibold text-slate-800">$1</span>')
            .replace(/(Lưu ý quan trọng\s*:|Lưu ý\s*:|Chú ý\s*:|Quan trọng\s*:|Cảnh báo\s*:)/gi, '<strong class="font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded">$1</strong>')
            .replace(/\n\n/g, '<div class="h-2.5"></div>')
            .replace(/\n/g, '<br>');

        blocks.forEach((html, idx) => {
            formatted = formatted.replace(`___BTEC_BLK_${idx}___`, html);
        });
        return formatted;
    }

    // Ticket Dialog Actions
    openTicketBtn.addEventListener('click', () => ticketModal.classList.remove('hidden'));
    closeTicketBtn.addEventListener('click', () => ticketModal.classList.add('hidden'));
    cancelTicketBtn.addEventListener('click', () => ticketModal.classList.add('hidden'));

    ticketForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const cat = document.getElementById('ticket-category').value;
        const classSelect = document.getElementById('ticket-class-id');
        const classId = classSelect && classSelect.value ? parseInt(classSelect.value, 10) : null;
        const desc = document.getElementById('ticket-desc').value.trim();
        if (!desc) return;

        try {
            const res = await fetch('{{ route("student.btec-ai.ticket") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    topic: cat,
                    class_id: classId,
                    description: desc
                })
            });
            const d = await res.json();
            if (d.success || d.status === 'ok') {
                ticketModal.classList.add('hidden');
                document.getElementById('ticket-desc').value = '';
                if (classSelect) classSelect.value = '';
                const code = d.ticket_code || (d.ticket ? d.ticket.ticket_code : 'ĐÃ GỬI');
                appendMessage('assistant', `✓ Đã tiếp nhận **Ticket hỗ trợ #${code}**! Quản trị viên đã nhận thông báo và sẽ xử lý hoặc chuyển tiếp cho Giảng viên phụ trách. Bạn có thể theo dõi kết quả tại mục **Phiếu hỗ trợ**.`);
            } else {
                alert(d.message || 'Không thể tạo ticket');
            }
        } catch (err) {
            alert('Không thể kết nối với SV, vui lòng kiểm tra lại mạng.');
        }
    });

})();
</script>
