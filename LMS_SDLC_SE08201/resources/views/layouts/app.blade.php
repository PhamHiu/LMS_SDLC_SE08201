<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS') — LMS.edu</title>
    <script>
        const _warn = console.warn;
        console.warn = function(msg) {
            if(typeof msg === 'string' && msg.includes('cdn.tailwindcss.com should not be used in production')) return;
            _warn.apply(console, arguments);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cyber: {
                            dark: '#030407',
                            card: '#0a0d17',
                            border: 'rgba(255, 255, 255, 0.08)',
                            blue: '#1558C9',
                            'blue-light': '#2563eb',
                            lime: '#D4FF24',
                            'lime-hover': '#c2ee12',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        /* Bảo đảm không bao giờ tràn ngang & mọi phần tử co giãn theo màn hình */
        html, body { max-width: 100%; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }
        img, svg, video, iframe { max-width: 100%; height: auto; }
        /* Chart.js controls its internal width/height properly if container is responsive */
        canvas { display: block; }
        /* Tables: cuộn ngang trong wrapper, không tràn ra ngoài */
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        /* Đảm bảo mọi khung chứa biểu đồ không vượt quá container cha */
        .chart-wrapper { position: relative; width: 100%; }
        /* Chuyển động mượt cho drawer & tương tác */
        #sidebar { will-change: transform; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #1558C9; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #D4FF24; }

        /* ===== THEME CORES: #030407 (Dark), #1558C9 (Deep Blue), #D4FF24 (Electric Lime) ===== */
        .surreal-mesh-bg {
            background: linear-gradient(135deg, #030407 0%, #081125 35%, #030407 65%, #0c1a3a 100%);
            background-size: 400% 400%;
            animation: gradient-drift 15s ease infinite;
            background-attachment: fixed;
            position: relative;
        }

        /* Ambient Glowing Orbs */
        .ambient-orb-1 {
            position: fixed;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(21, 88, 201, 0.28) 0%, rgba(21, 88, 201, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: float-subtle 10s infinite ease-in-out alternate;
        }

        .ambient-orb-2 {
            position: fixed;
            bottom: -15%;
            right: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(212, 255, 36, 0.12) 0%, rgba(212, 255, 36, 0) 70%);
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
            animation: float-subtle 12s infinite ease-in-out alternate-reverse;
        }

        .ambient-orb-3 {
            position: fixed;
            top: 40%;
            left: 30%;
            width: 40vw;
            height: 40vw;
            background: radial-gradient(circle, rgba(21, 88, 201, 0.15) 0%, rgba(21, 88, 201, 0) 70%);
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
            animation: float-subtle 8s infinite ease-in-out;
        }

        /* Ultra 3D Glass Surface */
        .glass-card-3d {
            background: rgba(10, 14, 24, 0.78) !important;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.04) inset !important;
            border-radius: 1.5rem !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            color: #f8fafc !important;
        }

        .glass-card-3d:hover {
            transform: translateY(-6px) scale(1.01) !important;
            border-color: rgba(212, 255, 36, 0.5) !important;
            box-shadow: 0 30px 60px -10px rgba(21, 88, 201, 0.4), 0 0 20px rgba(212, 255, 36, 0.15) inset !important;
        }

        /* Holographic Text Gradient */
        .holo-text {
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 40%, #1558C9 70%, #D4FF24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ===== SIDEBAR 3D POP-OUT ANIMATION ===== */
        .sidebar-nav-item {
            position: relative;
            transform-origin: left center;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .sidebar-nav-item:hover {
            transform: translateX(8px) scale(1.04);
            background: linear-gradient(90deg, rgba(21, 88, 201, 0.4) 0%, rgba(212, 255, 36, 0.12) 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 10px 25px -5px rgba(21, 88, 201, 0.5), 0 0 15px rgba(212, 255, 36, 0.2) !important;
            border-left: 3px solid #D4FF24 !important;
            border-radius: 0.75rem !important;
        }

        .sidebar-nav-item:hover span:first-child, 
        .sidebar-nav-item:hover svg, 
        .sidebar-nav-item:hover img {
            transform: scale(1.2) rotate(-3deg);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            filter: drop-shadow(0 0 8px rgba(212, 255, 36, 0.8));
        }

        .sidebar-nav-item.active-menu-item {
            transform: translateX(6px);
            background: linear-gradient(90deg, #1558C9 0%, #0d3882 100%) !important;
            box-shadow: 0 10px 20px -3px rgba(21, 88, 201, 0.6) !important;
            border-left: 3px solid #D4FF24 !important;
            color: #ffffff !important;
        }

        /* Nút Học Cùng Mr. Biết Tuốt */
        .mr-biet-tuot-btn {
            transform-origin: center;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .mr-biet-tuot-btn:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(212, 255, 36, 0.4), 0 0 25px rgba(21, 88, 201, 0.4) !important;
            border-color: #D4FF24 !important;
        }

        /* ===== HIGH-TECH GLASSMORPHISM & STYLES ===== */
        :root {
            --glass-bg: rgba(255, 255, 255, 0.72);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(3, 4, 7, 0.3);
            --brand-dark: #030407;
            --brand-blue: #1558C9;
            --brand-lime: #D4FF24;
        }

        /* 3D Depth Card with Glassmorphism */
        .glass-card, .glass-card-3d {
            background: rgba(8, 12, 22, 0.82) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.04) inset !important;
            border-radius: 1.25rem !important;
            color: #f1f5f9 !important;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .glass-card:hover, .glass-card-3d:hover {
            transform: translateY(-4px) scale(1.008) !important;
            border-color: rgba(212, 255, 36, 0.4) !important;
            box-shadow: 0 20px 45px -10px rgba(21, 88, 201, 0.4), 0 0 20px rgba(212, 255, 36, 0.15) inset !important;
        }

        /* Dark Glass for Sidebar & High-Tech Components */
        .glass-dark {
            background: linear-gradient(180deg, rgba(3, 4, 7, 0.96) 0%, rgba(8, 13, 24, 0.94) 50%, rgba(3, 4, 7, 0.98) 100%);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 10px 0 35px -5px rgba(0, 0, 0, 0.6);
        }

        /* Cyber Glow Pulse Animations */
        @keyframes cyber-pulse {
            0%, 100% {
                box-shadow: 0 0 15px rgba(21, 88, 201, 0.5), 0 0 30px rgba(212, 255, 36, 0.2);
            }
            50% {
                box-shadow: 0 0 25px rgba(21, 88, 201, 0.8), 0 0 45px rgba(212, 255, 36, 0.35);
            }
        }
        .cyber-glow {
            animation: cyber-pulse 3s infinite ease-in-out;
        }

        /* Floating 3D Micro-animation */
        @keyframes float-subtle {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(0.5deg); }
        }
        .float-3d {
            animation: float-subtle 4s ease-in-out infinite;
        }

        /* Gradient Glow Mesh Background */
        .mesh-gradient-bg {
            background-color: #030407;
            background-image: 
                radial-gradient(at 0% 0%, rgba(21, 88, 201, 0.22) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(212, 255, 36, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(21, 88, 201, 0.18) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(212, 255, 36, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
        }

        /* Neon Border Highlighting */
        .neon-border {
            position: relative;
        }
        .neon-border::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, rgba(21, 88, 201, 0.7), rgba(212, 255, 36, 0.4), rgba(21, 88, 201, 0.7));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }
            /* ===== HỆ THỐNG ĐẢM BẢO ĐỘ TƯƠNG PHẢN & MÀU CHỮ TOÀN DIỆN CHO 3 ROLE ===== */
        /* 1. Mọi chữ tối (slate/gray) trên nền tối tự động đổi thành sáng tương phản cao */
        .text-slate-950, .text-slate-900, .text-slate-800,
        .text-gray-950, .text-gray-900, .text-gray-800 {
            color: #ffffff !important;
        }

        .text-slate-700, .text-gray-700 {
            color: #f8fafc !important;
        }

        .text-slate-600, .text-gray-600 {
            color: #e2e8f0 !important;
        }

        .text-slate-500, .text-gray-500 {
            color: #cbd5e1 !important;
        }

        .text-slate-400, .text-gray-400, .text-zinc-400, .text-neutral-400 {
            color: #94a3b8 !important;
        }

        .text-black, .text-zinc-950, .text-zinc-900, .text-zinc-800,
        .text-neutral-950, .text-neutral-900, .text-neutral-800 {
            color: #ffffff !important;
        }

        /* 2. Biến các khối màu trắng thô (bg-white) thành nền kính tối sang trọng (Dark Glass) */
        .bg-white:not(button):not(input):not(select):not(textarea):not(.badge):not(.avatar):not(.dropdown-menu):not(.mr-biet-tuot-avatar):not(img) {
            background-color: rgba(10, 14, 24, 0.88) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .bg-slate-50, .bg-slate-50\/50, .bg-slate-50\/60, .bg-slate-50\/70, .bg-slate-50\/80,
        .bg-slate-100, .bg-slate-100\/50, .bg-slate-100\/60, .bg-slate-100\/70 {
            background-color: rgba(14, 20, 36, 0.75) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
        }

        /* 3. Tiêu đề & Nội dung Bảng (Table) luôn sáng, viền sắc nét */
        table thead, table thead tr, table thead th {
            background-color: rgba(14, 20, 36, 0.95) !important;
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        table tbody tr {
            border-color: rgba(255, 255, 255, 0.06) !important;
        }

        table tbody tr:hover {
            background-color: rgba(21, 88, 201, 0.08) !important;
        }

        table tbody td {
            border-color: rgba(255, 255, 255, 0.06) !important;
            color: #f1f5f9;
        }

        /* 4. Ô nhập liệu Form (Input, Select, Textarea) luôn có chữ trắng sáng, nền tối sang trọng */
        input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not(#chatInput),
        select,
        textarea {
            background-color: rgba(10, 14, 24, 0.92) !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #D4FF24 !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(212, 255, 36, 0.25) !important;
        }

        input::placeholder, textarea::placeholder {
            color: #94a3b8 !important;
        }

        select option {
            background-color: #030407 !important;
            color: #ffffff !important;
        }

        /* 5. Nút bấm viền xám nền trắng chuyển sang nút kính sang trọng */
        button.bg-white, a.bg-white {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        button.bg-white:hover, a.bg-white:hover {
            background-color: rgba(21, 88, 201, 0.25) !important;
            border-color: #D4FF24 !important;
            color: #ffffff !important;
        }

        /* 6. Huy hiệu & Trạng thái phát sáng neon, chữ siêu nét */
        .bg-emerald-50, .bg-emerald-100 {
            background-color: rgba(16, 185, 129, 0.22) !important;
            color: #6ee7b7 !important;
            border-color: rgba(52, 211, 153, 0.4) !important;
        }
        .text-emerald-700, .text-emerald-800, .text-emerald-900 {
            color: #6ee7b7 !important;
        }

        .bg-rose-50, .bg-rose-100 {
            background-color: rgba(244, 63, 94, 0.22) !important;
            color: #fda4af !important;
            border-color: rgba(251, 113, 133, 0.4) !important;
        }
        .text-rose-700, .text-rose-800, .text-rose-900 {
            color: #fda4af !important;
        }

        .bg-amber-50, .bg-amber-100 {
            background-color: rgba(245, 158, 11, 0.22) !important;
            color: #fde68a !important;
            border-color: rgba(251, 191, 36, 0.4) !important;
        }
        .text-amber-700, .text-amber-800, .text-amber-900 {
            color: #fde68a !important;
        }

        .bg-indigo-50, .bg-indigo-100 {
            background-color: rgba(99, 102, 241, 0.22) !important;
            color: #c7d2fe !important;
            border-color: rgba(129, 140, 248, 0.4) !important;
        }
        .text-indigo-700, .text-indigo-800, .text-indigo-900 {
            color: #c7d2fe !important;
        }

        .bg-sky-50, .bg-sky-100 {
            background-color: rgba(14, 165, 233, 0.22) !important;
            color: #bae6fd !important;
            border-color: rgba(56, 189, 248, 0.4) !important;
        }
        .text-sky-700, .text-sky-800, .text-sky-900 {
            color: #bae6fd !important;
        }

        .bg-blue-50, .bg-blue-100 {
            background-color: rgba(21, 88, 201, 0.25) !important;
            color: #93c5fd !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
        }
        .text-blue-700, .text-blue-800, .text-blue-900 {
            color: #93c5fd !important;
        }

        .bg-purple-50, .bg-purple-100, .bg-violet-50, .bg-violet-100 {
            background-color: rgba(168, 85, 247, 0.22) !important;
            color: #d8b4fe !important;
            border-color: rgba(192, 132, 252, 0.4) !important;
        }
        .text-purple-700, .text-purple-800, .text-purple-900,
        .text-violet-700, .text-violet-800, .text-violet-900 {
            color: #d8b4fe !important;
        }

        .bg-yellow-50, .bg-yellow-100 {
            background-color: rgba(234, 179, 8, 0.22) !important;
            color: #fef08a !important;
            border-color: rgba(250, 204, 21, 0.4) !important;
        }
        .text-yellow-700, .text-yellow-800, .text-yellow-900 {
            color: #fef08a !important;
        }

        .bg-orange-50, .bg-orange-100 {
            background-color: rgba(249, 115, 22, 0.22) !important;
            color: #fdba74 !important;
            border-color: rgba(251, 146, 60, 0.4) !important;
        }
        .text-orange-700, .text-orange-800, .text-orange-900 {
            color: #fdba74 !important;
        }

        .bg-teal-50, .bg-teal-100, .bg-cyan-50, .bg-cyan-100 {
            background-color: rgba(20, 184, 166, 0.22) !important;
            color: #5eead4 !important;
            border-color: rgba(45, 212, 191, 0.4) !important;
        }
        .text-teal-700, .text-teal-800, .text-teal-900,
        .text-cyan-700, .text-cyan-800, .text-cyan-900 {
            color: #5eead4 !important;
        }

        /* 7. Phân trang Laravel (Pagination) tương phản cao */
        nav[role="navigation"] span, nav[role="navigation"] a {
            background-color: rgba(14, 20, 36, 0.85) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #f1f5f9 !important;
        }
        nav[role="navigation"] a:hover {
            background-color: #1558C9 !important;
            color: #D4FF24 !important;
            border-color: #D4FF24 !important;
        }
        nav[role="navigation"] [aria-current="page"] span,
        nav[role="navigation"] span.bg-blue-50 {
            background-color: #1558C9 !important;
            color: #D4FF24 !important;
            border-color: #D4FF24 !important;
            font-weight: 800 !important;
        }

        /* 8. Hộp thoại thông báo / Modal Box */
        .modal-content, [role="dialog"] {
            background-color: #0a0e1a !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.9) !important;
        }

        /* Viền sáng mỏng đồng bộ */
        .border-slate-200, .border-slate-300, .border-slate-100 {
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen text-slate-100 antialiased selection:bg-[#D4FF24] selection:text-[#030407] relative" style="background-color: #030407; background-image: radial-gradient(at 0% 0%, rgba(21, 88, 201, 0.25) 0px, transparent 50%), radial-gradient(at 100% 0%, rgba(212, 255, 36, 0.12) 0px, transparent 40%), radial-gradient(at 50% 50%, rgba(21, 88, 201, 0.15) 0px, transparent 55%), radial-gradient(at 100% 100%, rgba(21, 88, 201, 0.22) 0px, transparent 50%); background-attachment: fixed;">
    {{-- Ambient Glowing Light Spheres --}}
    <div class="ambient-orb-1"></div>
    <div class="ambient-orb-2"></div>
    <div class="ambient-orb-3"></div>
@php
    $u = auth()->user();
    $item = 'sidebar-nav-item flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-200 font-medium cursor-pointer';
    $active = 'active-menu-item font-bold text-white shadow-lg';
@endphp

<div class="min-h-screen">
    {{-- ===== BACKDROP (mobile khi mở drawer) ===== --}}
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-black/60 backdrop-blur-sm md:hidden"></div>

    {{-- ===== SIDEBAR / DRAWER ===== --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col glass-dark text-slate-100 -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0">
        <div class="flex items-center gap-2 px-6 h-16 border-b border-white/10">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#1558C9] text-[#D4FF24] text-lg font-black shadow-lg shadow-[#1558C9]/40 border border-[#D4FF24]/30">L</span>
            <span class="text-xl font-extrabold text-white tracking-tight">LMS<span class="text-[#D4FF24]">.edu</span></span>
            <button id="sidebarClose" class="ml-auto rounded-lg p-1.5 text-slate-300 hover:bg-white/10 hover:text-white md:hidden" aria-label="Đóng menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">

            @if ($u->isAdmin())
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-[#D4FF24]">Quản trị viên</p>
                <a href="{{ route('admin.dashboard') }}" class="{{ $item }} {{ request()->routeIs('admin.dashboard') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(21,88,201,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Dashboard</span></a>
                <a href="{{ route('admin.users.index') }}" class="{{ $item }} {{ request()->routeIs('admin.users.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg> <span>Quản lý người dùng</span></a>
                <a href="{{ route('admin.majors.index') }}" class="{{ $item }} {{ request()->routeIs('admin.majors.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> <span>Quản lý chuyên ngành</span></a>
                <a href="{{ route('admin.subjects.index') }}" class="{{ $item }} {{ request()->routeIs('admin.subjects.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(21,88,201,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Quản lý môn học</span></a>
                <a href="{{ route('admin.classes.index') }}" class="{{ $item }} {{ request()->routeIs('admin.classes.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg> <span>Quản lý lớp học</span></a>
                <a href="{{ route('admin.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('admin.notifications.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg> <span>Thông báo hệ thống</span></a>
                <a href="{{ route('admin.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.reports.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#38bdf8] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <span>Thống kê & Báo cáo</span></a>
                <a href="{{ route('admin.risk.index') }}" class="{{ $item }} {{ request()->routeIs('admin.risk.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24] animate-pulse filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> <span class="font-bold text-[#D4FF24]">Cảnh báo nguy cơ (AI)</span></a>
            @elseif ($u->isTeacher())
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-[#D4FF24]">Giảng viên</p>
                <a href="{{ route('teacher.dashboard') }}" class="{{ $item }} {{ request()->routeIs('teacher.dashboard') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(21,88,201,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Dashboard</span></a>
                <a href="{{ route('teacher.classes.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.classes.*') || request()->routeIs('teacher.attendance.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg> <span>Lớp của tôi</span></a>
                <a href="{{ route('teacher.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.attendance.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24] group-hover:text-white filter drop-shadow-[0_0_6px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span class="font-medium text-[#D4FF24]">Điểm danh học sinh</span></a>
                <a href="{{ route('teacher.materials.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.materials.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Học liệu</span></a>
                <a href="{{ route('teacher.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.assignments.*') || request()->routeIs('teacher.grading.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> <span>Bài tập & Chấm bài</span></a>
                <a href="{{ route('teacher.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.notifications.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg> <span>Thông báo lớp</span></a>
                <a href="{{ route('teacher.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.analytics.*') || request()->routeIs('teacher.reports.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#38bdf8] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <span>Thống kê lớp</span></a>
                <a href="{{ route('teacher.risk.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.risk.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24] animate-pulse filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> <span class="font-bold text-[#D4FF24]">Cảnh báo học sinh (AI)</span></a>
            @else
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-[#D4FF24]">Sinh viên</p>
                <a href="{{ route('student.dashboard') }}" class="{{ $item }} {{ request()->routeIs('student.dashboard') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(21,88,201,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Dashboard</span></a>
                <a href="{{ route('student.schedule.index') }}" class="{{ $item }} {{ request()->routeIs('student.schedule.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#38bdf8] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <span>Thời khóa biểu</span></a>
                <a href="{{ route('student.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('student.attendance.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24] group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span>Lịch sử điểm danh</span></a>
                <a href="{{ route('student.materials.index') }}" class="{{ $item }} {{ request()->routeIs('student.materials.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Học liệu</span></a>
                <a href="{{ route('student.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('student.assignments.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#3b82f6] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> <span>Bài tập & Quiz</span></a>
                <a href="{{ route('student.grades.index') }}" class="{{ $item }} {{ request()->routeIs('student.grades.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#D4FF24] group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg> <span>Bảng điểm</span></a>
                <a href="{{ route('student.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('student.notifications.*') ? $active : '' }}"><svg class="w-5 h-5 text-[#38bdf8] group-hover:text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> <span>Thông báo</span></a>

                <div class="pt-3 pb-1">
                    <a href="{{ route('student.ai-tutor.index') }}" class="mr-biet-tuot-btn group relative flex items-center gap-3 rounded-xl px-3.5 py-3 text-base font-extrabold transition-all duration-200 {{ request()->routeIs('student.ai-tutor.*') ? 'bg-gradient-to-r from-[#1558C9] to-[#0d3882] text-white shadow-lg ring-2 ring-[#D4FF24]' : 'bg-gradient-to-r from-[#1558C9]/20 via-[#D4FF24]/15 to-[#1558C9]/20 text-[#D4FF24] hover:from-[#1558C9]/35 hover:to-[#D4FF24]/25 hover:text-white border border-[#D4FF24]/30' }}">
                        {{-- Mr. Biết Tuốt Cute SVG Avatar --}}
                        <div class="w-9 h-9 rounded-xl border border-[#D4FF24]/60 shadow-lg shadow-[#1558C9]/50 shrink-0 transform group-hover:scale-110 group-hover:rotate-6 transition duration-300 overflow-hidden" style="background: linear-gradient(135deg, #1558C9 0%, #0a0d17 100%);">
                            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                                <!-- Hat -->
                                <rect x="8" y="8" width="20" height="4" rx="2" fill="#D4FF24" opacity="0.95"/>
                                <rect x="13" y="4" width="10" height="6" rx="2" fill="#D4FF24" opacity="0.9"/>
                                <rect x="14" y="4" width="8" height="2" rx="1" fill="#c2ee12"/>
                                <!-- Face -->
                                <circle cx="18" cy="20" r="8" fill="#FFD6A5" stroke="#D4FF24" stroke-width="0.8"/>
                                <!-- Eyes - big cute sparkly -->
                                <ellipse cx="14.5" cy="19" rx="2" ry="2.3" fill="#1558C9"/>
                                <ellipse cx="21.5" cy="19" rx="2" ry="2.3" fill="#1558C9"/>
                                <circle cx="15.3" cy="18.3" r="0.8" fill="white"/>
                                <circle cx="22.3" cy="18.3" r="0.8" fill="white"/>
                                <!-- Blush -->
                                <ellipse cx="11.5" cy="22" rx="2" ry="1.2" fill="#ff9eb5" opacity="0.5"/>
                                <ellipse cx="24.5" cy="22" rx="2" ry="1.2" fill="#ff9eb5" opacity="0.5"/>
                                <!-- Smile -->
                                <path d="M15 23 Q18 25.5 21 23" stroke="#c0763a" stroke-width="1.2" stroke-linecap="round" fill="none"/>
                                <!-- Glasses -->
                                <rect x="12" y="17.5" width="4.5" height="3.5" rx="1.8" fill="none" stroke="#D4FF24" stroke-width="1" opacity="0.9"/>
                                <rect x="19.5" y="17.5" width="4.5" height="3.5" rx="1.8" fill="none" stroke="#D4FF24" stroke-width="1" opacity="0.9"/>
                                <line x1="16.5" y1="19.3" x2="19.5" y2="19.3" stroke="#D4FF24" stroke-width="0.8" opacity="0.8"/>
                                <!-- Star sparkle -->
                                <path d="M28 10 L28.6 12 L30.5 12 L29 13.2 L29.6 15 L28 13.9 L26.4 15 L27 13.2 L25.5 12 L27.4 12 Z" fill="#D4FF24" opacity="0.9"/>
                            </svg>
                        </div>
                        <span class="tracking-wide text-xs sm:text-sm font-black uppercase text-[#D4FF24] group-hover:text-white">HỌC CÙNG MR. BIẾT TUỐT</span>
                    </a>
                </div>
            @endif

            <p class="px-3 pt-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tài khoản</p>
            <a href="{{ route('profile.edit') }}" class="{{ $item }} {{ request()->routeIs('profile.*') ? $active : '' }}"><svg class="w-5 h-5 text-slate-300 group-hover:text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(203,213,225,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> <span>Hồ sơ cá nhân</span></a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                @if ($u->avatar)
                    <img src="{{ asset('storage/'.$u->avatar) }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-[#D4FF24]" alt="avatar">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1558C9] text-[#D4FF24] font-black border border-[#D4FF24]/40">{{ mb_substr($u->full_name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $u->full_name }}</p>
                    <p class="truncate text-xs text-[#D4FF24] font-medium">{{ $u->roleLabel() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full flex items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold text-indigo-100 transition duration-200 hover:bg-rose-600/80 hover:text-white border border-white/10 hover:border-rose-400/50 hover:shadow-lg hover:shadow-rose-600/30">
                    <svg class="w-4 h-4 text-indigo-200 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Đăng xuất</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== MAIN ===== --}}
    <div class="flex min-h-screen flex-col md:ml-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-white/10 bg-[#030407]/75 backdrop-blur-2xl shadow-lg px-4 md:px-8 relative z-20">
            <div class="flex min-w-0 items-center gap-2 md:gap-3">
                <button id="sidebarToggle" class="-ml-1 rounded-lg p-2 text-slate-200 hover:bg-white/10 md:hidden" aria-label="Mở menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-base sm:text-lg md:text-xl font-bold text-white">@yield('title', 'LMS')</h1>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <div class="hidden sm:inline-flex items-center gap-2.5 rounded-xl bg-white/5 border border-white/10 px-3.5 py-1.5 shadow-[0_4px_15px_rgba(0,0,0,0.5)] backdrop-blur-md">
                    <svg class="w-4 h-4 text-[#D4FF24] shrink-0 filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm md:text-base font-black uppercase tracking-wider text-white drop-shadow-[0_2px_6px_rgba(0,0,0,0.8)]">
                        {{ mb_strtoupper(now()->isoFormat('dddd, DD/MM/YYYY')) }}
                    </span>
                </div>
                <span class="inline-flex items-center rounded-xl bg-[#1558C9]/20 border border-[#1558C9]/40 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-[#D4FF24] shadow-[0_0_12px_rgba(21,88,201,0.4)]">{{ $u->roleLabel() }}</span>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/15 backdrop-blur-md px-4 py-3 text-sm text-emerald-200 shadow-lg shadow-emerald-500/10">
                    <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-500/30 bg-rose-500/15 backdrop-blur-md px-4 py-3 text-sm text-rose-200 shadow-lg shadow-rose-500/10">
                    <svg class="h-5 w-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/15 backdrop-blur-md px-4 py-3 text-sm text-rose-200 shadow-lg shadow-rose-500/10">
                    <p class="font-semibold text-rose-200">Vui lòng kiểm tra lại:</p>
                    <ul class="mt-1 list-disc pl-5 text-rose-300">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        var sidebar  = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        var toggle   = document.getElementById('sidebarToggle');
        var closeBtn = document.getElementById('sidebarClose');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        if (toggle)   toggle.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
        // Đóng drawer sau khi chọn mục trên mobile
        sidebar.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.innerWidth < 768) closeSidebar();
            });
        });
        // Reset trạng thái khi chuyển sang desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    })();
</script>
@if(auth()->check() && auth()->user()->isStudent())
    @include('components.btec-ai-bubble')
@endif

@stack('scripts')
</body>
</html>
