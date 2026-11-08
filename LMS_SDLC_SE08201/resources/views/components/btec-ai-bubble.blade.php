{{-- BTEC FPT AI Student Support - Floating Bubble Widget (Draggable & Dismissible Edition) --}}
<div id="btec-ai-widget-container" class="fixed bottom-4 right-4 z-50 font-sans select-none touch-none">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- 1. FLOATING BUBBLE BUTTON (COMPACT BY DEFAULT, EXPANDS ON HOVER / OPEN) --}}
    <div id="btec-ai-trigger" class="relative group cursor-grab active:cursor-grabbing">
        {{-- High-tech Multi-layer Glow (Subtle & Compact) --}}
        <div class="absolute -inset-1 bg-gradient-to-r from-[#1558C9] via-[#D4FF24] to-[#1558C9] rounded-full blur opacity-50 group-hover:opacity-90 transition duration-300"></div>
        
        {{-- Nút [✕] Tắt hoàn toàn bong bóng nổi khi bị vướng --}}
        <button type="button" 
                id="btec-ai-dismiss-btn" 
                title="Tắt trợ lý AI (không hiển thị nữa)" 
                class="absolute -top-1.5 -left-1.5 z-30 w-4 h-4 rounded-full bg-[#030407] border border-white/25 hover:border-rose-500 text-slate-400 hover:text-white hover:bg-rose-600 flex items-center justify-center text-[9px] font-black transition-all shadow-md opacity-0 group-hover:opacity-100">
            ✕
        </button>

        {{-- Nút bấm chính: Mặc định thu nhỏ gọn thành nút tròn 44x44px, rê chuột vào mới trượt nhẹ nhãn nhỏ --}}
        <div id="btec-ai-open-btn" 
             class="relative flex items-center bg-[#030407]/95 hover:bg-[#030407] text-white p-1.5 rounded-full shadow-xl backdrop-blur-xl border border-[#D4FF24]/40 transition-all duration-300 transform hover:scale-105 active:scale-95">

            {{-- Avatar Mascot BTEC - Compact Cute Robot SVG --}}
            <div class="relative flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-tr from-[#1558C9] via-[#2563eb] to-[#D4FF24] p-0.5 shadow-md shadow-[#1558C9]/40 shrink-0 pointer-events-none">
                <div class="w-full h-full rounded-full bg-[#030407]/90 backdrop-blur-sm overflow-hidden flex items-center justify-center border border-white/15">
                    {{-- Cute Robot AI Avatar SVG --}}
                    <svg viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7">
                        <!-- Antenna -->
                        <line x1="22" y1="4" x2="22" y2="10" stroke="#D4FF24" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="22" cy="3.5" r="2" fill="#D4FF24" opacity="0.95"/>
                        <!-- Robot head -->
                        <rect x="10" y="10" width="24" height="20" rx="5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.8" opacity="0.95"/>
                        <!-- Eyes - glowing cute -->
                        <rect x="13.5" y="15" width="6" height="5" rx="2.5" fill="#D4FF24" opacity="0.95"/>
                        <rect x="24.5" y="15" width="6" height="5" rx="2.5" fill="#D4FF24" opacity="0.95"/>
                        <circle cx="16.5" cy="17.5" r="1.5" fill="white"/>
                        <circle cx="27.5" cy="17.5" r="1.5" fill="white"/>
                        <circle cx="17.2" cy="16.9" r="0.5" fill="#030407"/>
                        <circle cx="28.2" cy="16.9" r="0.5" fill="#030407"/>
                        <!-- Mouth - cute smile display -->
                        <rect x="15" y="22.5" width="14" height="4" rx="2" fill="#030407/80" stroke="#D4FF24" stroke-width="0.6" opacity="0.6"/>
                        <path d="M16.5 24.5 L18.5 24.5 M20 24.5 L22 24.5 M23.5 24.5 L25.5 24.5 M27 24.5 L27 24.5" stroke="#D4FF24" stroke-width="1" stroke-linecap="round" opacity="0.9"/>
                        <!-- Ears/sides -->
                        <rect x="7" y="15" width="3" height="7" rx="1.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.5"/>
                        <rect x="34" y="15" width="3" height="7" rx="1.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.5"/>
                        <!-- Body -->
                        <rect x="14" y="30" width="16" height="10" rx="3" fill="#1558C9" stroke="#D4FF24" stroke-width="0.6" opacity="0.85"/>
                        <!-- Heart/power icon on body -->
                        <path d="M20 34.5 Q22 32 24 34.5 Q22 37 20 34.5Z" fill="#D4FF24" opacity="0.8"/>
                        <!-- Star sparkle top right -->
                        <path d="M38 7 L38.5 8.8 L40.3 8.8 L38.9 9.8 L39.4 11.5 L38 10.5 L36.6 11.5 L37.1 9.8 L35.7 8.8 L37.5 8.8 Z" fill="#D4FF24" opacity="0.8"/>
                    </svg>
                </div>

                {{-- Status Online Dot --}}
                <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5 z-20">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#D4FF24] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#D4FF24] border border-[#030407]"></span>
                </span>
            </div>

            {{-- Nhãn chữ thu ẩn hoàn toàn khi bình thường, chỉ trượt nhẹ ra khi di chuột vào --}}
            <div class="text-left max-w-0 opacity-0 overflow-hidden group-hover:max-w-[165px] group-hover:opacity-100 group-hover:pl-2 group-hover:pr-2.5 transition-all duration-300 whitespace-nowrap pointer-events-none">
                <div class="text-[9px] uppercase tracking-wider text-[#D4FF24] font-black leading-tight">
                    BTEC AI · 24/7
                </div>
                <div class="text-xs font-extrabold text-white leading-tight flex items-center gap-1">
                    <span>Trợ lý Học vụ</span>
                    <svg class="w-3 h-3 text-[#D4FF24] fill-current shrink-0" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. CHAT WINDOW / MODAL (MỞ RA MỚI HIỂN THỊ ĐẦY ĐỦ + CÓ NÚT PHÓNG TO / THU NHỎ) --}}
    <div id="btec-ai-window" class="hidden fixed bottom-4 right-4 sm:right-5 w-[92vw] sm:w-[380px] max-w-[94vw] h-[520px] max-h-[85vh] bg-[#030407]/95 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/15 flex flex-col overflow-hidden transition-all duration-300 z-50 ring-1 ring-[#D4FF24]/20">
        
        {{-- Header --}}
        <div class="relative bg-gradient-to-r from-[#030407] via-[#081226] to-[#030407] text-white p-4.5 flex items-center justify-between border-b border-white/10 overflow-hidden cursor-move" id="btec-window-header">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#1558C9]/30 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-[#D4FF24]/15 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#1558C9] via-[#2563eb] to-[#D4FF24] p-0.5 shadow-lg shadow-[#1558C9]/40 shrink-0">
                    <div class="w-full h-full rounded-[14px] bg-[#030407]/90 overflow-hidden flex items-center justify-center border border-white/20">
                        {{-- Chat Header Robot Avatar --}}
                        <svg viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10">
                            <line x1="22" y1="4" x2="22" y2="10" stroke="#D4FF24" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="22" cy="3.5" r="2" fill="#D4FF24" opacity="0.95"/>
                            <rect x="10" y="10" width="24" height="20" rx="5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.8" opacity="0.95"/>
                            <rect x="13.5" y="15" width="6" height="5" rx="2.5" fill="#D4FF24" opacity="0.95"/>
                            <rect x="24.5" y="15" width="6" height="5" rx="2.5" fill="#D4FF24" opacity="0.95"/>
                            <circle cx="16.5" cy="17.5" r="1.5" fill="white"/>
                            <circle cx="27.5" cy="17.5" r="1.5" fill="white"/>
                            <circle cx="17.2" cy="16.9" r="0.5" fill="#030407"/>
                            <circle cx="28.2" cy="16.9" r="0.5" fill="#030407"/>
                            <rect x="15" y="22.5" width="14" height="4" rx="2" fill="#030407" fill-opacity="0.8" stroke="#D4FF24" stroke-width="0.6" opacity="0.6"/>
                            <path d="M16.5 24.5 L18.5 24.5 M20 24.5 L22 24.5 M23.5 24.5 L25.5 24.5" stroke="#D4FF24" stroke-width="1" stroke-linecap="round" opacity="0.9"/>
                            <rect x="7" y="15" width="3" height="7" rx="1.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.5"/>
                            <rect x="34" y="15" width="3" height="7" rx="1.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.5"/>
                            <rect x="14" y="30" width="16" height="10" rx="3" fill="#1558C9" stroke="#D4FF24" stroke-width="0.6" opacity="0.85"/>
                            <path d="M20 34.5 Q22 32 24 34.5 Q22 37 20 34.5Z" fill="#D4FF24" opacity="0.8"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-black text-sm sm:text-base tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-[#D4FF24]">BTEC AI Student Support</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-[#D4FF24]/20 border border-[#D4FF24]/40 text-[#D4FF24]">Active</span>
                    </div>
                    <p class="text-xs text-slate-400">Quy chế BTEC FPT & Định vị tính năng LMS</p>
                </div>
            </div>
            <div class="relative z-10 flex items-center gap-1 text-slate-400">
                <button type="button" id="btec-ai-expand-btn" title="Phóng to / Thu gọn cửa sổ" class="p-2 hover:text-white hover:bg-white/10 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                </button>
                <button type="button" id="btec-ai-clear-btn" title="Làm mới cuộc trò chuyện" class="p-2 hover:text-white hover:bg-white/10 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                <button type="button" id="btec-ai-close-btn" title="Thu nhỏ" class="p-2 hover:text-white hover:bg-white/10 rounded-xl transition text-base font-bold">
                    ✕
                </button>
            </div>
        </div>

        {{-- Notice Banner --}}
        <div class="bg-[#1558C9]/20 border-b border-[#1558C9]/30 px-3.5 py-1.5 flex items-center gap-2 text-[11px] text-[#D4FF24]">
            <svg class="w-4 h-4 text-[#D4FF24] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Đối soát trực tiếp từ Sổ tay sinh viên & Khung đào tạo BTEC FPT.</span>
        </div>

        {{-- Messages Container --}}
        <div id="btec-ai-messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-[#030407]/60 text-sm">
            {{-- Welcome Bot Message --}}
            <div class="flex items-start gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#1558C9] to-[#D4FF24] p-0.5 shrink-0 shadow-md">
                    <div class="w-full h-full rounded-[10px] bg-[#030407] overflow-hidden flex items-center justify-center">
                        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-9 h-9">
                            <line x1="18" y1="3" x2="18" y2="8" stroke="#D4FF24" stroke-width="1.3" stroke-linecap="round"/>
                            <circle cx="18" cy="2.5" r="1.6" fill="#D4FF24"/>
                            <rect x="8" y="8" width="20" height="17" rx="4" fill="#1558C9" stroke="#D4FF24" stroke-width="0.7"/>
                            <rect x="11" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24" opacity="0.95"/>
                            <rect x="20.5" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24" opacity="0.95"/>
                            <circle cx="13.3" cy="14.5" r="1.2" fill="white"/>
                            <circle cx="22.8" cy="14.5" r="1.2" fill="white"/>
                            <path d="M12.5 19.5 Q18 22 23.5 19.5" stroke="#D4FF24" stroke-width="0.8" stroke-linecap="round" fill="none" opacity="0.9"/>
                            <rect x="5.5" y="12" width="2.5" height="6" rx="1.2" fill="#1558C9" stroke="#D4FF24" stroke-width="0.4"/>
                            <rect x="28" y="12" width="2.5" height="6" rx="1.2" fill="#1558C9" stroke="#D4FF24" stroke-width="0.4"/>
                            <rect x="11" y="25" width="14" height="8" rx="2.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.6" opacity="0.8"/>
                            <circle cx="18" cy="29" r="2" fill="#D4FF24" opacity="0.8"/>
                        </svg>
                    </div>
                </div>
                <div class="max-w-[85%] bg-slate-900/90 backdrop-blur-md p-4 rounded-2xl rounded-tl-none border border-white/10 shadow-lg text-slate-200 space-y-2">
                    <p class="font-bold text-[#D4FF24] flex items-center gap-1.5">
                        Xin chào {{ auth()->user()->full_name }}!
                    </p>
                    <p class="text-xs leading-relaxed text-slate-300">
                        Tôi là **BTEC FPT AI Student Support Assistant**. Tôi sẵn sàng hỗ trợ bạn 24/7:
                    </p>
                    <ul class="text-xs space-y-1 text-slate-400 list-disc list-inside">
                        <li>Quy chế điểm danh (ngưỡng nghỉ 20% cấm thi).</li>
                        <li>Quy định Assignment, nộp lại (Resubmission), đạo văn.</li>
                        <li>Học phí, gia hạn, thủ tục bảo lưu.</li>
                        <li>Dẫn link trực tiếp đến mọi chức năng trên Smart LMS.</li>
                    </ul>
                </div>
            </div>

            {{-- Quick Starter Chips --}}
            <div id="btec-quick-chips" class="pt-1 pb-2 space-y-2">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 px-1">Gợi ý nhanh:</p>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" class="btec-chip text-left text-xs bg-slate-900/80 hover:bg-[#1558C9]/30 hover:border-[#D4FF24]/50 text-slate-300 hover:text-white px-3 py-1.5 rounded-xl border border-white/10 transition backdrop-blur-sm">
                        Nghỉ bao nhiêu buổi thì bị cấm thi?
                    </button>
                    <button type="button" class="btec-chip text-left text-xs bg-slate-900/80 hover:bg-[#1558C9]/30 hover:border-[#D4FF24]/50 text-slate-300 hover:text-white px-3 py-1.5 rounded-xl border border-white/10 transition backdrop-blur-sm">
                        Quy định nộp trễ Assignment thế nào?
                    </button>
                    <button type="button" class="btec-chip text-left text-xs bg-slate-900/80 hover:bg-[#1558C9]/30 hover:border-[#D4FF24]/50 text-slate-300 hover:text-white px-3 py-1.5 rounded-xl border border-white/10 transition backdrop-blur-sm">
                        Xem lịch học và điểm danh ở đâu trong app?
                    </button>
                    <button type="button" class="btec-chip text-left text-xs bg-slate-900/80 hover:bg-[#1558C9]/30 hover:border-[#D4FF24]/50 text-slate-300 hover:text-white px-3 py-1.5 rounded-xl border border-white/10 transition backdrop-blur-sm">
                        Học phí đóng muộn có bị phạt không?
                    </button>
                </div>
            </div>
        </div>

        {{-- Typing indicator --}}
        <div id="btec-ai-typing" class="hidden px-4 py-2 bg-[#030407] flex items-center gap-2 text-xs text-[#D4FF24] border-t border-white/10">
            <div class="flex space-x-1.5">
                <span class="w-2 h-2 bg-[#D4FF24] rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-[#D4FF24] rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 bg-[#D4FF24] rounded-full animate-bounce [animation-delay:0.4s]"></span>
            </div>
            <span class="text-slate-400">BTEC AI đang tra cứu quy chế & soạn câu trả lời...</span>
        </div>

        {{-- Input Form --}}
        <div class="p-3.5 bg-[#030407] border-t border-white/10">
            <form id="btec-ai-form" class="flex items-center gap-2.5">
                <textarea 
                    id="btec-ai-input" 
                    rows="1" 
                    placeholder="Hỏi quy chế hoặc chức năng hệ thống..." 
                    class="flex-1 resize-none bg-slate-900/90 text-white placeholder-slate-500 text-xs sm:text-sm rounded-xl px-4 py-2.5 border border-white/10 focus:outline-none focus:ring-2 focus:ring-[#D4FF24] focus:bg-[#030407] transition"></textarea>
                <button 
                    type="submit" 
                    id="btec-ai-send-btn"
                    class="bg-[#D4FF24] hover:bg-[#c3ee15] text-[#030407] font-black p-2.5 rounded-xl shadow-lg shadow-[#D4FF24]/20 transition transform hover:scale-105 active:scale-95 disabled:opacity-50 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
            <div class="flex items-center justify-between text-[10px] text-slate-500 mt-2 px-1">
                <span>Nhấn Enter để gửi (Shift+Enter xuống dòng)</span>
                <button type="button" id="btec-open-ticket-dialog" class="text-orange-400 hover:text-orange-300 font-bold transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span>Gửi Ticket cho Đào tạo / CTSV</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 3. TICKET CREATION MODAL OVERLAY --}}
    <div id="btec-ticket-modal" class="hidden fixed inset-0 z-60 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-slate-900 text-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-white/15 space-y-4">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h4 class="font-extrabold text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span>Tiếp nhận Chuyển tiếp Hồ sơ Học vụ</span>
                </h4>
                <button type="button" id="btec-ticket-close" class="text-slate-400 hover:text-white text-lg">✕</button>
            </div>
            <form id="btec-ticket-form" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Loại yêu cầu</label>
                    <select id="ticket-category" class="w-full text-xs p-2.5 rounded-xl bg-slate-800 border border-white/10 text-white focus:ring-2 focus:ring-orange-500">
                        <option value="attendance_dispute">Khiếu nại điểm danh / Vắng mặt</option>
                        <option value="grade_appeal">Phúc khảo điểm Assignment / Thi cử</option>
                        <option value="tuition_issue">Thắc mắc học phí & gia hạn nộp</option>
                        <option value="leave_suspension">Thủ tục bảo lưu / Xin thôi học</option>
                        <option value="app_bug">Báo cáo sự cố ứng dụng LMS</option>
                        <option value="other">Hỗ trợ học vụ khác</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Mô tả chi tiết vấn đề</label>
                    <textarea id="ticket-desc" rows="3" required placeholder="Ghi rõ môn học, mã lớp, ngày học và tình huống bạn gặp phải..." class="w-full text-xs p-2.5 rounded-xl bg-slate-800 border border-white/10 text-white placeholder-slate-500 focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="flex justify-end gap-2.5 pt-2">
                    <button type="button" id="btec-ticket-cancel" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl">Hủy</button>
                    <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-indigo-600 hover:from-orange-400 hover:to-indigo-500 rounded-xl shadow-lg transition">Gửi Ticket ngay</button>
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
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#1558C9] to-[#D4FF24] p-0.5 shrink-0 shadow-md">
                        <div class="w-full h-full rounded-[10px] bg-[#030407] overflow-hidden flex items-center justify-center">
                            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" width="36" height="36">
                                <line x1="18" y1="3" x2="18" y2="8" stroke="#D4FF24" stroke-width="1.3" stroke-linecap="round"/>
                                <circle cx="18" cy="2.5" r="1.6" fill="#D4FF24"/>
                                <rect x="8" y="8" width="20" height="17" rx="4" fill="#1558C9" stroke="#D4FF24" stroke-width="0.7"/>
                                <rect x="11" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24"/>
                                <rect x="20.5" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24"/>
                                <circle cx="13.3" cy="14.5" r="1.2" fill="white"/>
                                <circle cx="22.8" cy="14.5" r="1.2" fill="white"/>
                                <path d="M12.5 19.5 Q18 22 23.5 19.5" stroke="#D4FF24" stroke-width="0.8" stroke-linecap="round" fill="none"/>
                                <rect x="11" y="25" width="14" height="8" rx="2.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.6"/>
                                <circle cx="18" cy="29" r="2" fill="#D4FF24"/>
                            </svg>
                        </div>
                    </div>
                    <div class="max-w-[85%] bg-slate-800/90 backdrop-blur-md p-4 rounded-2xl rounded-tl-none border border-white/10 shadow-lg text-slate-200 space-y-2">
                        <p class="font-bold text-[#D4FF24]">Đã làm mới phiên hỗ trợ!</p>
                        <p class="text-xs text-slate-300 leading-relaxed">Mời bạn đặt câu hỏi về quy chế BTEC FPT hoặc thao tác ứng dụng.</p>
                    </div>
                </div>
            `;
            await initSession();
        }
    });

    function scrollToBottom() {
        setTimeout(() => {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }, 50);
    }

    // Submit Chat - ULTRA FAST SSE STREAMING & INSTANT PREVIEW
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        // 1. Render User Message ngay lập tức
        appendMessage('user', text);
        input.value = '';
        input.style.height = 'auto';

        // 2. Tạo khung tin nhắn AI ngay trong 10ms (Không để người dùng chờ)
        const bubbleId = 'btec-stream-' + Date.now();
        const aiWrap = document.createElement('div');
        aiWrap.className = 'flex items-start gap-2.5';
        aiWrap.innerHTML = `
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#1558C9] to-[#D4FF24] p-0.5 shrink-0 shadow-md">
                <div class="w-full h-full rounded-[10px] bg-[#030407] overflow-hidden flex items-center justify-center">
                    <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" width="36" height="36">
                        <line x1="18" y1="3" x2="18" y2="8" stroke="#D4FF24" stroke-width="1.3" stroke-linecap="round"/>
                        <circle cx="18" cy="2.5" r="1.6" fill="#D4FF24"/>
                        <rect x="8" y="8" width="20" height="17" rx="4" fill="#1558C9" stroke="#D4FF24" stroke-width="0.7"/>
                        <rect x="11" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24"/>
                        <rect x="20.5" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24"/>
                        <circle cx="13.3" cy="14.5" r="1.2" fill="white"/>
                        <circle cx="22.8" cy="14.5" r="1.2" fill="white"/>
                        <path d="M12.5 19.5 Q18 22 23.5 19.5" stroke="#D4FF24" stroke-width="0.8" stroke-linecap="round" fill="none"/>
                        <rect x="11" y="25" width="14" height="8" rx="2.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.6"/>
                        <circle cx="18" cy="29" r="2" fill="#D4FF24"/>
                    </svg>
                </div>
            </div>
            <div class="max-w-[85%] bg-slate-800/90 backdrop-blur-md p-4 rounded-2xl rounded-tl-none border border-white/10 shadow-lg text-slate-200 text-xs leading-relaxed space-y-1">
                <div class="btec-ai-content" id="${bubbleId}"><span class="inline-block w-1.5 h-3.5 bg-[#D4FF24] animate-pulse ml-0.5 align-middle"></span></div>
                <div id="${bubbleId}-meta" class="hidden"></div>
            </div>
        `;
        messagesBox.appendChild(aiWrap);
        scrollToBottom();

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
                                    contentEl.innerHTML = formatMarkdown(fullText) + '<span class="inline-block w-1.5 h-3.5 bg-amber-400 animate-pulse ml-0.5 align-middle"></span>';
                                    scrollToBottom();
                                }
                                if (json.done) {
                                    currentConversationId = json.conversation_id;
                                    contentEl.innerHTML = formatMarkdown(fullText);

                                    let metaHtml = '';
                                    if (json.sources && json.sources.length > 0) {
                                        metaHtml += `
                                            <div class="mt-2.5 pt-2 border-t border-white/10">
                                                <div class="text-[9px] font-black uppercase tracking-wider text-orange-400 mb-1 flex items-center gap-1">
                                                    <span>Nguồn quy định tham chiếu:</span>
                                                </div>
                                                <div class="space-y-1">
                                                    ${json.sources.map(s => `
                                                        <div class="text-[11px] bg-slate-900/60 p-2 rounded-xl border border-white/10 text-slate-300">
                                                            <span class="font-extrabold text-orange-400">[${s.source_id}]</span> ${escapeHtml(s.title || s.name || '')}
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            </div>
                                        `;
                                    }
                                    if (json.app_actions && json.app_actions.length > 0) {
                                        metaHtml += `
                                            <div class="mt-2.5 pt-2 border-t border-white/10 flex flex-wrap gap-1.5">
                                                ${json.app_actions.map(act => `
                                                    <a href="${act.url}" class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 hover:text-white px-3 py-1.5 rounded-xl border border-indigo-400/30 transition shadow-xs">
                                                        <span>${escapeHtml(act.label)}</span>
                                                    </a>
                                                `).join('')}
                                            </div>
                                        `;
                                    }
                                    if (json.suggest_ticket) {
                                        metaHtml += `
                                            <div class="mt-2.5 p-2.5 bg-amber-500/10 rounded-xl border border-amber-500/30 flex items-center justify-between text-xs">
                                                <span class="text-amber-300 text-[11px]">Cần cán bộ kiểm tra thủ công?</span>
                                                <button type="button" onclick="document.getElementById('btec-ticket-modal').classList.remove('hidden')" class="px-2.5 py-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white font-black rounded-lg text-[10px] shadow-sm">
                                                    Tạo Ticket
                                                </button>
                                            </div>
                                        `;
                                    }
                                    if (metaHtml) {
                                        metaEl.innerHTML = metaHtml;
                                        metaEl.classList.remove('hidden');
                                    }
                                    scrollToBottom();
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
                    contentEl.innerHTML = formatMarkdown(data.answer || data.message);
                } else {
                    contentEl.innerHTML = '⚠️ ' + escapeHtml(data.message || 'Hệ thống đang bận, vui lòng thử lại sau giây lát.');
                }
            } catch (fallbackErr) {
                contentEl.innerHTML = '⚠️ Không thể kết nối đến máy chủ BTEC AI Support.';
            }
        }
    });

    // Render Append Message
    function appendMessage(role, content, sources = [], actions = [], confidence = null, suggestTicket = false, animate = false) {
        const wrap = document.createElement('div');
        wrap.className = 'flex items-start gap-2.5 ' + (role === 'user' ? 'justify-end' : '');

        if (role === 'user') {
            wrap.innerHTML = `
                <div class="max-w-[85%] bg-gradient-to-r from-orange-600 to-indigo-600 text-white p-3.5 rounded-2xl rounded-tr-none shadow-lg text-xs leading-relaxed border border-white/10">
                    ${escapeHtml(content).replace(/\n/g, '<br>')}
                </div>
                <div class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 border border-white/10 flex items-center justify-center shrink-0 shadow-md">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
            `;
        } else {
            let sourcesHtml = '';
            if (sources && sources.length > 0) {
                sourcesHtml = `
                    <div class="mt-2.5 pt-2 border-t border-white/10">
                        <div class="text-[9px] font-black uppercase tracking-wider text-orange-400 mb-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span>Nguồn quy định tham chiếu:</span>
                        </div>
                        <div class="space-y-1">
                            ${sources.map(s => `
                                <div class="text-[11px] bg-slate-900/60 p-2 rounded-xl border border-white/10 text-slate-300">
                                    <span class="font-extrabold text-orange-400">[${s.source_id}]</span> ${escapeHtml(s.title || s.name || '')}
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
                    <div class="mt-2.5 pt-2 border-t border-white/10 flex flex-wrap gap-1.5">
                        ${actions.map(act => `
                            <a href="${act.url}" class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 hover:text-white px-3 py-1.5 rounded-xl border border-indigo-400/30 transition shadow-xs">
                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>${escapeHtml(act.label)}</span>
                            </a>
                        `).join('')}
                    </div>
                `;
            }

            let ticketSuggestionHtml = '';
            if (suggestTicket) {
                ticketSuggestionHtml = `
                    <div class="mt-2.5 p-2.5 bg-amber-500/10 rounded-xl border border-amber-500/30 flex items-center justify-between text-xs">
                        <span class="text-amber-300 text-[11px]">Cần cán bộ kiểm tra thủ công?</span>
                        <button type="button" onclick="document.getElementById('btec-ticket-modal').classList.remove('hidden')" class="px-2.5 py-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white font-black rounded-lg text-[10px] shadow-sm">
                            Tạo Ticket
                        </button>
                    </div>
                `;
            }

            const contentId = 'bubble-msg-' + Date.now();
            wrap.innerHTML = `
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#1558C9] to-[#D4FF24] p-0.5 shrink-0 shadow-md">
                    <div class="w-full h-full rounded-[10px] bg-[#030407] overflow-hidden flex items-center justify-center">
                        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" width="36" height="36">
                            <line x1="18" y1="3" x2="18" y2="8" stroke="#D4FF24" stroke-width="1.3" stroke-linecap="round"/>
                            <circle cx="18" cy="2.5" r="1.6" fill="#D4FF24"/>
                            <rect x="8" y="8" width="20" height="17" rx="4" fill="#1558C9" stroke="#D4FF24" stroke-width="0.7"/>
                            <rect x="11" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24"/>
                            <rect x="20.5" y="12.5" width="4.5" height="4" rx="2" fill="#D4FF24"/>
                            <circle cx="13.3" cy="14.5" r="1.2" fill="white"/>
                            <circle cx="22.8" cy="14.5" r="1.2" fill="white"/>
                            <path d="M12.5 19.5 Q18 22 23.5 19.5" stroke="#D4FF24" stroke-width="0.8" stroke-linecap="round" fill="none"/>
                            <rect x="11" y="25" width="14" height="8" rx="2.5" fill="#1558C9" stroke="#D4FF24" stroke-width="0.6"/>
                            <circle cx="18" cy="29" r="2" fill="#D4FF24"/>
                        </svg>
                    </div>
                </div>
                <div class="max-w-[85%] bg-slate-800/90 backdrop-blur-md p-4 rounded-2xl rounded-tl-none border border-white/10 shadow-lg text-slate-200 text-xs leading-relaxed space-y-1">
                    <div class="btec-ai-content" id="${contentId}">${animate ? '<span class="inline-block w-1.5 h-3.5 bg-[#D4FF24] animate-pulse ml-0.5 align-middle"></span>' : formatMarkdown(content)}</div>
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
                            if (el) el.innerHTML = formatMarkdown(slice) + '<span class="inline-block w-1.5 h-3.5 bg-amber-400 animate-pulse ml-0.5 align-middle"></span>';
                            scrollToBottom();
                            setTimeout(typeNext, 12);
                        } else {
                            if (el) el.innerHTML = formatMarkdown(content);
                            if (metaEl) metaEl.classList.remove('hidden');
                            scrollToBottom();
                        }
                    }
                    typeNext();
                }, 50);
            }
        }

        messagesBox.appendChild(wrap);
        scrollToBottom();
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

    function formatMarkdown(text) {
        if (!text) return '';
        let formatted = escapeHtml(text)
            .replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-bold">$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code class="bg-slate-900 px-1.5 py-0.5 rounded text-amber-300 font-mono text-[11px] border border-white/10">$1</code>')
            .replace(/\n\n/g, '<div class="h-2"></div>')
            .replace(/\n/g, '<br>');
        return formatted;
    }

    // Ticket Dialog Actions
    openTicketBtn.addEventListener('click', () => ticketModal.classList.remove('hidden'));
    closeTicketBtn.addEventListener('click', () => ticketModal.classList.add('hidden'));
    cancelTicketBtn.addEventListener('click', () => ticketModal.classList.add('hidden'));

    ticketForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const cat = document.getElementById('ticket-category').value;
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
                    description: desc
                })
            });
            const d = await res.json();
            if (d.success || d.status === 'ok') {
                ticketModal.classList.add('hidden');
                document.getElementById('ticket-desc').value = '';
                const code = d.ticket_code || (d.ticket ? d.ticket.ticket_code : 'ĐÃ GỬI');
                appendMessage('assistant', `✅ Đã tiếp nhận **Ticket hỗ trợ #${code}**! Bộ phận Đào tạo / CTSV sẽ xử lý và phản hồi cho bạn qua email hoặc hệ thống.`);
            } else {
                alert(d.message || 'Không thể tạo ticket');
            }
        } catch (err) {
            alert('Lỗi gửi phiếu hỗ trợ');
        }
    });

})();
</script>
