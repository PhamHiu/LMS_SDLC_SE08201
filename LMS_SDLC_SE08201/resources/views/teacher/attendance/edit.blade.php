@extends('layouts.app')

@section('title', 'Điểm danh buổi ' . $session->session_date->format('d/m/Y') . ' — ' . $class->class_name)

@section('content')
@php
    $startTime = $session->slot?->start_time ? substr($session->slot->start_time, 0, 5) : '--:--';
    $endTime = $session->slot?->end_time ? substr($session->slot->end_time, 0, 5) : '--:--';
    $slotNumber = $session->slot?->slot_number ?? 1;
    $slotLabel = "Ca {$slotNumber}: {$startTime} – {$endTime}";
    $ws = $windowStatus ?? $session->getAttendanceWindowStatus(auth()->user());
    $isAdmin = $ws['is_admin'] ?? (auth()->user()?->isAdmin() || auth()->user()?->role === \App\Models\User::ROLE_ADMIN);
    $isPastWindow = $ws['is_past_window'] ?? false;
    $isBeforeWindow = $ws['is_before_window'] ?? false;
    $isInWindow = $ws['is_in_window'] ?? false;
@endphp

<div class="w-full space-y-6">
    <!-- Header -->
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-bold text-sm">✓</span>
                    <h1 class="text-xl font-bold text-slate-800">
                        Điểm danh Buổi học: {{ $session->session_date->format('d/m/Y') }}
                    </h1>
                    @if ($session->isToday())
                        <span class="rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-bold text-white shadow-sm">
                            📍 HÔM NAY (Thời gian thực)
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                    <span class="font-bold text-[#1e4f8f]">{{ $class->class_name }}</span> ·
                    <span>{{ $class->subject->subject_name ?? '' }}</span> ·
                    <span>{{ $session->room?->room_name ?? 'Phòng Lab' }}</span> ·
                    <span class="font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">⏰ {{ $slotLabel }}</span> ·
                    <span class="font-mono text-xs text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">🕒 Live: <strong id="liveRealtimeClockEdit" class="text-slate-800">{{ now()->format('H:i:s') }}</strong></span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.attendance.index', ['class_id' => $class->class_id]) }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition shadow-sm">
                    ← Trở lại danh sách buổi
                </a>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- THÔNG BÁO CỬA SỔ THỜI GIAN ĐIỂM DANH & PHÂN QUYỀN GIÁO VIÊN / ADMIN       -->
        <!-- ========================================================================= -->
        <div class="mt-4">
            @if ($errors->any())
                <div class="mb-3 rounded-md border border-rose-300 bg-rose-50 p-4 text-xs font-semibold text-rose-800 shadow-sm">
                    <p class="font-bold text-sm flex items-center gap-1.5 mb-1.5">
                        <span>⛔</span> Không thể lưu điểm danh do vi phạm quy định:
                    </p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($isAdmin)
                <div class="rounded-md border border-blue-300 bg-blue-50/80 p-3.5 text-xs text-blue-950 flex items-start gap-2.5 shadow-sm">
                    <span class="text-base leading-none">🛡️</span>
                    <div>
                        <p class="font-bold text-blue-900">Quyền Quản trị viên (ADMIN)</p>
                        <p class="text-blue-800 mt-0.5">
                            Bạn đang thao tác với quyền ADMIN. Bạn có toàn quyền phê duyệt, xác nhận "Có mặt" (P), báo Đến muộn (L) hoặc Hủy điểm danh (A) bất kỳ thời điểm nào.
                        </p>
                    </div>
                </div>
            @elseif ($isPastWindow)
                <div class="rounded-md border border-amber-300 bg-amber-50 p-3.5 text-xs text-amber-950 flex items-start gap-2.5 shadow-sm">
                    <span class="text-base leading-none">⚠️</span>
                    <div>
                        <p class="font-bold text-amber-900">
                            Đã kết thúc thời gian điểm danh tiêu chuẩn (Sau khi vào ca 15 phút: {{ $ws['close'] ? $ws['close']->format('H:i d/m/Y') : '' }})
                        </p>
                        <p class="text-amber-800 mt-0.5">
                            Theo quy chế đào tạo: Giảng viên <strong>KHÔNG ĐƯỢC PHÉP</strong> cập nhật học sinh thành <strong>"Có mặt" (P)</strong> nữa. Bạn chỉ được phép <strong>Hủy điểm danh (Vắng - A)</strong> hoặc báo <strong>Đến muộn (L)</strong>.
                            Trạng thái "Có mặt" chỉ có <strong>Quản trị viên (ADMIN)</strong> mới có quyền cập nhật sau giờ điểm danh.
                        </p>
                    </div>
                </div>
            @elseif ($isBeforeWindow)
                <div class="rounded-md border border-blue-300 bg-blue-50 p-3.5 text-xs text-blue-950 flex items-start gap-2.5 shadow-sm">
                    <span class="text-base leading-none">⏳</span>
                    <div>
                        <p class="font-bold text-blue-900">
                            Chưa đến giờ điểm danh ca học này
                        </p>
                        <p class="text-blue-800 mt-0.5">
                            Hệ thống sẽ tự động mở điểm danh trước giờ bắt đầu 30 phút (lúc <strong>{{ $ws['open'] ? $ws['open']->format('H:i d/m/Y') : '' }}</strong>).
                        </p>
                    </div>
                </div>
            @else
                <div class="rounded-md border border-emerald-300 bg-emerald-50 p-3.5 text-xs text-emerald-950 flex items-start gap-2.5 shadow-sm">
                    <span class="text-base leading-none">✓</span>
                    <div>
                        <p class="font-bold text-emerald-900">
                            Đang trong cửa sổ điểm danh hợp lệ
                        </p>
                        <p class="text-emerald-800 mt-0.5">
                            Thời gian điểm danh mở từ <strong>{{ $ws['open'] ? $ws['open']->format('H:i') : '' }}</strong> (trước 30p) đến <strong>{{ $ws['close'] ? $ws['close']->format('H:i') : '' }}</strong> (sau khi vào ca 15p). Giảng viên có toàn quyền điểm danh Có mặt, Muộn hoặc Vắng.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Thẻ tóm tắt nhanh tình trạng lớp học ĐẦY ĐỦ 4 MỨC NGUY CƠ -->
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-md border border-slate-200/80 bg-slate-50/60 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sĩ số lớp</span>
                <p class="text-lg font-bold text-slate-800 mt-0.5">{{ $total_students ?? $students->count() }} SV</p>
            </div>
            <div class="rounded-md border border-rose-200 bg-rose-50/80 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Nguy cơ cao & Cấm thi</span>
                <p class="text-lg font-bold text-rose-700 mt-0.5">{{ $high_risk_count ?? 0 }} SV</p>
            </div>
            <div class="rounded-md border border-amber-200 bg-amber-50/80 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Cảnh báo sớm (Tiệm cận 20%)</span>
                <p class="text-lg font-bold text-amber-800 mt-0.5">{{ $warning_count ?? 0 }} SV</p>
            </div>
            <div class="rounded-md border border-emerald-200 bg-emerald-50/80 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">An toàn</span>
                <p class="text-lg font-bold text-emerald-700 mt-0.5">{{ $safe_count ?? (($total_students ?? $students->count()) - ($high_risk_count ?? 0) - ($warning_count ?? 0)) }} SV</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- BỘ LỌC ĐIỂM DANH: TẤT CẢ, CÓ MẶT, ĐẾN MUỘN, VẮNG MẶT (THEO YÊU CẦU USER) -->
        <!-- ========================================================================= -->
        <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1 flex items-center gap-1">
                    <span>🔍</span> Bộ lọc điểm danh:
                </span>
                <!-- Nút lọc Tất cả -->
                <button type="button"
                        onclick="filterByStatus('ALL')"
                        id="btnFilterAll"
                        class="status-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold border transition shadow-sm bg-slate-900 text-white border-slate-900"
                        data-filter="ALL">
                    <span>Tất cả</span>
                    <span id="countAll" class="rounded-full bg-slate-700 text-white px-2 py-0.2 text-[10px]">{{ $total_students ?? $students->count() }}</span>
                </button>

                <!-- Nút lọc Có mặt (P) -->
                <button type="button"
                        onclick="filterByStatus('P')"
                        id="btnFilterP"
                        class="status-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold border transition shadow-sm bg-white text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                        data-filter="P">
                    <span>✓ Có mặt</span>
                    <span id="countP" class="rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.2 text-[10px]">{{ $current_present_count ?? 0 }}</span>
                </button>

                <!-- Nút lọc Đến muộn (L) -->
                <button type="button"
                        onclick="filterByStatus('L')"
                        id="btnFilterL"
                        class="status-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold border transition shadow-sm bg-white text-amber-700 border-amber-300 hover:bg-amber-50"
                        data-filter="L">
                    <span>⚠ Đến muộn</span>
                    <span id="countL" class="rounded-full bg-amber-100 text-amber-800 px-2 py-0.2 text-[10px]">{{ $current_late_count ?? 0 }}</span>
                </button>

                <!-- Nút lọc Vắng mặt (A) -->
                <button type="button"
                        onclick="filterByStatus('A')"
                        id="btnFilterA"
                        class="status-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold border transition shadow-sm bg-white text-rose-700 border-rose-300 hover:bg-rose-50"
                        data-filter="A">
                    <span>✗ Vắng mặt</span>
                    <span id="countA" class="rounded-full bg-rose-100 text-rose-800 px-2 py-0.2 text-[10px]">{{ $current_absent_count ?? 0 }}</span>
                </button>
            </div>

            <!-- Công cụ hỗ trợ nhanh -->
            <div class="flex flex-wrap items-center gap-2">
                @if ($isAdmin || ! $isPastWindow)
                    <button type="button" onclick="markAll('P')" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-sm flex items-center gap-1.5">
                        <span>✓</span> Điểm danh tất cả Có mặt
                    </button>
                @else
                    <span class="rounded-lg bg-slate-100 text-slate-400 px-3 py-1.5 text-xs font-semibold border border-slate-200 cursor-not-allowed" title="Đã hết giờ điểm danh Có mặt">
                        🔒 Đã khóa "Tất cả Có mặt"
                    </span>
                @endif

                <button type="button" onclick="toggleHighlightWarning()" id="btnHighlight" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 transition shadow-sm flex items-center gap-1.5">
                    Làm nổi bật SV Cảnh báo / Nguy hiểm
                </button>
            </div>
        </div>
    </div>

    <!-- Bảng điểm danh từng sinh viên -->
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('teacher.attendance.update', [$class, $session]) }}" id="attendanceForm">
            @csrf
            <div class="divide-y divide-slate-100" id="studentListContainer">
                @forelse ($student_data as $item)
                    @php
                        $sv = $item['student'];
                        $current = $item['existing_status'];
                        $riskLevel = $item['risk_level'];
                        // Quy tắc khóa: Nếu đã hết giờ và không phải Admin, nếu SV chưa là 'P' thì radio 'P' bị khóa hoàn toàn.
                        $lockPresentRadio = ($isPastWindow && ! $isAdmin && $current !== 'P');
                    @endphp
                    <div class="student-row flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 hover:bg-slate-50/70 transition"
                         data-student-id="{{ $sv->user_id }}"
                         data-risk="{{ $riskLevel }}"
                         data-status="{{ $current }}"
                         data-name="{{ mb_strtolower($sv->full_name) }}">
                        <!-- Cột 1: Thông tin học sinh + CẢNH BÁO NGUY CƠ BỎ HỌC Ở NGAY DƯỚI TÊN -->
                        <div class="flex items-start gap-3.5 min-w-0 flex-1">
                            <!-- Avatar / Initial -->
                            <div class="flex-shrink-0 mt-0.5">
                                <span class="flex h-11 w-11 items-center justify-center rounded-md font-bold text-xs shadow-sm
 {{ $riskLevel === 'VERY_HIGH' ? 'bg-rose-100 text-rose-700 border-2 border-rose-400 ring-2 ring-rose-500/20' :
 ($riskLevel === 'HIGH' ? 'bg-orange-100 text-orange-700 border-2 border-orange-400' :
 ($riskLevel === 'MEDIUM' ? 'bg-amber-100 text-amber-700 border-2 border-amber-400' :
 'bg-emerald-100 text-emerald-700 border-2 border-emerald-300')) }}">
                                    {{ mb_substr($sv->full_name, 0, 1) }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <!-- Tên học sinh và Mã SV -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-base font-semibold text-slate-900">{{ $sv->full_name }}</span>
                                    <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-bold text-slate-700">{{ $sv->student_code ?? ('SV' . $sv->user_id) }}</span>
                                    <span class="text-xs text-slate-500">({{ $sv->email }})</span>
                                </div>

                                <!-- CẢNH BÁO NGUY CƠ BỎ HỌC CỦA HỌC SINH -->
                                <div class="mt-2">
                                    @if ($riskLevel === 'VERY_HIGH')
                                        <div class="inline-flex flex-col sm:flex-row sm:items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-3.5 py-2 text-xs text-rose-950 shadow-sm">
                                            <span class="font-bold text-rose-700 flex items-center gap-1.5 uppercase tracking-wide">
                                                [CỰC KỲ NGUY HIỂM — NGUY CƠ RẤT CAO]
                                            </span>
                                            <span class="hidden sm:inline text-rose-700">|</span>
                                            <span>Đã vắng môn này: <strong class="text-rose-700 font-semibold">{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}% > 20%)</strong></span>
                                            <span class="hidden sm:inline text-rose-700">|</span>
                                            <span class="font-bold text-rose-700">Cần gặp khẩn cấp sinh viên & phụ huynh để can thiệp!</span>
                                        </div>
                                    @elseif ($riskLevel === 'HIGH' || $item['attendance_failed'])
                                        <div class="inline-flex flex-col sm:flex-row sm:items-center gap-2 rounded-md border border-orange-300 bg-orange-50 px-3.5 py-2 text-xs text-orange-950 shadow-sm">
                                            <span class="font-bold text-orange-700 flex items-center gap-1.5 uppercase tracking-wide">
                                                [NGUY HIỂM — CẤM THI VÌ VẮNG > 20%]
                                            </span>
                                            <span class="hidden sm:inline text-orange-700">|</span>
                                            <span>Đã vắng môn này: <strong class="text-orange-700 font-semibold">{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}%)</strong></span>
                                            <span class="hidden sm:inline text-orange-700">|</span>
                                            <span class="font-bold text-orange-700">Nhắc nhở: Đã vượt ngưỡng 20%, thông báo cấm thi và giao bài tập bù qua AI Tutor!</span>
                                        </div>
                                    @elseif ($riskLevel === 'MEDIUM' || $item['attendance_warning'])
                                        <div class="inline-flex flex-col sm:flex-row sm:items-center gap-2 rounded-md border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs text-amber-950 shadow-sm">
                                            <span class="font-bold text-amber-800 flex items-center gap-1.5 uppercase tracking-wide">
                                                <span>⚠️</span> [CẢNH BÁO SỚM — TIỆM CẬN 20%]
                                            </span>
                                            <span class="hidden sm:inline text-amber-700">|</span>
                                            <span>Đã vắng môn này: <strong class="text-amber-800 font-semibold">{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}%)</strong></span>
                                            <span class="hidden sm:inline text-amber-700">|</span>
                                            <span class="font-bold text-amber-800">Nhắc nhở: Sinh viên tuyệt đối không được nghỉ thêm buổi nào để tránh bị cấm thi!</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-2 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs text-emerald-900 shadow-sm">
                                            <span class="font-bold text-emerald-700 flex items-center gap-1">
                                                [AN TOÀN — CHUYÊN CẦN TỐT]
                                            </span>
                                            <span class="text-emerald-700">|</span>
                                            <span>Đã vắng: <strong>{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}%)</strong></span>
                                            <span class="text-emerald-700">|</span>
                                            <span class="text-slate-500">Học tập và chuyên cần ổn định</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Cột 2: Chọn trạng thái điểm danh + Ghi chú buổi học -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <!-- Radio group trạng thái: Có mặt / Muộn / Vắng -->
                            <div class="inline-flex rounded-md bg-slate-100 p-1 border border-slate-200/80">
                                <!-- Option: CÓ MẶT (P) -->
                                <label class="{{ $lockPresentRadio ? 'cursor-not-allowed' : 'cursor-pointer' }}" title="{{ $lockPresentRadio ? 'Chỉ ADMIN mới có quyền xác nhận Có mặt sau khi hết giờ điểm danh' : 'Có mặt' }}">
                                    <input type="radio"
                                           name="attendances[{{ $sv->user_id }}][status]"
                                           value="P"
                                           class="peer sr-only status-radio status-radio-p"
                                           @checked($current === 'P')
                                           @disabled($lockPresentRadio)>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-bold transition {{ $lockPresentRadio ? 'opacity-40 cursor-not-allowed bg-slate-200 text-slate-400' : 'text-slate-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-sm' }}">
                                        @if ($lockPresentRadio)
                                            🔒
                                        @else
                                            ✓
                                        @endif
                                        Có mặt
                                    </span>
                                </label>

                                <!-- Option: ĐẾN MUỘN (L) -->
                                <label class="cursor-pointer" title="Báo học sinh đến muộn">
                                    <input type="radio"
                                           name="attendances[{{ $sv->user_id }}][status]"
                                           value="L"
                                           class="peer sr-only status-radio status-radio-l"
                                           @checked($current === 'L')>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 transition peer-checked:bg-amber-500 peer-checked:text-white peer-checked:shadow-sm">
                                        ⚠ Muộn
                                    </span>
                                </label>

                                <!-- Option: VẮNG MẶT / HỦY ĐIỂM DANH (A) -->
                                <label class="cursor-pointer" title="Hủy điểm danh hoặc báo Vắng mặt">
                                    <input type="radio"
                                           name="attendances[{{ $sv->user_id }}][status]"
                                           value="A"
                                           class="peer sr-only status-radio status-radio-a"
                                           @checked($current === 'A')>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 transition peer-checked:bg-rose-600 peer-checked:text-white peer-checked:shadow-sm">
                                        ✗ Vắng
                                    </span>
                                </label>
                            </div>

                            <!-- Ô nhập ghi chú cá nhân -->
                            <input type="text"
                                   list="absenceReasonPresets"
                                   name="attendances[{{ $sv->user_id }}][note]"
                                   value="{{ $item['existing_note'] }}"
                                   placeholder="Lý do vắng / Ghi chú (Ốm, Gia đình, Đến muộn...)"
                                   class="w-full sm:w-56 rounded-md border border-slate-200 px-3.5 py-2 text-xs text-slate-700 placeholder-slate-400 focus:border-[#1e4f8f] focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-sm text-slate-500">
                        Lớp học chưa có sinh viên nào ghi danh.
                    </div>
                @endforelse
            </div>

            <!-- Dòng thông báo khi bộ lọc không có sinh viên nào -->
            <div id="noMatchFilteredRow" class="hidden py-12 text-center text-sm text-slate-500">
                Không tìm thấy sinh viên nào theo bộ lọc đang chọn.
            </div>

            @if ($students->isNotEmpty())
                <div class="sticky bottom-4 mx-4 my-4 p-4 rounded-lg bg-slate-900 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold flex items-center gap-2">
                            <span>💾</span> Xác nhận kết quả điểm danh buổi học
                        </p>
                        <p class="text-xs text-slate-300 mt-0.5">
                            Sau khi lưu, hệ thống AI sẽ tự động đồng bộ và tính toán lại nguy cơ bỏ học thời gian thực.
                        </p>
                    </div>
                    <button type="submit" class="rounded-md bg-emerald-500 hover:bg-emerald-600 px-6 py-3 text-sm font-bold text-white transition shadow-md flex items-center justify-center gap-2">
                        <span>Lưu Điểm Danh & Đồng Bộ AI</span>
                    </button>
                </div>
            @endif
        </form>

        <datalist id="absenceReasonPresets">
            <option value="Ốm / Sức khỏe (Có phép)"></option>
            <option value="Việc gia đình (Có phép)"></option>
            <option value="Ngủ quên / Trễ ca sáng sớm"></option>
            <option value="Chưa làm bài tập / Hổng kiến thức"></option>
            <option value="Bận đi làm thêm / Trùng lịch cá nhân"></option>
            <option value="Buông xuôi sau vượt ngưỡng cấm thi >20%"></option>
            <option value="Vắng không phép"></option>
        </datalist>
    </div>
