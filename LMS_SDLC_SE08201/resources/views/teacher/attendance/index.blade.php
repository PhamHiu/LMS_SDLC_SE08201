@extends('layouts.app')

@section('title', 'Quản lý Điểm danh theo Lớp & Buổi học')

@section('content')
@php
    $rt = $realtimeInfo ?? app(\App\Services\Attendance\RealtimeScheduleService::class)->getCurrentTimeInfo();
    $todayDateStr = $rt['today']->format('Y-m-d');
@endphp

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 font-bold text-lg">✓</span>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800">Quản lý Điểm danh theo Lớp & Buổi học</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Xác định thời gian thực chuẩn xác, tự động nhận diện ca học và liên kết với <span class="font-semibold text-[#1e4f8f]">Hệ thống AI Cảnh báo nguy cơ bỏ học</span>.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('teacher.risk.index') }}" class="inline-flex items-center gap-2 rounded-md border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition shadow-sm">
                ⚠️ Xem Dashboard Cảnh báo AI
            </a>
            <a href="{{ route('teacher.classes.index') }}" class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                Quản lý Lớp học
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 flex items-center gap-2 shadow-sm">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- THANH THỜI GIAN THỰC HỆ THỐNG & THEO DÕI CA HỌC LIVE (SYSTEM REAL-TIME)    -->
    <!-- ========================================================================= -->
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Đồng hồ thời gian thực điện tử (Live Clock) -->
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white font-bold text-xl shadow-sm">
                    🕒
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Thời gian thực hệ thống:</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-pulse"></span> LIVE
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span id="liveRealtimeDate" class="text-base font-bold text-slate-800">{{ $rt['day_name'] }}, {{ $rt['formatted_date'] }}</span>
                        <span id="liveRealtimeClock" class="font-mono text-xl font-extrabold text-[#1e4f8f]">{{ $rt['formatted_time'] }}</span>
                        <span class="text-xs font-medium text-slate-400">({{ $rt['timezone'] }})</span>
                    </div>
                </div>
            </div>

            <!-- Tình trạng Ca học thời gian thực -->
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                @if ($rt['current_slot'])
                    <div class="inline-flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3.5 py-2 font-semibold text-emerald-900 shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-emerald-600 animate-ping"></span>
                        <span>Đang trong <strong>Ca {{ $rt['current_slot']->slot_number }} ({{ substr($rt['current_slot']->start_time, 0, 5) }} – {{ substr($rt['current_slot']->end_time, 0, 5) }})</strong></span>
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3.5 py-2 font-medium text-slate-700 shadow-sm">
                        <span>⏰ Ngoài giờ học chính khóa</span>
                        @if ($rt['upcoming_slot'])
                            <span class="text-slate-400">·</span>
                            <span>Ca tiếp theo: <strong>Ca {{ $rt['upcoming_slot']->slot_number }} ({{ substr($rt['upcoming_slot']->start_time, 0, 5) }})</strong></span>
                        @endif
                    </div>
                @endif

                @if ($rt['active_slot'])
                    <div class="inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 px-3 py-2 font-bold text-[#1e4f8f] shadow-sm">
                        <span>✓ Đang mở điểm danh Ca {{ $rt['active_slot']->slot_number }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Bộ chọn Lớp học (Class Selector Pills) -->
    <div class="rounded-lg border border-slate-200/80 bg-white p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                Chọn Lớp học để Điểm danh:
            </h2>
            <span class="text-xs text-slate-500">Bạn đang phụ trách <strong>{{ $classes->count() }}</strong> lớp học</span>
        </div>

        <div class="mt-4 flex flex-wrap gap-2.5">
            @forelse ($classes as $cls)
                @php
                    $isSelected = $selectedClass && $selectedClass->class_id === $cls->class_id;
                @endphp
                <a href="{{ route('teacher.attendance.index', ['class_id' => $cls->class_id]) }}"
                   class="group relative flex items-center gap-3 rounded-md px-4 py-3 border transition-all {{ $isSelected ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900 ring-2 ring-emerald-500/20 shadow-sm' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-100/70 text-slate-700' }}">
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
                <div class="w-full py-8 text-center text-sm text-slate-500">
                    Bạn chưa được phân công lớp học nào.
                </div>
            @endforelse
        </div>
    </div>

    @if ($selectedClass)
        @php
            $todayCount = $sessions->filter(fn($s) => $s->isToday())->count();
            $doneCount = $sessions->filter(fn($s) => $s->isLearnedAndMarked())->count();
            $futureCount = $sessions->filter(fn($s) => $s->isFutureSession())->count();
        @endphp

        <!-- Chi tiết thông tin lớp được chọn -->
        <div class="rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-[#1e4f8f]">{{ $selectedClass->subject->subject_code ?? 'SUBJECT' }}</span>
                        <h2 class="text-lg font-bold text-slate-800">{{ $selectedClass->class_name }} — {{ $selectedClass->subject->subject_name ?? '' }}</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Tổng số: <strong>{{ $selectedClass->total_sessions }} buổi học</strong> · Sĩ số lớp: <strong>{{ $selectedClass->students()->count() }} sinh viên</strong>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.classes.show', $selectedClass) }}" class="rounded-md border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                        Xem hồ sơ lớp
                    </a>
                </div>
            </div>

            <!-- Danh sách buổi học (Session List) -->
            <div class="mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <span>📅</span> Danh sách các Buổi học trong Kế hoạch đào tạo
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Xác định thời gian thực: Ngày tháng năm, giờ bắt đầu – kết thúc, và trạng thái mở điểm danh.
                        </p>
                    </div>

                    <!-- Chú thích màu sắc trực quan theo yêu cầu -->
                    <div class="flex flex-wrap items-center gap-2.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#1e4f8f] text-white font-semibold shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-white"></span>
                            Màu xanh đậm: Đã học & Đã điểm danh ({{ $doneCount }})
                        </span>
                        @if ($todayCount > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-600 text-white font-semibold shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                                Hôm nay (Thời gian thực): {{ $todayCount }} buổi
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-slate-200 bg-white text-slate-700 font-semibold shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                            Màu bình thường: Chưa học ({{ $futureCount }})
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($sessions as $index => $sess)
                        @php
                            $sessNumber = $index + 1;
                            $isToday = $sess->isToday();
                            $isDone = $sess->isLearnedAndMarked();
                            $isFuture = $sess->isFutureSession();
                            $roomLabel = $sess->room?->room_name ?? 'Phòng Lab 201';
                            $slotDetail = $sess->getSlotTimeDisplay();
                            $windowStatus = $sess->getAttendanceWindowStatus(auth()->user());
                        @endphp

                        {{-- CARD BUỔI HỌC VỚI 3 TRẠNG THÁI MÀU SẮC THỜI GIAN THỰC --}}
                        <div class="flex flex-col justify-between rounded-lg transition-all p-4.5
                            @if ($isToday)
                                border-2 border-emerald-500 bg-emerald-50/30 shadow-md ring-2 ring-emerald-500/20
                            @elseif ($isDone)
                                border-2 border-[#1e4f8f] bg-blue-50/30 shadow-md ring-1 ring-[#1e4f8f]/20
                            @else
                                border border-slate-200 bg-white hover:border-slate-300 shadow-sm
                            @endif">
                            <div>
                                <!-- Header card buổi học -->
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-bold shadow-sm
                                        @if ($isToday)
                                            bg-emerald-600 text-white
                                        @elseif ($isDone)
                                            bg-[#1e4f8f] text-white
                                        @else
                                            bg-slate-100 text-slate-700
                                        @endif">
                                        <span>Buổi #{{ $sessNumber }}</span>
                                    </span>

                                    <!-- Badge trạng thái thời gian thực -->
                                    @if ($isToday)
                                        @if ($windowStatus['is_in_window'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 border border-emerald-300 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 animate-pulse shadow-sm">
                                                ● Đang mở điểm danh (Còn {{ $windowStatus['remaining_minutes'] }}p)
                                            </span>
                                        @elseif ($windowStatus['is_before_window'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-bold text-blue-800 shadow-sm">
                                                📍 Hôm nay (Mở lúc {{ $windowStatus['open'] ? $windowStatus['open']->format('H:i') : '' }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-900 shadow-sm">
                                                📍 Hôm nay (Quá giờ điểm danh)
                                            </span>
                                        @endif
                                    @elseif ($isDone)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#1e4f8f] px-2.5 py-0.5 text-[11px] font-bold text-white shadow-sm">
                                            ✓ Đã học & Đã điểm danh
                                        </span>
                                    @elseif ($isFuture)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                            ⏳ Chưa học (Tương lai)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100/90 px-2 py-0.5 text-[11px] font-semibold text-amber-900">
                                            ⚠️ Chưa điểm danh
                                        </span>
                                    @endif
                                </div>

                                <!-- Thông tin Ngày tháng năm và Ca học (ghi rõ giờ bắt đầu & kết thúc) -->
                                <div class="mt-3.5 space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-slate-400">📅 Ngày:</span>
                                        <p class="text-sm font-bold
                                            @if ($isToday)
                                                text-emerald-700
                                            @elseif ($isDone)
                                                text-[#1e4f8f]
                                            @else
                                                text-slate-800
                                            @endif">
                                            {{ $sess->session_date->format('d/m/Y') }}
                                            <span class="text-xs font-normal text-slate-500">({{ $sess->session_date->locale('vi')->dayName }})</span>
                                            @if ($isToday)
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase">Hôm nay</span>
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="text-slate-400">⏰ Ca học:</span>
                                        <span class="font-bold px-2 py-0.5 rounded
                                            @if ($isToday)
                                                text-emerald-800 bg-emerald-100
                                            @elseif ($isDone)
                                                text-[#1e4f8f] bg-blue-100/80
                                            @else
                                                text-slate-700 bg-slate-100
                                            @endif">
                                            {{ $slotDetail }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="text-slate-400">📍 Phòng:</span>
                                        <span class="font-medium text-slate-600">{{ $roomLabel }}</span>
                                        @if ($windowStatus['open'] && $windowStatus['close'])
                                            <span class="text-[11px] text-slate-400" title="Khung giờ điểm danh chuẩn (trước 30p - sau 15p)">
                                                ({{ $windowStatus['open']->format('H:i') }} – {{ $windowStatus['close']->format('H:i') }})
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Thống kê điểm danh nếu đã có dữ liệu -->
                                @if (($sess->total_attended ?? 0) > 0)
                                    <div class="mt-3 flex items-center justify-between gap-2 pt-3 border-t {{ $isDone ? 'border-blue-100' : 'border-slate-100' }} text-xs">
                                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">✓ {{ $sess->present_count ?? 0 }} Có mặt</span>
                                        <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded">✗ {{ $sess->absent_count ?? 0 }} Vắng</span>
                                        <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded">⚠ {{ $sess->late_count ?? 0 }} Muộn</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 pt-3 border-t {{ $isToday ? 'border-emerald-200' : ($isDone ? 'border-blue-100' : 'border-slate-100') }}">
                                <a href="{{ route('teacher.attendance.edit', [$selectedClass, $sess]) }}"
                                   class="flex items-center justify-center gap-2 w-full rounded-md py-2.5 text-xs font-bold transition shadow-sm
                                    @if ($isToday)
                                        @if ($windowStatus['is_in_window'])
                                            bg-emerald-600 hover:bg-emerald-700 text-white
                                        @else
                                            bg-emerald-700 hover:bg-emerald-800 text-white
                                        @endif
                                    @elseif ($isDone)
                                        bg-[#1e4f8f] text-white hover:bg-[#163c6d]
                                    @else
                                        bg-slate-100 text-slate-700 hover:bg-slate-200
                                    @endif">
                                    <span>
                                        @if ($isToday)
                                            @if ($windowStatus['is_in_window'])
                                                ✓ Bắt đầu điểm danh (Đang trong giờ)
                                            @elseif ($windowStatus['is_before_window'])
                                                Xem điểm danh hôm nay (Chưa tới ca)
                                            @else
                                                Cập nhật điểm danh hôm nay (Quá 15p)
                                            @endif
                                        @elseif ($isDone)
                                            Xem / Cập nhật kết quả điểm danh
                                        @elseif ($isFuture)
                                            Buổi học tương lai (Chưa diễn ra)
                                        @else
                                            Điểm danh buổi học
                                        @endif
                                    </span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-sm text-slate-500">
                            Lớp học này chưa có lịch buổi học nào trong hệ thống.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Script đồng hồ thời gian thực (Live Realtime Clock) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const el = document.getElementById('liveRealtimeClock');
        if (el) {
            el.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }
    setInterval(updateClock, 1000);
});
</script>
@endsection
