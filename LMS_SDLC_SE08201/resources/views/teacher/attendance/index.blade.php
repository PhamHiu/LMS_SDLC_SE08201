@extends('layouts.app')

@section('title', 'Quản lý Điểm danh theo Lớp & Buổi học')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-bold text-lg">✅</span>
                <h1 class="text-2xl font-black tracking-tight text-slate-800">Quản lý Điểm danh theo Lớp & Buổi học</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Điểm danh từng buổi học, theo dõi chuyên cần thời gian thực và tự động liên kết với <span class="font-semibold text-indigo-600">Hệ thống AI Cảnh báo nguy cơ bỏ học</span>.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('teacher.risk.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition shadow-sm">
                ⚠️ Xem Dashboard Cảnh báo AI
            </a>
            <a href="{{ route('teacher.classes.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                🏫 Quản lý Lớp học
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 flex items-center gap-2 shadow-sm">
            <span>🎉</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Bộ chọn Lớp học (Class Selector Pills) -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <span>📚</span> Chọn Lớp học để Điểm danh:
            </h2>
            <span class="text-xs text-slate-400">Bạn đang phụ trách <strong>{{ $classes->count() }}</strong> lớp học</span>
        </div>

        <div class="mt-4 flex flex-wrap gap-2.5">
            @forelse ($classes as $cls)
                @php
                    $isSelected = $selectedClass && $selectedClass->class_id === $cls->class_id;
                @endphp
                <a href="{{ route('teacher.attendance.index', ['class_id' => $cls->class_id]) }}"
                   class="group relative flex items-center gap-3 rounded-xl px-4 py-3 border transition-all {{ $isSelected ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900 ring-2 ring-emerald-500/20 shadow-sm' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-100/70 text-slate-700' }}">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $isSelected ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }} font-bold text-xs">
                        {{ mb_substr($cls->class_name, 0, 2) }}
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-bold leading-tight">{{ $cls->class_name }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $cls->subject->subject_code ?? 'Môn học' }} · {{ $cls->students_count }} sinh viên</p>
                    </div>
                    @if ($isSelected)
                        <span class="ml-1 inline-block h-2 w-2 rounded-full bg-emerald-600"></span>
                    @endif
                </a>
            @empty
                <div class="w-full py-8 text-center text-sm text-slate-400">
                    Bạn chưa được phân công lớp học nào.
                </div>
            @endforelse
        </div>
    </div>

    @if ($selectedClass)
        <!-- Chi tiết thông tin lớp được chọn -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-extrabold text-indigo-700">{{ $selectedClass->subject->subject_code ?? 'SUBJECT' }}</span>
                        <h2 class="text-lg font-bold text-slate-800">{{ $selectedClass->class_name }} — {{ $selectedClass->subject->subject_name ?? '' }}</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Tổng số: <strong>{{ $selectedClass->total_sessions }} buổi học</strong> · Sĩ số lớp: <strong>{{ $selectedClass->students()->count() }} sinh viên</strong>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.classes.show', $selectedClass) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                        👁️ Xem hồ sơ lớp
                    </a>
                </div>
            </div>

            <!-- Danh sách buổi học (Session List) -->
            <div class="mt-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-1.5">
                    <span>🗓️</span> Danh sách các Buổi học trong Kế hoạch đào tạo
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($sessions as $index => $sess)
                        @php
                            $isDone = ($sess->total_attended ?? 0) > 0;
                            $sessNumber = $index + 1;
                            $roomLabel = $sess->room?->room_name ?? 'Phòng Lab';
                            $slotLabel = $sess->slot?->label ?? 'Ca học';
                        @endphp
                        <div class="flex flex-col justify-between rounded-xl border {{ $isDone ? 'border-slate-200 bg-white' : 'border-dashed border-slate-300 bg-slate-50/50' }} p-4.5 hover:shadow-md transition">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg {{ $isDone ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-bold">
                                        <span>Buổi #{{ $sessNumber }}</span>
                                    </span>
                                    @if ($isDone)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100/80 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                                            ✓ Đã điểm danh
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100/80 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                            ⏳ Chưa điểm danh
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-3">
                                    <p class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                        <span>📅</span> {{ $sess->session_date->format('d/m/Y') }}
                                        <span class="text-xs font-normal text-slate-400">({{ $sess->session_date->locale('vi')->dayName }})</span>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                                        <span>📍 {{ $roomLabel }}</span>
                                        <span>⏰ {{ $slotLabel }}</span>
                                    </p>
                                </div>

                                @if ($isDone)
                                    <div class="mt-3 flex items-center gap-3 pt-3 border-t border-slate-100 text-xs">
                                        <span class="font-semibold text-emerald-600">✓ {{ $sess->present_count ?? 0 }} Có mặt</span>
                                        <span class="font-semibold text-rose-600">✗ {{ $sess->absent_count ?? 0 }} Vắng</span>
                                        @if (($sess->late_count ?? 0) > 0)
                                            <span class="font-semibold text-amber-600">⚠ {{ $sess->late_count }} Muộn</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100">
                                <a href="{{ route('teacher.attendance.edit', [$selectedClass, $sess]) }}"
                                   class="flex items-center justify-center gap-2 w-full rounded-xl {{ $isDone ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-600 text-white hover:bg-emerald-700' }} py-2.5 text-xs font-bold transition shadow-sm">
                                    <span>{{ $isDone ? '✏️ Cập nhật điểm danh' : '✅ Bắt đầu điểm danh ngay' }}</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-sm text-slate-400">
                            Lớp học này chưa có lịch buổi học nào trong hệ thống.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