</div>

<script>
const isPastWindow = {{ $isPastWindow ? 'true' : 'false' }};
const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
let currentStatusFilter = 'ALL';
let isHighlighted = false;

// Hàm điểm danh tất cả Có mặt (Chỉ hoạt động khi chưa hết giờ hoặc là Admin)
function markAll(status) {
    if (status === 'P') {
        if (isPastWindow && !isAdmin) {
            alert('Đã hết thời gian điểm danh Có mặt. Chỉ ADMIN mới có quyền xác nhận Có mặt sau giờ điểm danh.');
            return;
        }
        document.querySelectorAll('.status-radio-p:not(:disabled)').forEach(input => {
            input.checked = true;
            const row = input.closest('.student-row');
            if (row) {
                row.setAttribute('data-status', 'P');
            }
        });
        updateStatusCounts();
        applyFilters();
    }
}

// Cập nhật số đếm thống kê trên các nút lọc
function updateStatusCounts() {
    let pCount = 0;
    let lCount = 0;
    let aCount = 0;

    document.querySelectorAll('.student-row').forEach(row => {
        const checkedRadio = row.querySelector('input[type="radio"]:checked');
        const status = checkedRadio ? checkedRadio.value : 'P';
        row.setAttribute('data-status', status);

        if (status === 'P') pCount++;
        else if (status === 'L') lCount++;
        else if (status === 'A') aCount++;
    });

    const elP = document.getElementById('countP');
    const elL = document.getElementById('countL');
    const elA = document.getElementById('countA');
    const elAll = document.getElementById('countAll');

    if (elP) elP.textContent = pCount;
    if (elL) elL.textContent = lCount;
    if (elA) elA.textContent = aCount;
    if (elAll) elAll.textContent = (pCount + lCount + aCount);
}

