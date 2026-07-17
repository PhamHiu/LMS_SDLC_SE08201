<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LMS') — LMS.edu</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        img, svg, video, canvas, iframe { max-width: 100%; height: auto; }
        table { width: 100%; }
        /* Chuyển động mượt cho drawer & tương tác */
        #sidebar { will-change: transform; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
@php($u = auth()->user())

<div class="min-h-screen">
    {{-- ===== BACKDROP (mobile khi mở drawer) ===== --}}
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm md:hidden"></div>

    {{-- ===== SIDEBAR / DRAWER ===== --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gradient-to-b from-indigo-950 via-indigo-900 to-indigo-950 text-indigo-100 -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0">
        <div class="flex items-center gap-2 px-6 h-16 border-b border-white/10">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 text-white text-lg font-extrabold">L</span>
            <span class="text-xl font-extrabold text-white tracking-tight">LMS<span class="text-indigo-300">.edu</span></span>
            <button id="sidebarClose" class="ml-auto rounded-lg p-1.5 text-indigo-200 hover:bg-white/10 hover:text-white md:hidden" aria-label="Đóng menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
            @php($item = 'flex items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white')
            @php($active = 'bg-indigo-600 text-white font-semibold shadow')

            @if ($u->isAdmin())
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-indigo-400">Quản trị viên</p>
                <a href="{{ route('admin.dashboard') }}" class="{{ $item }} {{ request()->routeIs('admin.dashboard') ? $active : '' }}">📊 <span>Dashboard</span></a>
                <a href="{{ route('admin.users.index') }}" class="{{ $item }} {{ request()->routeIs('admin.users.*') ? $active : '' }}">👥 <span>Quản lý người dùng</span></a>
                <a href="{{ route('admin.majors.index') }}" class="{{ $item }} {{ request()->routeIs('admin.majors.*') ? $active : '' }}">🏛️ <span>Quản lý chuyên ngành</span></a>
                <a href="{{ route('admin.subjects.index') }}" class="{{ $item }} {{ request()->routeIs('admin.subjects.*') ? $active : '' }}">📚 <span>Quản lý môn học</span></a>
                <a href="{{ route('admin.classes.index') }}" class="{{ $item }} {{ request()->routeIs('admin.classes.*') ? $active : '' }}">🏫 <span>Quản lý lớp học</span></a>
                <a href="{{ route('admin.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('admin.notifications.*') ? $active : '' }}">📢 <span>Thông báo hệ thống</span></a>
                <a href="{{ route('admin.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.reports.*') ? $active : '' }}">📈 <span>Thống kê & Báo cáo</span></a>
            @elseif ($u->isTeacher())
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-indigo-400">Giảng viên</p>
                <a href="{{ route('teacher.dashboard') }}" class="{{ $item }} {{ request()->routeIs('teacher.dashboard') ? $active : '' }}">📊 <span>Dashboard</span></a>
                <a href="{{ route('teacher.classes.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.classes.*') || request()->routeIs('teacher.attendance.*') ? $active : '' }}">🏫 <span>Lớp của tôi</span></a>
                <a href="{{ route('teacher.materials.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.materials.*') ? $active : '' }}">📚 <span>Học liệu</span></a>
                <a href="{{ route('teacher.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.assignments.*') || request()->routeIs('teacher.grading.*') ? $active : '' }}">📝 <span>Bài tập & Chấm bài</span></a>
                <a href="{{ route('teacher.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.notifications.*') ? $active : '' }}">📢 <span>Thông báo lớp</span></a>
                <a href="{{ route('teacher.analytics.index') }}" class="{{ $item }} {{ request()->routeIs('teacher.analytics.*') || request()->routeIs('teacher.reports.*') ? $active : '' }}">📈 <span>Thống kê lớp</span></a>
            @else
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-indigo-400">Sinh viên</p>
                <a href="{{ route('student.dashboard') }}" class="{{ $item }} {{ request()->routeIs('student.dashboard') ? $active : '' }}">📊 <span>Dashboard</span></a>
                <a href="{{ route('student.schedule.index') }}" class="{{ $item }} {{ request()->routeIs('student.schedule.*') ? $active : '' }}">🗓️ <span>Thời khóa biểu</span></a>
                <a href="{{ route('student.attendance.index') }}" class="{{ $item }} {{ request()->routeIs('student.attendance.*') ? $active : '' }}">✅ <span>Lịch sử điểm danh</span></a>
                <a href="{{ route('student.materials.index') }}" class="{{ $item }} {{ request()->routeIs('student.materials.*') ? $active : '' }}">📚 <span>Học liệu</span></a>
                <a href="{{ route('student.assignments.index') }}" class="{{ $item }} {{ request()->routeIs('student.assignments.*') ? $active : '' }}">📝 <span>Bài tập & Quiz</span></a>
                <a href="{{ route('student.grades.index') }}" class="{{ $item }} {{ request()->routeIs('student.grades.*') ? $active : '' }}">🏆 <span>Bảng điểm</span></a>
                <a href="{{ route('student.notifications.index') }}" class="{{ $item }} {{ request()->routeIs('student.notifications.*') ? $active : '' }}">🔔 <span>Thông báo</span></a>
            @endif

            <p class="px-3 pt-4 pb-2 text-[11px] font-bold uppercase tracking-wider text-indigo-400">Tài khoản</p>
            <a href="{{ route('profile.edit') }}" class="{{ $item }} {{ request()->routeIs('profile.*') ? $active : '' }}">👤 <span>Hồ sơ cá nhân</span></a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                @if ($u->avatar)
                    <img src="{{ asset('storage/'.$u->avatar) }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-indigo-400" alt="avatar">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500 text-white font-bold">{{ mb_substr($u->full_name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $u->full_name }}</p>
                    <p class="truncate text-xs text-indigo-300">{{ $u->roleLabel() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-indigo-100 transition hover:bg-rose-600 hover:text-white">
                    ⏻ Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== MAIN ===== --}}
    <div class="flex min-h-screen flex-col md:ml-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/90 backdrop-blur px-4 md:px-8">
            <div class="flex min-w-0 items-center gap-2 md:gap-3">
                <button id="sidebarToggle" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Mở menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-base sm:text-lg md:text-xl font-bold text-slate-800">@yield('title', 'LMS')</h1>
            </div>
            <div class="flex shrink-0 items-center gap-2 md:gap-3 text-sm text-slate-500">
                <span class="hidden sm:inline">{{ now()->isoFormat('dddd, DD/MM/YYYY') }}</span>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 md:px-3">{{ $u->roleLabel() }}</span>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <span>✅</span><span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <span>⚠️</span><span>{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
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
@stack('scripts')
</body>
</html>
