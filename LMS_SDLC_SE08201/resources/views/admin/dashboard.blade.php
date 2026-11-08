@extends('layouts.app')

@section('title', 'Dashboard quản trị')

@section('content')
{{-- Global Filter Bar --}}
<div id="filterBarWrapper" class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight !text-white flex items-center gap-2.5">
                <svg class="w-6 h-6 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                <span>Dashboard Quản Trị</span>
                <span class="rounded-full bg-[#1558C9]/20 border border-[#1558C9]/40 px-3 py-0.5 text-xs font-black text-[#D4FF24] shadow-[0_0_10px_rgba(212,255,36,0.3)]">ADMIN PORTAL</span>
            </h1>
            <p class="text-xs !text-slate-300 mt-1">Giám sát toàn diện học vụ, lớp học, giảng viên và hiệu suất đào tạo</p>
        </div>
        {{-- Nút toggle bộ lọc trên mobile --}}
        <button id="mobileFilterToggleBtn" type="button"
            class="flex xl:hidden items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3.5 py-2 text-sm font-semibold text-slate-200 shadow-sm hover:bg-white/15 transition">
            <svg class="h-4 w-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <span>Bộ lọc</span>
        </button>
    </div>

    <div id="filterContainer" class="hidden xl:block">
        <form id="globalDashboardFilterForm"
            class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 glass-card p-4 rounded-2xl shadow-xl border border-white/10 w-full text-sm">
            <div class="flex items-center gap-2">
                <input type="date" name="date_from"
                    class="w-full sm:w-auto min-w-0 rounded-xl border border-white/15 bg-slate-800/90 text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 py-2 px-3 text-xs"
                    title="Từ ngày">
                <span class="text-slate-400 font-bold">-</span>
                <input type="date" name="date_to"
                    class="w-full sm:w-auto min-w-0 rounded-xl border border-white/15 bg-slate-800/90 text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 py-2 px-3 text-xs"
                    title="Đến ngày">
            </div>
            <select name="major_id"
                class="w-full sm:w-auto min-w-0 rounded-xl border border-white/15 bg-slate-800/90 text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 py-2 px-3 text-xs">
                <option value="" class="bg-slate-900 text-slate-300">Tất cả ngành</option>
                @foreach ($majors as $m)
                    <option value="{{ $m->major_id }}" class="bg-slate-900 text-white">{{ $m->major_name }}</option>
                @endforeach
            </select>
            <select name="subject_id"
                class="w-full sm:w-auto min-w-0 rounded-xl border border-white/15 bg-slate-800/90 text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 py-2 px-3 text-xs">
                <option value="" class="bg-slate-900 text-slate-300">Tất cả môn</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->subject_id }}" class="bg-slate-900 text-white">{{ $s->subject_name }}</option>
                @endforeach
            </select>
            <div class="flex-1 hidden sm:block"></div>
            <button type="reset" id="resetDashboardFilterBtn"
                class="flex items-center justify-center gap-1.5 rounded-xl border border-white/15 bg-white/10 hover:bg-white/20 px-4 py-2 text-xs font-bold text-slate-200 transition duration-200 w-full sm:w-auto">
                <svg class="h-4 w-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Làm mới bộ lọc</span>
            </button>
        </form>
    </div>
</div>