// Lọc danh sách sinh viên theo trạng thái: ALL, P, L, A
function filterByStatus(status) {
    currentStatusFilter = status;

    // Cập nhật giao diện nút bấm
    document.querySelectorAll('.status-filter-btn').forEach(btn => {
        const f = btn.getAttribute('data-filter');
        if (f === status) {
            btn.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
            btn.classList.remove('bg-white', 'text-emerald-700', 'text-amber-700', 'text-rose-700');
        } else {
            btn.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
            btn.classList.add('bg-white');
            if (f === 'P') btn.classList.add('text-emerald-700');
            else if (f === 'L') btn.classList.add('text-amber-700');
            else if (f === 'A') btn.classList.add('text-rose-700');
        }
    });

    applyFilters();
}

// Áp dụng bộ lọc hiển thị sinh viên
function applyFilters() {
    const rows = document.querySelectorAll('.student-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        const risk = row.getAttribute('data-risk');
        const matchStatus = (currentStatusFilter === 'ALL' || status === currentStatusFilter);

        if (matchStatus) {
            row.style.display = '';
            visibleCount++;

            if (isHighlighted) {
                if (risk === 'SAFE') {
                    row.style.opacity = '0.35';
                    row.classList.remove('bg-amber-50/50', 'bg-rose-50/50');
                } else if (risk === 'VERY_HIGH' || risk === 'HIGH') {
                    row.style.opacity = '1';
                    row.classList.add('bg-rose-50/50');
                } else if (risk === 'MEDIUM') {
                    row.style.opacity = '1';
                    row.classList.add('bg-amber-50/50');
                }
            } else {
                row.style.opacity = '1';
                row.classList.remove('bg-amber-50/50', 'bg-rose-50/50');
            }
        } else {
            row.style.display = 'none';
        }
    });

    const noMatch = document.getElementById('noMatchFilteredRow');
    if (noMatch) {
        noMatch.classList.toggle('hidden', visibleCount > 0);
    }
}

