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
        tailwind.config = { darkMode: 'class' };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #1e4f8f;
            --brand-dark: #163c6d;
            --line: #e5e7eb;
            --ink: #1f2937;
            --muted: #6b7280;
            --page: #f3f4f6;
            --side: #1b2a41;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        /* Bảo đảm không bao giờ tràn ngang & mọi phần tử co giãn theo màn hình */
        html, body { max-width: 100%; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }
        img, svg, video, iframe { max-width: 100%; height: auto; }
        /* Chart.js tự co giãn theo khung chứa */
        canvas { display: block; }
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        .chart-wrapper { position: relative; width: 100%; }
        #sidebar { will-change: transform; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Khung nội dung (tên class cũ vẫn dùng trong các view) */
        .glass-card, .glass-card-3d {
            background: #fff !important;
            border: 1px solid var(--line) !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04) !important;
            color: var(--ink);
        }
        .glass-dark { background: var(--side); }
        .holo-text { color: var(--ink); }
        .mesh-gradient-bg, .surreal-mesh-bg { background: var(--page); }

        /* Tắt hiệu ứng trang trí còn sót lại ở các view */
        [class*="drop-shadow"] { filter: none !important; }
        [class*="backdrop-blur"] { -webkit-backdrop-filter: none !important; backdrop-filter: none !important; }

        /* Menu bên trái */
        .side-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .5rem .75rem; border-radius: 6px;
            color: #c7d0dd; font-weight: 500;
        }
        .side-link svg { width: 18px; height: 18px; flex-shrink: 0; color: #8fa3bf; }
        .side-link:hover { background: rgba(255, 255, 255, .06); color: #fff; }
        .side-link.is-active { background: rgba(255, 255, 255, .10); color: #fff; box-shadow: inset 3px 0 0 #7fa7dd; }
        .side-link.is-active svg { color: #c9d8ee; }
        .side-heading { padding: .25rem .75rem .4rem; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #7d8ea8; }

        /* Bảng */
        table thead th { color: #374151; }

        /* Ô nhập liệu */
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--brand) !important; box-shadow: 0 0 0 3px rgba(30, 79, 143, .12) !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-[#f3f4f6] text-slate-800 antialiased">
@php
    $u = auth()->user();
    $item = 'side-link';
    $active = 'is-active';
@endphp

<div class="min-h-screen">
    {{-- ===== BACKDROP (mobile khi mở drawer) ===== --}}
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-black/40 md:hidden"></div>

    {{-- ===== SIDEBAR / DRAWER ===== --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-[#1b2a41] text-slate-200 -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0">
        <div class="flex items-center gap-2 px-6 h-16 border-b border-white/10">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-white text-[#1b2a41] text-base font-bold">L</span>
            <span class="text-lg font-semibold text-white">LMS<span class="text-slate-400">.edu</span></span>
            <button id="sidebarClose" class="ml-auto rounded-lg p-1.5 text-slate-300 hover:bg-white/10 hover:text-white md:hidden" aria-label="Đóng menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">

            @if ($u->isAdmin())
                <p class="side-heading">Quản trị viên</p>
                <a href="{{ route('admin.dashboard') }}" class="{{ $item }} {{ request()->routeIs('admin.dashboard') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Dashboard</span></a>
                <a href="{{ route('admin.users.index') }}" class="{{ $item }} {{ request()->routeIs('admin.users.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg> <span>Quản lý người dùng</span></a>
                <a href="{{ route('admin.majors.index') }}" class="{{ $item }} {{ request()->routeIs('admin.majors.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> <span>Quản lý chuyên ngành</span></a>
                <a href="{{ route('admin.subjects.index') }}" class="{{ $item }} {{ request()->routeIs('admin.subjects.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Quản lý môn học</span></a>
                <a href="{{ route('admin.classes.index') }}" class="{{ $item }} {{ request()->routeIs('admin.classes.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg> <span>Quản lý lớp học</span></a>
                <a href="{{ route('admin.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('admin.notifications.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg> <span>Thông báo hệ thống</span></a>
                <a href="{{ route('admin.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.reports.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <span>Thống kê & Báo cáo</span></a>
                <a href="{{ route('admin.risk.index') }}" class="{{ $item }} {{ request()->routeIs('admin.risk.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> <span>Cảnh báo nguy cơ (AI)</span></a>
            @elseif ($u->isTeacher())
                <p class="side-heading">Giảng viên</p>
                <a href="{{ route('teacher.dashboard') }}" class="{{ $item }} {{ request()->routeIs('teacher.dashboard') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Dashboard</span></a>
                <a href="{{ route('teacher.classes.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.classes.*') || request()->routeIs('teacher.attendance.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg> <span>Lớp của tôi</span></a>
                <a href="{{ route('teacher.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.attendance.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span>Điểm danh học sinh</span></a>
                <a href="{{ route('teacher.materials.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.materials.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Học liệu</span></a>
                <a href="{{ route('teacher.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.assignments.*') || request()->routeIs('teacher.grading.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> <span>Bài tập & Chấm bài</span></a>
                <a href="{{ route('teacher.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.notifications.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg> <span>Thông báo lớp</span></a>
                <a href="{{ route('teacher.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.analytics.*') || request()->routeIs('teacher.reports.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <span>Thống kê lớp</span></a>
                <a href="{{ route('teacher.risk.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.risk.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> <span>Cảnh báo học sinh (AI)</span></a>
            @else
                <p class="side-heading">Sinh viên</p>
                <a href="{{ route('student.dashboard') }}" class="{{ $item }} {{ request()->routeIs('student.dashboard') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg> <span>Dashboard</span></a>
                <a href="{{ route('student.schedule.index') }}" class="{{ $item }} {{ request()->routeIs('student.schedule.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <span>Thời khóa biểu</span></a>
                <a href="{{ route('student.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('student.attendance.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span>Lịch sử điểm danh</span></a>
                <a href="{{ route('student.materials.index') }}" class="{{ $item }} {{ request()->routeIs('student.materials.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> <span>Học liệu</span></a>
                <a href="{{ route('student.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('student.assignments.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> <span>Bài tập & Quiz</span></a>
                <a href="{{ route('student.grades.index') }}" class="{{ $item }} {{ request()->routeIs('student.grades.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg> <span>Bảng điểm</span></a>
                <a href="{{ route('student.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('student.notifications.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> <span>Thông báo</span></a>

                <div class="pt-3 pb-1">
                    <a href="{{ route('student.ai-tutor.index') }}" class="flex items-center gap-3 rounded-md border px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('student.ai-tutor.*') ? 'border-white/30 bg-white/10 text-white' : 'border-white/10 text-slate-200 hover:bg-white/5' }}">
                        {{-- Mr. Biết Tuốt Cute SVG Avatar --}}
                        <div class="w-8 h-8 rounded-md shrink-0 overflow-hidden bg-[#2b4a75]">
                            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                                <!-- Hat -->
                                <rect x="8" y="8" width="20" height="4" rx="2" fill="#f2c94c" opacity="0.95"/>
                                <rect x="13" y="4" width="10" height="6" rx="2" fill="#f2c94c" opacity="0.9"/>
                                <rect x="14" y="4" width="8" height="2" rx="1" fill="#e0b43a"/>
                                <!-- Face -->
                                <circle cx="18" cy="20" r="8" fill="#FFD6A5" stroke="#f2c94c" stroke-width="0.8"/>
                                <!-- Eyes -->
                                <ellipse cx="14.5" cy="19" rx="2" ry="2.3" fill="#1e4f8f"/>
                                <ellipse cx="21.5" cy="19" rx="2" ry="2.3" fill="#1e4f8f"/>
                                <circle cx="15.3" cy="18.3" r="0.8" fill="white"/>
                                <circle cx="22.3" cy="18.3" r="0.8" fill="white"/>
                                <!-- Blush -->
                                <ellipse cx="11.5" cy="22" rx="2" ry="1.2" fill="#ff9eb5" opacity="0.5"/>
                                <ellipse cx="24.5" cy="22" rx="2" ry="1.2" fill="#ff9eb5" opacity="0.5"/>
                                <!-- Smile -->
                                <path d="M15 23 Q18 25.5 21 23" stroke="#c0763a" stroke-width="1.2" stroke-linecap="round" fill="none"/>
                                <!-- Glasses -->
                                <rect x="12" y="17.5" width="4.5" height="3.5" rx="1.8" fill="none" stroke="#f2c94c" stroke-width="1" opacity="0.9"/>
                                <rect x="19.5" y="17.5" width="4.5" height="3.5" rx="1.8" fill="none" stroke="#f2c94c" stroke-width="1" opacity="0.9"/>
                                <line x1="16.5" y1="19.3" x2="19.5" y2="19.3" stroke="#f2c94c" stroke-width="0.8" opacity="0.8"/>
                                
                            </svg>
                        </div>
                        <span>Học cùng Mr. Biết Tuốt</span>
                    </a>
                </div>
            @endif

            <p class="side-heading pt-4">Tài khoản</p>
            <a href="{{ route('profile.edit') }}" class="{{ $item }} {{ request()->routeIs('profile.*') ? $active : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> <span>Hồ sơ cá nhân</span></a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                @if ($u->avatar)
                    <img src="{{ asset('storage/'.$u->avatar) }}" class="h-9 w-9 rounded-full object-cover" alt="avatar">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2b4a75] text-white text-sm font-semibold">{{ mb_substr($u->full_name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $u->full_name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $u->roleLabel() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full flex items-center justify-center gap-2 rounded-md border border-white/15 px-3 py-2 text-sm text-slate-200 hover:bg-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Đăng xuất</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== MAIN ===== --}}
    <div class="flex min-h-screen flex-col md:ml-64">
        <header class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 md:px-8">
            <div class="flex min-w-0 items-center gap-2 md:gap-3">
                <button id="sidebarToggle" class="-ml-1 rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Mở menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-base sm:text-lg font-semibold text-slate-800">@yield('title', 'LMS')</h1>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <span class="hidden sm:inline text-sm text-slate-500">{{ ucfirst(now()->isoFormat('dddd, DD/MM/YYYY')) }}</span>
                <span class="inline-flex items-center rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $u->roleLabel() }}</span>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-6">
            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Vui lòng kiểm tra lại:</p>
                    <ul class="mt-1 list-disc pl-5">
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