{{-- ===== KPI CARDS (5 cards hàng ngang - 3D POP-OUT & DIRECT NAVIGATION) ===== --}}
<div class="grid gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
    {{-- Card 1: Tổng người dùng --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-[#1558C9] via-indigo-700 to-blue-900 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-[#1558C9]/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('admin.users.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Tổng người dùng</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-black tracking-tight text-white">{{ number_format($total_users) }}</p>
            <p class="mt-1 text-xs text-indigo-200">{{ $total_students }} SV · {{ $total_teachers }} GV</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-indigo-100">
            <span class="font-medium group-hover:text-white">Quản lý người dùng</span>
            <span class="inline-flex items-center gap-1 font-bold text-[#D4FF24] group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>

    {{-- Card 2: Lớp học --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-emerald-900 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-emerald-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('admin.classes.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-200">Lớp học</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-black tracking-tight text-white">{{ $total_classes }}</p>
            <p class="mt-1 text-xs text-emerald-200">{{ $active_classes }} đang hoạt động</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-emerald-100">
            <span class="font-medium group-hover:text-white">Quản lý lớp học</span>
            <span class="inline-flex items-center gap-1 font-bold text-[#D4FF24] group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>

    {{-- Card 3: Môn học --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-amber-600 via-orange-600 to-amber-900 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-orange-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('admin.subjects.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-100">Môn học</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-black tracking-tight text-white">{{ $total_subjects }}</p>
            <p class="mt-1 text-xs text-amber-100">Chương trình đào tạo BTEC</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-amber-100">
            <span class="font-medium group-hover:text-white">Quản lý môn học</span>
            <span class="inline-flex items-center gap-1 font-bold text-[#D4FF24] group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>

    {{-- Card 4: Chuyên ngành --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-purple-700 via-fuchsia-800 to-slate-900 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-purple-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('admin.majors.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-fuchsia-200">Chuyên ngành</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-black tracking-tight text-white">{{ $total_majors }}</p>
            <p class="mt-1 text-xs text-fuchsia-200">Khoa / Khối ngành</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-fuchsia-100">
            <span class="font-medium group-hover:text-white">Quản lý chuyên ngành</span>
            <span class="inline-flex items-center gap-1 font-bold text-[#D4FF24] group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>

    {{-- Card 5: Chuyên cần --}}
    <div class="group relative rounded-2xl bg-gradient-to-br from-cyan-700 via-teal-700 to-blue-950 p-5 text-white shadow-xl border border-white/15 transition-all duration-300 transform hover:-translate-y-2.5 hover:scale-[1.03] hover:shadow-2xl hover:shadow-cyan-500/40 cursor-pointer overflow-hidden flex flex-col justify-between"
         onclick="window.location='{{ route('admin.analytics.index') }}'">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition duration-500"></div>
        <div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-cyan-200">Chuyên cần</p>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/25 text-white shadow-lg group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                    <svg class="w-6 h-6 text-[#D4FF24] filter drop-shadow-[0_0_8px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-black tracking-tight text-white">{{ $attendance_rate !== null ? $attendance_rate.'%' : '—' }}</p>
            <p class="mt-1 text-xs text-cyan-200">Tỷ lệ toàn trường</p>
        </div>
        <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-cyan-100">
            <span class="font-medium group-hover:text-white">Báo cáo chuyên cần</span>
            <span class="inline-flex items-center gap-1 font-bold text-[#D4FF24] group-hover:translate-x-1.5 transition duration-200">Chi tiết →</span>
        </div>
    </div>
</div>

{{-- ===== LAYOUT 2 CỘT: KHUNG TRÁI (8/12) & KHUNG PHẢI (4/12) ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    {{-- ===== KHUNG TRÁI (8/12) ===== --}}
    <div class="lg:col-span-8 space-y-6">

        {{-- 1. Line Chart: Xu hướng điểm TB --}}
        <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 border border-indigo-400/30 text-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(129,140,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    </div>
                    <span>Xu hướng điểm trung bình toàn trường</span>
                </h2>
                <a href="{{ route('admin.analytics.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition duration-200">
                    <span>Xem báo cáo</span>
                    <span class="group-hover:translate-x-1 transition duration-200">→</span>
                </a>
            </div>
            <div class="relative w-full" style="height:260px">
                <canvas id="scoreTrendChart"></canvas>
            </div>
        </div>

        {{-- 2+3. Hai Doughnut cạnh nhau --}}
        <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
            {{-- Doughnut: Phân bố điểm --}}
            <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-white flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/20 border border-emerald-400/30 text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        </div>
                        <span>Phân bố điểm số</span>
                    </h2>
                </div>
                <div class="relative w-full" style="height:240px">
                    <canvas id="scoreDistChart"></canvas>
                </div>
            </div>

            {{-- Doughnut: SV theo ngành --}}
            <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-white flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-500/20 border border-purple-400/30 text-purple-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <span>Sinh viên theo chuyên ngành</span>
                    </h2>
                </div>
                <div class="relative w-full" style="height:240px">
                    <canvas id="studentMajorChart"></canvas>
                </div>
            </div>
        </div>

        {{-- 4. Horizontal Bar: Top môn điểm cao --}}
        <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/20 border border-amber-400/30 text-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(245,158,11,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <span>Top môn học có điểm trung bình cao nhất</span>
                </h2>
                <a href="{{ route('admin.subjects.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-amber-400 hover:text-amber-300 transition duration-200">
                    <span>Xem tất cả môn</span>
                    <span class="group-hover:translate-x-1 transition duration-200">→</span>
                </a>
            </div>
            <div class="relative w-full" style="height:280px">
                <canvas id="topSubjectsChart"></canvas>
            </div>
        </div>

        {{-- 5. Pie: Điểm danh --}}
        <div class="w-full glass-card p-6 border border-white/10 shadow-xl overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
                <div class="w-full">
                    <h2 class="font-bold text-white mb-4 flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-500/20 border border-cyan-400/30 text-cyan-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span>Tỷ lệ điểm danh toàn hệ thống</span>
                    </h2>
                    <div class="relative w-full" style="height:200px">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-400/20 hover:scale-[1.02] transition duration-200">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500 text-white text-sm font-black shadow-md shadow-emerald-500/30">P</span>
                        <div>
                            <p class="text-xs text-slate-300">Có mặt</p>
                            <p class="text-xl font-black text-emerald-400" id="attTextP">0</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-rose-500/15 border border-rose-400/20 hover:scale-[1.02] transition duration-200">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-500 text-white text-sm font-black shadow-md shadow-rose-500/30">A</span>
                        <div>
                            <p class="text-xs text-slate-300">Vắng mặt</p>
                            <p class="text-xl font-black text-rose-400" id="attTextA">0</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-amber-500/15 border border-amber-400/20 hover:scale-[1.02] transition duration-200">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500 text-white text-sm font-black shadow-md shadow-amber-500/30">L</span>
                        <div>
                            <p class="text-xs text-slate-300">Đi muộn</p>
                            <p class="text-xl font-black text-amber-400" id="attTextL">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Bảng lịch học hôm nay (KHÔNG CẮT CHỮ - CUỘN MƯỢT MÀ) --}}
        <div class="w-full glass-card border border-white/10 shadow-xl overflow-hidden">
            <div class="border-b border-white/10 px-6 py-4 flex items-center justify-between">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/20 border border-sky-400/30 text-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(56,189,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span>Lịch học hôm nay trên toàn trường</span>
                </h2>
                <a href="{{ route('admin.classes.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-sky-400 hover:text-sky-300 transition duration-200">
                    <span>Xem lớp học</span>
                    <span class="group-hover:translate-x-1 transition duration-200">→</span>
                </a>
            </div>
            <div class="w-full overflow-x-auto max-h-[460px] overflow-y-auto pr-1 pb-2 scroll-smooth">
                <table class="w-full min-w-full text-sm">
                    <thead class="bg-white/5 text-slate-300 border-b border-white/10 sticky top-0 z-10 backdrop-blur-md">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">STT</th>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">Ca học / Giờ</th>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">Lớp</th>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">Môn học</th>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">Phòng</th>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">Giảng viên</th>
                            <th class="px-5 py-3 text-left font-bold whitespace-nowrap">Trạng thái điểm danh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($today_sessions as $i => $s)
                            <tr class="hover:bg-white/5 hover:translate-x-1 transition-all duration-200">
                                <td class="px-5 py-3.5 !text-slate-300 font-medium">{{ $i + 1 }}</td>
                                <td class="px-5 py-3.5">
                                    @if ($s->slot)
                                        <span class="inline-flex items-center rounded-lg bg-indigo-500/20 border border-indigo-400/30 px-2.5 py-1 text-xs font-bold text-indigo-300 whitespace-nowrap">
                                            {{ $s->slot->label }} ({{ \Carbon\Carbon::parse($s->slot->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->slot->end_time)->format('H:i') }})
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 font-bold !text-white whitespace-nowrap">{{ $s->classRoom?->class_name }}</td>
                                <td class="px-5 py-3.5 !text-slate-200">{{ $s->classRoom?->subject?->subject_name }}</td>
                                <td class="px-5 py-3.5 !text-slate-200 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-md bg-white/10 border border-white/10 text-xs !text-slate-200">{{ $s->room?->room_name ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3.5 !text-slate-200 whitespace-nowrap">{{ $s->classRoom?->teacher?->full_name }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($s->attendances && $s->attendances->count() > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 px-2.5 py-0.5 text-xs font-bold text-emerald-300 shadow-sm">
                                            <svg class="h-3 w-3 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span>Đã điểm danh ({{ $s->attendances->count() }} SV)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/20 border border-amber-400/30 px-2.5 py-0.5 text-xs font-bold text-amber-300 shadow-sm">
                                            <svg class="h-3 w-3 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Chưa điểm danh</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg class="w-8 h-8 text-sky-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Hôm nay không có lịch học nào.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== KHUNG PHẢI (4/12) ===== --}}
    <div class="lg:col-span-4 space-y-6">

        {{-- 1. Thông báo hệ thống --}}
        <div class="glass-card border border-white/10 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/20 border border-sky-400/30 text-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(56,189,248,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <span>Thông báo hệ thống</span>
                </h2>
                <a href="{{ route('admin.notifications.index') }}" class="group flex items-center gap-1 text-xs font-semibold text-sky-400 hover:text-sky-300 transition duration-200">
                    <span>Quản lý</span>
                    <span class="group-hover:translate-x-1 transition duration-200">→</span>
                </a>
            </div>
            <ul class="divide-y divide-white/5 max-h-72 overflow-y-auto pr-1">
                @forelse ($recent_notifications as $n)
                    <li class="px-6 py-3.5 hover:bg-white/5 hover:translate-x-1.5 transition-all duration-200 cursor-pointer"
                        onclick="window.location='{{ route('admin.notifications.index') }}'">
                        <div class="flex items-start gap-2.5">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-400/30 text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold !text-white line-clamp-1">{{ $n->title }}
                                    @if ($n->isSystemWide()) <span class="ml-1 rounded-md bg-[#1558C9]/30 border border-[#1558C9]/50 px-1.5 py-0.5 text-[10px] font-semibold text-[#D4FF24]">Toàn trường</span> @endif
                                </p>
                                <p class="text-xs !text-slate-300 mt-0.5">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm !text-slate-400">Chưa có thông báo</li>
                @endforelse
            </ul>
        </div>

        {{-- 2. Bài tập sắp đến hạn --}}
        <div class="glass-card border border-white/10 shadow-xl overflow-hidden">
            <div class="border-b border-white/10 px-6 py-4 flex items-center justify-between">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/20 border border-rose-400/30 text-rose-400 shadow-[0_0_10px_rgba(244,63,94,0.3)]">
                        <svg class="w-4 h-4 filter drop-shadow-[0_0_5px_rgba(251,113,133,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span>Bài tập sắp đến hạn</span>
                </h2>
                <span class="text-xs !text-slate-300">Toàn trường</span>
            </div>
            <ul class="divide-y divide-white/5 max-h-80 overflow-y-auto pr-1">
                @forelse ($upcoming_assignments as $a)
                    <li class="px-6 py-3.5 hover:bg-white/5 hover:translate-x-1.5 transition-all duration-200">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold !text-white truncate">{{ $a->title }}</p>
                                <p class="text-xs !text-slate-300 mt-0.5">{{ $a->classRoom?->class_name }} · GV: {{ $a->classRoom?->teacher?->full_name }}</p>
                            </div>
                            @php
                            $hoursLeft = now()->diffInHours($a->due_date, false);
                            @endphp
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold border {{ $hoursLeft < 24 ? 'bg-rose-500/20 border-rose-400/30 text-rose-300 animate-pulse' : ($hoursLeft < 72 ? 'bg-amber-500/20 border-amber-400/30 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300') }}">
                                {{ $a->due_date->diffForHumans() }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-400">Không có bài tập sắp hạn</li>
                @endforelse
            </ul>
        </div>

        {{-- 3. Trạng thái hệ thống (POP-OUT & VECTOR ICONS) --}}
        <div class="glass-card border border-white/10 shadow-xl overflow-hidden">
            <div class="border-b border-white/10 px-6 py-4 flex items-center justify-between">
                <h2 class="font-bold text-white flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/20 border border-emerald-400/30 text-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.3)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                    </div>
                    <span>Trạng thái hệ thống</span>
                </h2>
                <span class="flex items-center gap-1.5 text-xs text-emerald-400 font-bold">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Live</span>
                </span>
            </div>
            <div class="p-4 space-y-2.5">
                {{-- Item 1: Lớp đang hoạt động --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-400/20 hover:translate-x-1.5 hover:bg-emerald-500/20 transition-all duration-200 cursor-pointer"
                     onclick="window.location='{{ route('admin.classes.index') }}'">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-[#D4FF24] border border-emerald-400/30 flex items-center justify-center">
                            <svg class="w-4 h-4 filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        </div>
                        <span class="text-sm font-semibold !text-slate-100">Lớp đang hoạt động</span>
                    </div>
                    <span class="text-lg font-black text-emerald-400">{{ $system_status['active_classes'] }}</span>
                </div>

                {{-- Item 2: Bài tập đang mở --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-400/20 hover:translate-x-1.5 hover:bg-indigo-500/20 transition-all duration-200 cursor-pointer"
                     onclick="window.location='{{ route('admin.classes.index') }}'">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-[#D4FF24] border border-indigo-400/30 flex items-center justify-center">
                            <svg class="w-4 h-4 filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-sm font-semibold !text-slate-100">Bài tập đang mở</span>
                    </div>
                    <span class="text-lg font-black text-indigo-400">{{ $system_status['open_assignments'] }}</span>
                </div>

                {{-- Item 3: Bài chờ chấm --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-amber-500/10 border border-amber-400/20 hover:translate-x-1.5 hover:bg-amber-500/20 transition-all duration-200 cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-[#D4FF24] border border-amber-400/30 flex items-center justify-center">
                            <svg class="w-4 h-4 filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-sm font-semibold !text-slate-100">Bài chờ chấm điểm</span>
                    </div>
                    <span class="text-lg font-black text-amber-400">{{ $system_status['pending_grading'] }}</span>
                </div>

                {{-- Item 4: Thảo luận mới hôm nay --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-cyan-500/10 border border-cyan-400/20 hover:translate-x-1.5 hover:bg-cyan-500/20 transition-all duration-200 cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-500/20 text-[#D4FF24] border border-cyan-400/30 flex items-center justify-center">
                            <svg class="w-4 h-4 filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <span class="text-sm font-semibold !text-slate-100">Thảo luận mới hôm nay</span>
                    </div>
                    <span class="text-lg font-black text-cyan-400">{{ $system_status['today_discussions'] }}</span>
                </div>

                {{-- Item 5: Người dùng mới (7 ngày) --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-fuchsia-500/10 border border-fuchsia-400/20 hover:translate-x-1.5 hover:bg-fuchsia-500/20 transition-all duration-200 cursor-pointer"
                     onclick="window.location='{{ route('admin.users.index') }}'">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-fuchsia-500/20 text-[#D4FF24] border border-fuchsia-400/30 flex items-center justify-center">
                            <svg class="w-4 h-4 filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <span class="text-sm font-semibold !text-slate-100">Người dùng mới (7 ngày)</span>
                    </div>
                    <span class="text-lg font-black text-fuchsia-400">{{ $system_status['new_users_7days'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.register(ChartDataLabels);

    const pieDatalabelsConfig = {
        anchor: 'center',
        align: 'center',
        color: '#fff',
        font: { weight: 'bold', size: 10 },
        formatter: (value, ctx) => {
            if (!value) return ''; 
            let sum = 0;
            let dataArr = ctx.chart.data.datasets[0].data;
            dataArr.map(data => { sum += Number(data); });
            if (sum === 0) return value;
            return (value * 100 / sum).toFixed(1) + "%";
        }
    };

    // === 1. Line Chart: Xu hướng điểm TB ===
    const scoreTrendCtx = document.getElementById('scoreTrendChart');
    let scoreTrendChart = new Chart(scoreTrendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Điểm TB',
                data: [],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderWidth: 3,
                pointBackgroundColor: '#818cf8',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#0f172a', 
                    borderColor: 'rgba(99, 102, 241, 0.4)',
                    borderWidth: 1,
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    padding: 10, 
                    cornerRadius: 8 
                },
                datalabels: { display: false }
            },
            scales: {
                x: { 
                    grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                    ticks: { font: { size: 11 }, color: '#94a3b8' } 
                },
                y: { 
                    beginAtZero: true, 
                    max: 10, 
                    grid: { color: 'rgba(255, 255, 255, 0.08)' }, 
                    ticks: { font: { size: 11 }, color: '#94a3b8' } 
                }
            }
        }
    });

    const dashboardForm = document.getElementById('globalDashboardFilterForm');

    // === Khởi tạo 4 biểu đồ rỗng ===
    const legendPos = window.innerWidth < 1024 ? 'bottom' : 'right';
    const scoreDistChart = new Chart(document.getElementById('scoreDistChart'), {
        type: 'doughnut',
        data: { 
            labels: [], 
            datasets: [{ 
                data: [], 
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e', '#6366f1'], 
                borderWidth: 2, 
                borderColor: '#0f172a' 
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            layout: { padding: 10 }, 
            plugins: { 
                legend: { 
                    position: legendPos, 
                    labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 }, color: '#cbd5e1' } 
                }, 
                tooltip: { backgroundColor: '#0f172a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 10, cornerRadius: 8 }, 
                datalabels: pieDatalabelsConfig 
            }, 
            cutout: '55%' 
        }
    });

    const majorColors = ['#6366f1', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#14b8a6', '#ef4444', '#84cc16'];
    const studentMajorChart = new Chart(document.getElementById('studentMajorChart'), {
        type: 'doughnut',
        data: { 
            labels: [], 
            datasets: [{ 
                data: [], 
                backgroundColor: majorColors, 
                borderWidth: 2, 
                borderColor: '#0f172a' 
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            layout: { padding: 10 }, 
            plugins: { 
                legend: { 
                    position: legendPos, 
                    labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 }, color: '#cbd5e1' } 
                }, 
                tooltip: { backgroundColor: '#0f172a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 10, cornerRadius: 8 }, 
                datalabels: pieDatalabelsConfig 
            }, 
            cutout: '55%' 
        }
    });

    const topSubjectsChart = new Chart(document.getElementById('topSubjectsChart'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Điểm TB', data: [], backgroundColor: [], borderRadius: 8, barThickness: 22 }] },
        options: { 
            indexAxis: 'y', 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { display: false }, 
                tooltip: { backgroundColor: '#0f172a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 10, cornerRadius: 8 }, 
                datalabels: { display: false } 
            }, 
            scales: { 
                x: { beginAtZero: true, max: 10, grid: { color: 'rgba(255,255,255,0.08)' }, ticks: { color: '#94a3b8' } }, 
                y: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#cbd5e1' } } 
            } 
        }
    });

    const attendanceChart = new Chart(document.getElementById('attendanceChart'), {
        type: 'pie',
        data: { 
            labels: ['Có mặt (P)', 'Vắng (A)', 'Muộn (L)'], 
            datasets: [{ 
                data: [0, 0, 0], 
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'], 
                borderWidth: 2, 
                borderColor: '#0f172a' 
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            layout: { padding: 10 }, 
            plugins: { 
                legend: { 
                    position: legendPos, 
                    labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 }, color: '#cbd5e1' } 
                }, 
                tooltip: { backgroundColor: '#0f172a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 10, cornerRadius: 8 }, 
                datalabels: pieDatalabelsConfig 
            } 
        }
    });

    function loadDashboardCharts() {
        const query = new URLSearchParams(new FormData(dashboardForm)).toString();
        fetch("{{ route('admin.dashboard.charts-data') }}?" + query)
            .then(res => res.json())
            .then(data => {
                // 1. Trend
                scoreTrendChart.data.labels = data.scoreTrend.labels;
                scoreTrendChart.data.datasets[0].data = data.scoreTrend.scores;
                scoreTrendChart.update();

                // 2. Score Dist
                scoreDistChart.data.labels = Object.keys(data.scoreDist);
                scoreDistChart.data.datasets[0].data = Object.values(data.scoreDist);
                scoreDistChart.update();

                // 3. Students by Major
                studentMajorChart.data.labels = data.studentsMajor.labels;
                studentMajorChart.data.datasets[0].data = data.studentsMajor.counts;
                studentMajorChart.data.datasets[0].backgroundColor = majorColors.slice(0, data.studentsMajor.labels.length);
                studentMajorChart.update();

                // 4. Top Subjects
                topSubjectsChart.data.labels = data.topSubjects.labels;
                topSubjectsChart.data.datasets[0].data = data.topSubjects.scores;
                topSubjectsChart.data.datasets[0].backgroundColor = data.topSubjects.scores.map((_, i) => majorColors[i % majorColors.length]);
                topSubjectsChart.update();

                // 5. Attendance
                attendanceChart.data.datasets[0].data = [data.attendance.P, data.attendance.A, data.attendance.L];
                attendanceChart.update();
                document.getElementById('attTextP').innerText = new Intl.NumberFormat().format(data.attendance.P);
                document.getElementById('attTextA').innerText = new Intl.NumberFormat().format(data.attendance.A);
                document.getElementById('attTextL').innerText = new Intl.NumberFormat().format(data.attendance.L);
            })
            .catch(err => console.error(err));
    }
    
    // Gọi Ajax lấy data lần đầu
    loadDashboardCharts();

    // Auto submit lọc khi form change
    dashboardForm.addEventListener('change', loadDashboardCharts);

    const dashboardMajorSelect = dashboardForm.querySelector('[name="major_id"]');
    const dashboardSubjectSelect = dashboardForm.querySelector('[name="subject_id"]');

    function fetchDashboardFilterOptions() {
        const query = new URLSearchParams(new FormData(dashboardForm)).toString();
        fetch("{{ route('admin.analytics.filter-options') }}?" + query)
            .then(res => res.json())
            .then(data => {
                const currentSubject = dashboardSubjectSelect.value;
                dashboardSubjectSelect.innerHTML = '<option value="" class="bg-slate-900 text-slate-300">Tất cả môn</option>';
                data.subjects.forEach(s => {
                    let opt = new Option(s.subject_name, s.subject_id);
                    if (s.subject_id == currentSubject) opt.selected = true;
                    opt.className = 'bg-slate-900 text-white';
                    dashboardSubjectSelect.add(opt);
                });
            })
            .catch(err => console.error('Lỗi filter options:', err));
    }

    if(dashboardMajorSelect) {
        dashboardMajorSelect.addEventListener('change', fetchDashboardFilterOptions);
    }

    // Mobile filter toggle
    const mobileFilterToggleBtn = document.getElementById('mobileFilterToggleBtn');
    const filterContainer = document.getElementById('filterContainer');
    if(mobileFilterToggleBtn && filterContainer) {
        mobileFilterToggleBtn.addEventListener('click', () => {
            filterContainer.classList.toggle('hidden');
        });
    }

    // Reset bộ lọc
    const resetDashboardFilterBtn = document.getElementById('resetDashboardFilterBtn');
    if(resetDashboardFilterBtn && dashboardSubjectSelect) {
        resetDashboardFilterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            dashboardForm.reset();
            dashboardSubjectSelect.innerHTML = '<option value="" class="bg-slate-900 text-slate-300">Tất cả môn</option>';
            loadDashboardCharts();
        });
    }
});
</script>
@endpush