// Bật / Tắt làm nổi bật sinh viên nguy cơ
function toggleHighlightWarning() {
    isHighlighted = !isHighlighted;
    const btn = document.getElementById('btnHighlight');

    if (isHighlighted) {
        btn.classList.add('bg-amber-600', 'text-white');
        btn.classList.remove('bg-amber-50', 'text-amber-800');
        btn.innerHTML = '<span>✕</span> Bỏ làm nổi bật nguy cơ';
    } else {
        btn.classList.remove('bg-amber-600', 'text-white');
        btn.classList.add('bg-amber-50', 'text-amber-800');
        btn.innerHTML = 'Làm nổi bật SV Cảnh báo / Nguy hiểm';
    }

    applyFilters();
}

// Lắng nghe sự kiện thay đổi radio điểm danh
document.addEventListener('DOMContentLoaded', function() {
    function updateLiveClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const el = document.getElementById('liveRealtimeClockEdit');
        if (el) {
            el.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }
    setInterval(updateLiveClock, 1000);

    updateStatusCounts();

    document.querySelectorAll('.status-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const row = this.closest('.student-row');
            if (!row) return;

            row.setAttribute('data-status', this.value);

            // Quy định: Nếu quá giờ và không phải Admin, khi giáo viên đã chuyển sang Vắng (A) hoặc Muộn (L),
            // thì khóa không cho quay lại Có mặt (P)
            if (isPastWindow && !isAdmin && (this.value === 'A' || this.value === 'L')) {
                const radioP = row.querySelector('.status-radio-p');
                if (radioP) {
                    radioP.disabled = true;
                    const spanP = radioP.nextElementSibling;
                    if (spanP) {
                        spanP.classList.add('opacity-40', 'cursor-not-allowed', 'bg-slate-200', 'text-slate-400');
                        spanP.classList.remove('peer-checked:bg-emerald-600', 'peer-checked:text-white');
                        spanP.innerHTML = '🔒 Có mặt';
                        spanP.parentElement.title = 'Chỉ ADMIN mới có quyền xác nhận Có mặt sau khi hết giờ điểm danh';
                    }
                }
            }

            updateStatusCounts();

            // Nếu đang lọc theo 1 trạng thái cụ thể, cập nhật lại view
            if (currentStatusFilter !== 'ALL') {
                applyFilters();
            }
        });
    });
});
</script>
@endsection
