<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS') — Smart LMS</title>
    <script>
        const _warn = console.warn;
        console.warn = function(msg) {
            if(typeof msg === 'string' && msg.includes('cdn.tailwindcss.com should not be used in production')) return;
            _warn.apply(console, arguments);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0f172a;
            --accent: #2563eb;
            --line: #e4e4e7;
            --line-strong: #d4d4d8;
            --ink: #09090b;
            --muted: #71717a;
            --page: #fafafa;
            --surface: #ffffff;
            --side: #09090b;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--page);
            color: var(--ink);
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
            -webkit-font-smoothing: antialiased;
        }
        code, pre, .font-mono { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace; }
        table, .tabular-nums { font-variant-numeric: tabular-nums; }

        /* Không tràn ngang */
        html, body { max-width: 100%; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }
        img, svg, video, iframe { max-width: 100%; height: auto; }
        canvas { display: block; }
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        .chart-wrapper { position: relative; width: 100%; }
        #sidebar { will-change: transform; }

        /* Scrollbar mảnh, sắc nét */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #a1a1aa; }
        #sidebar ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.14); }

        /* High-Tech Precision Surfaces: khử hiệu ứng bóng nhựa & glassmorphism cũ trên toàn dự án */
        .glass-card, .glass-card-3d {
            background: var(--surface) !important;
            border: 1px solid var(--line) !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03) !important;
            color: var(--ink);
        }
        .glass-dark { background: var(--side); }
        .holo-text { color: var(--ink); }
        .mesh-gradient-bg, .surreal-mesh-bg { background: var(--page); }
        [class*="drop-shadow"] { filter: none !important; }
        [class*="backdrop-blur"] { -webkit-backdrop-filter: none !important; backdrop-filter: none !important; }
        main .shadow-sm, main .shadow, main .shadow-md, main .shadow-lg {
            box-shadow: 0 1px 2px rgba(9, 9, 11, 0.04) !important;
        }

        /* Sidebar Obsidian */
        .side-link {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.48rem 0.7rem; border-radius: 6px;
            color: #a1a1aa; font-size: 13px; font-weight: 500;
            transition: background-color 0.14s ease, color 0.14s ease;
        }
        .side-link svg { width: 16px; height: 16px; flex-shrink: 0; color: #71717a; transition: color 0.14s ease; }
        .side-link:hover { background: rgba(255, 255, 255, 0.06); color: #f4f4f5; }
        .side-link:hover svg { color: #d4d4d8; }
        .side-link.is-active {
            background: rgba(255, 255, 255, 0.09);
            color: #ffffff;
            font-weight: 600;
            box-shadow: inset 2px 0 0 #3b82f6;
        }
        .side-link.is-active svg { color: #60a5fa; }
        .side-heading {
            padding: 0.65rem 0.7rem 0.3rem;
            font-size: 10px; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: #52525b;
        }

        /* Bảng biểu sắc nét kiểu Enterprise */
        main table thead th {
            background-color: #fafafa;
            color: #52525b;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--line);
        }
        main table tbody tr {
            border-color: #f4f4f5;
            transition: background-color 0.12s ease;
        }
        main table tbody tr:hover {
            background-color: #fafafa;
        }

        /* Form controls */
        input:not([type="checkbox"]):not([type="radio"]), select, textarea {
            border-color: var(--line-strong);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--brand) !important;
            box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.10) !important;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-[#fafafa] text-zinc-950 antialiased">
@php
    $u = auth()->user();
    $item = 'side-link';
    $active = 'is-active';
@endphp

<div class="min-h-screen">
    {{-- ===== BACKDROP (mobile) ===== --}}
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-black/50 md:hidden"></div>

    {{-- ===== SIDEBAR OBSIDIAN ===== --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-[#09090b] text-zinc-300 border-r border-zinc-800/80 -translate-x-full transition-transform duration-200 ease-out md:translate-x-0">
        <div class="flex items-center gap-2.5 px-4 h-14 border-b border-zinc-800/80">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded bg-white text-zinc-950 text-xs font-bold tracking-tight font-mono">SL</span>
            <div class="min-w-0 leading-none">
                <span class="text-sm font-semibold tracking-tight text-white">Smart LMS</span>
                <span class="block text-[10px] font-mono text-zinc-500 mt-0.5">ACADEMIC CORE</span>
            </div>
            <button id="sidebarClose" class="ml-auto rounded p-1 text-zinc-400 hover:bg-zinc-800 hover:text-white md:hidden" aria-label="Đóng menu">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-2.5 py-3 space-y-0.5">
            @if ($u->isAdmin())
                <p class="side-heading">Quản trị hệ thống</p>
                <a href="{{ route('admin.dashboard') }}" class="{{ $item }} {{ request()->routeIs('admin.dashboard') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Tổng quan</span></a>
                <a href="{{ route('admin.users.index') }}" class="{{ $item }} {{ request()->routeIs('admin.users.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg> <span>Người dùng</span></a>
                <a href="{{ route('admin.majors.index') }}" class="{{ $item }} {{ request()->routeIs('admin.majors.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg> <span>Chuyên ngành</span></a>
                <a href="{{ route('admin.subjects.index') }}" class="{{ $item }} {{ request()->routeIs('admin.subjects.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Môn học</span></a>
                <a href="{{ route('admin.classes.index') }}" class="{{ $item }} {{ request()->routeIs('admin.classes.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg> <span>Lớp học</span></a>

                <p class="side-heading">Vận hành & AI</p>
                <a href="{{ route('admin.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('admin.notifications.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6"/></svg> <span>Thông báo hệ thống</span></a>
                <a href="{{ route('admin.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.reports.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <span>Thống kê & Báo cáo</span></a>
                <a href="{{ route('admin.risk.index') }}" class="{{ $item }} {{ request()->routeIs('admin.risk.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> <span>Tỷ lệ nghỉ học</span></a>
                <a href="{{ route('admin.rag.index') }}" class="{{ $item }} {{ request()->routeIs('admin.rag.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg> <span>Kho tri thức RAG</span></a>
                <a href="{{ route('admin.tickets.index') }}" class="{{ $item }} {{ request()->routeIs('admin.tickets.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg> <span>Quản lý Ticket</span></a>
            @elseif ($u->isTeacher())
                <p class="side-heading">Giảng dạy</p>
                <a href="{{ route('teacher.dashboard') }}" class="{{ $item }} {{ request()->routeIs('teacher.dashboard') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Tổng quan</span></a>
                <a href="{{ route('teacher.classes.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.classes.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg> <span>Lớp phụ trách</span></a>
                <a href="{{ route('teacher.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.attendance.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span>Điểm danh</span></a>
                <a href="{{ route('teacher.materials.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.materials.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Học liệu</span></a>
                <a href="{{ route('teacher.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.assignments.*') || request()->routeIs('teacher.grading.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> <span>Bài tập & Chấm bài</span></a>

                <p class="side-heading">Theo dõi & Hỗ trợ</p>
                <a href="{{ route('teacher.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.notifications.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6"/></svg> <span>Thông báo lớp</span></a>
                <a href="{{ route('teacher.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.analytics.*') || request()->routeIs('teacher.reports.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <span>Thống kê lớp</span></a>
                <a href="{{ route('teacher.risk.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.risk.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> <span>Tỷ lệ nghỉ học</span></a>
                <a href="{{ route('teacher.tickets.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.tickets.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg> <span>Ticket chuyển tiếp</span></a>
            @else
                <p class="side-heading">Học tập</p>
                <a href="{{ route('student.dashboard') }}" class="{{ $item }} {{ request()->routeIs('student.dashboard') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Tổng quan</span></a>
                <a href="{{ route('student.schedule.index') }}" class="{{ $item }} {{ request()->routeIs('student.schedule.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <span>Thời khóa biểu</span></a>
                <a href="{{ route('student.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('student.attendance.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span>Điểm danh</span></a>
                <a href="{{ route('student.materials.index') }}" class="{{ $item }} {{ request()->routeIs('student.materials.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Học liệu</span></a>
                <a href="{{ route('student.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('student.assignments.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> <span>Bài tập & Quiz</span></a>
                <a href="{{ route('student.grades.index') }}" class="{{ $item }} {{ request()->routeIs('student.grades.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> <span>Bảng điểm</span></a>

                <p class="side-heading">Hỗ trợ & AI</p>
                <a href="{{ route('student.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('student.notifications.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> <span>Thông báo</span></a>
                <a href="{{ route('student.tickets.index') }}" class="{{ $item }} {{ request()->routeIs('student.tickets.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg> <span>Phiếu hỗ trợ</span></a>
                <a href="{{ route('student.ai-tutor.index') }}" class="{{ $item }} {{ request()->routeIs('student.ai-tutor.*') ? $active : '' }}">
                    <img src="{{ asset('images/mr_biet_tuot.png') }}" alt="Mr. Biết Tuốt" class="w-6 h-6 rounded bg-white p-0.5 object-contain shrink-0">
                    <span>Học cùng Mr. Biết Tuốt</span>
                </a>
            @endif

            <p class="side-heading">Tài khoản</p>
            <a href="{{ route('profile.edit') }}" class="{{ $item }} {{ request()->routeIs('profile.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> <span>Hồ sơ cá nhân</span></a>
        </nav>

        <div class="border-t border-zinc-800/80 p-3">
            <div class="flex items-center gap-2.5 px-1">
                @if ($u->avatar)
                    <img src="{{ asset('storage/'.$u->avatar) }}" class="h-8 w-8 rounded-md object-cover border border-zinc-700" alt="avatar">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-zinc-800 border border-zinc-700 text-zinc-200 text-xs font-semibold font-mono">{{ mb_substr($u->full_name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-zinc-100">{{ $u->full_name }}</p>
                    <p class="truncate text-[11px] text-zinc-500">{{ $u->roleLabel() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2.5">
                @csrf
                <button class="w-full flex items-center justify-center gap-1.5 rounded-md border border-zinc-800 bg-zinc-900/70 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-800 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Đăng xuất</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== MAIN WORKSPACE ===== --}}
    <div class="flex min-h-screen flex-col md:ml-60">
        <header class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-zinc-200 bg-white px-4 md:px-7">
            <div class="flex min-w-0 items-center gap-2.5">
                <button id="sidebarToggle" class="-ml-1 rounded p-1.5 text-zinc-600 hover:bg-zinc-100 md:hidden" aria-label="Mở menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-sm sm:text-base font-semibold tracking-tight text-zinc-900">@yield('title', 'LMS')</h1>
            </div>
            <div class="flex shrink-0 items-center gap-2.5">
                <span class="hidden sm:inline font-mono text-xs text-zinc-500">{{ now()->format('d/m/Y') }}</span>
                <span class="inline-flex items-center rounded border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-700">{{ $u->roleLabel() }}</span>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-6 max-w-[1440px] w-full mx-auto">
            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 flex items-start gap-2.5 rounded-md border border-emerald-200 bg-emerald-50/80 px-3.5 py-2.5 text-xs font-medium text-emerald-900">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 flex items-start gap-2.5 rounded-md border border-rose-200 bg-rose-50/80 px-3.5 py-2.5 text-xs font-medium text-rose-900">
                    <svg class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-rose-200 bg-rose-50/80 px-3.5 py-2.5 text-xs text-rose-900">
                    <p class="font-semibold">Vui lòng kiểm tra lại:</p>
                    <ul class="mt-1 list-disc pl-4 space-y-0.5">
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
        sidebar.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.innerWidth < 768) closeSidebar();
            });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    })();
</script>
@include('components.network-status-banner')
@include('components.mermaid-diagram-runtime')
@if(auth()->check() && auth()->user()->isStudent())
    @include('components.btec-ai-bubble')
@endif
@stack('scripts')
</body>
</html>
