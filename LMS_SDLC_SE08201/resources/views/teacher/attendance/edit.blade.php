@extends('layouts.app')

@section('title', 'Điểm danh buổi ' . $session->session_date->format('d/m/Y') . ' — ' . $class->class_name)

@section('content')
<div class="w-full space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-bold text-sm">✅</span>
                    <h1 class="text-xl font-black text-slate-800">
                        Điểm danh Buổi học: {{ $session->session_date->format('d/m/Y') }}
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                    <span class="font-bold text-indigo-600">{{ $class->class_name }}</span> ·
                    <span>{{ $class->subject->subject_name ?? '' }}</span> ·
                    <span>📍 {{ $session->room?->room_name ?? 'Phòng Lab' }}</span> ·
                    <span>⏰ {{ $session->slot?->label ?? 'Ca học' }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.attendance.index', ['class_id' => $class->class_id]) }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition shadow-sm">
                    ← Trở lại danh sách buổi
                </a>
            </div>
        </div>

        <!-- Thẻ tóm tắt nhanh tình trạng lớp học ĐẦY ĐỦ 4 MỨC NGUY CƠ -->
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sĩ số lớp</span>
                <p class="text-lg font-black text-slate-800 mt-0.5">{{ $total_students ?? $students->count() }} SV</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">🔴 Nguy cơ cao & Cấm thi</span>
                <p class="text-lg font-black text-rose-700 mt-0.5">{{ $high_risk_count ?? 0 }} SV</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">🟡 Cảnh báo sớm (Tiệm cận 20%)</span>
                <p class="text-lg font-black text-amber-800 mt-0.5">{{ $warning_count ?? 0 }} SV</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">🟢 An toàn</span>
                <p class="text-lg font-black text-emerald-700 mt-0.5">{{ $safe_count ?? (($total_students ?? $students->count()) - ($high_risk_count ?? 0) - ($warning_count ?? 0)) }} SV</p>
            </div>
        </div>

        <!-- Thanh công cụ hỗ trợ giáo viên -->
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
            <div class="flex items-center gap-2">
                <button type="button" onclick="markAll('P')" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-sm flex items-center gap-1.5">
                    <span>⚡</span> Điểm danh tất cả Có mặt
                </button>
                <button type="button" onclick="toggleHighlightWarning()" id="btnHighlight" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 transition shadow-sm flex items-center gap-1.5">
                    <span>🔔</span> Làm nổi bật SV Cảnh báo / Nguy hiểm
                </button>
            </div>
            <div class="text-xs text-slate-500 italic">
                * Quy chế BTEC FPT: Học sinh vắng > 20% tổng số buổi môn học sẽ bị cấm thi.
            </div>
        </div>
    </div>

    <!-- Bảng điểm danh từng sinh viên -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('teacher.attendance.update', [$class, $session]) }}">
            @csrf
            <div class="divide-y divide-slate-100" id="studentListContainer">
                @forelse ($student_data as $item)
                    @php
                        $sv = $item['student'];
                        $current = $item['existing_status'];
                        $riskLevel = $item['risk_level'];
                    @endphp
                    <div class="student-row flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 hover:bg-slate-50/70 transition" data-risk="{{ $riskLevel }}">
                        <!-- Cột 1: Thông tin học sinh + CẢNH BÁO NGUY CƠ BỎ HỌC Ở NGAY DƯỚI TÊN -->
                        <div class="flex items-start gap-3.5 min-w-0 flex-1">
                            <!-- Avatar / Initial -->
                            <div class="flex-shrink-0 mt-0.5">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl font-black text-xs shadow-sm
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
                                    <span class="text-base font-extrabold text-slate-900">{{ $sv->full_name }}</span>
                                    <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-bold text-slate-700">{{ $sv->student_code ?? ('SV' . $sv->user_id) }}</span>
                                    <span class="text-xs text-slate-400">({{ $sv->email }})</span>
                                </div>

                                <!-- ======================================================== -->
                                <!-- CẢNH BÁO NGUY CƠ BỎ HỌC CỦA HỌC SINH (NGAY DƯỚI TÊN)      -->
                                <!-- ĐẦY ĐỦ 4 LOẠI: AN TOÀN, CẢNH BÁO, NGUY HIỂM, RẤT NGUY HIỂM-->
                                <!-- ======================================================== -->
                                <div class="mt-2">
                                    @if ($riskLevel === 'VERY_HIGH')
                                        <div class="inline-flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl border border-rose-300 bg-rose-50 px-3.5 py-2 text-xs text-rose-950 shadow-sm">
                                            <span class="font-black text-rose-700 flex items-center gap-1.5 uppercase tracking-wide">
                                                <span>⛔</span> [CỰC KỲ NGUY HIỂM — NGUY CƠ RẤT CAO]
                                            </span>
                                            <span class="hidden sm:inline text-rose-300">|</span>
                                            <span>Đã vắng môn này: <strong class="text-rose-700 font-extrabold">{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}% > 20%)</strong></span>
                                            <span class="hidden sm:inline text-rose-300">|</span>
                                            <span class="font-bold text-rose-700">💬 Cần gặp khẩn cấp sinh viên & phụ huynh để can thiệp!</span>
                                        </div>
                                    @elseif ($riskLevel === 'HIGH' || $item['attendance_failed'])
                                        <div class="inline-flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl border border-orange-300 bg-orange-50 px-3.5 py-2 text-xs text-orange-950 shadow-sm">
                                            <span class="font-black text-orange-700 flex items-center gap-1.5 uppercase tracking-wide">
                                                <span>🚨</span> [NGUY HIỂM — CẤM THI VÌ VẮNG > 20%]
                                            </span>
                                            <span class="hidden sm:inline text-orange-300">|</span>
                                            <span>Đã vắng môn này: <strong class="text-orange-700 font-extrabold">{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}%)</strong></span>
                                            <span class="hidden sm:inline text-orange-300">|</span>
                                            <span class="font-bold text-orange-700">💬 Nhắc nhở: Đã vượt ngưỡng 20%, thông báo cấm thi và giao bài tập bù qua AI Tutor!</span>
                                        </div>
                                    @elseif ($riskLevel === 'MEDIUM' || $item['attendance_warning'])
                                        <div class="inline-flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs text-amber-950 shadow-sm">
                                            <span class="font-black text-amber-800 flex items-center gap-1.5 uppercase tracking-wide">
                                                <span>⚠️</span> [CẢNH BÁO SỚM — TIỆM CẬN 20%]
                                            </span>
                                            <span class="hidden sm:inline text-amber-300">|</span>
                                            <span>Đã vắng môn này: <strong class="text-amber-800 font-extrabold">{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}%)</strong></span>
                                            <span class="hidden sm:inline text-amber-300">|</span>
                                            <span class="font-bold text-amber-800">💬 Nhắc nhở: Sinh viên tuyệt đối không được nghỉ thêm buổi nào để tránh bị cấm thi!</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs text-emerald-900 shadow-sm">
                                            <span class="font-bold text-emerald-700 flex items-center gap-1">
                                                <span>🟢</span> [AN TOÀN — CHUYÊN CẦN TỐT]
                                            </span>
                                            <span class="text-emerald-300">|</span>
                                            <span>Đã vắng: <strong>{{ $item['absent_count'] }}/{{ $item['total_sessions'] }} buổi ({{ $item['absence_rate'] }}%)</strong></span>
                                            <span class="text-emerald-300">|</span>
                                            <span class="text-slate-500">Học tập và chuyên cần ổn định</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Cột 2: Chọn trạng thái điểm danh + Ghi chú buổi học -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <!-- Radio group trạng thái: Có mặt / Muộn / Vắng -->
                            <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200/80">
                                <label class="cursor-pointer">
                                    <input type="radio" name="attendances[{{ $sv->user_id }}][status]" value="P" class="peer sr-only status-radio-p" @checked($current === 'P')>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 transition peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-sm">
                                        ✓ Có mặt
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="attendances[{{ $sv->user_id }}][status]" value="L" class="peer sr-only" @checked($current === 'L')>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 transition peer-checked:bg-amber-500 peer-checked:text-white peer-checked:shadow-sm">
                                        ⚠ Muộn
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="attendances[{{ $sv->user_id }}][status]" value="A" class="peer sr-only" @checked($current === 'A')>
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
                                   placeholder="Lý do vắng / Ghi chú (Ốm, Gia đình, Ca sáng...)"
                                   class="w-full sm:w-56 rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-700 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-sm text-slate-400">
                        Lớp học chưa có sinh viên nào ghi danh.
                    </div>
                @endforelse
            </div>

            @if ($students->isNotEmpty())
                <div class="sticky bottom-4 mx-4 my-4 p-4 rounded-2xl bg-slate-900/90 backdrop-blur-md border border-slate-700 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                    <div>
                        <p class="text-sm font-bold flex items-center gap-2">
                            <span>💾</span> Xác nhận kết quả điểm danh buổi học
                        </p>
                        <p class="text-xs text-slate-300 mt-0.5">
                            Sau khi lưu, hệ thống AI sẽ tự động đồng bộ và tính toán lại nguy cơ bỏ học thời gian thực.
                        </p>
                    </div>
                    <button type="submit" class="rounded-xl bg-emerald-500 px-6 py-3 text-sm font-extrabold text-white hover:bg-emerald-600 transition shadow-lg shadow-emerald-500/30 flex items-center justify-center gap-2">
                        <span>Lưu Điểm Danh & Đồng Bộ AI</span>
                        <span>➔</span>
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
function markAll(status) {
    if (status === 'P') {
        document.querySelectorAll('.status-radio-p').forEach(input => {
            input.checked = true;
        });
    }
}

let isHighlighted = false;
function toggleHighlightWarning() {
    isHighlighted = !isHighlighted;
    const btn = document.getElementById('btnHighlight');
    const rows = document.querySelectorAll('.student-row');

    rows.forEach(row => {
        const risk = row.getAttribute('data-risk');
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
    });

    if (isHighlighted) {
        btn.classList.add('bg-amber-500', 'text-white');
        btn.classList.remove('bg-amber-50', 'text-amber-800');
        btn.innerHTML = '<span>✕</span> Bỏ lọc (Hiện tất cả SV)';
    } else {
        btn.classList.remove('bg-amber-500', 'text-white');
        btn.classList.add('bg-amber-50', 'text-amber-800');
        btn.innerHTML = '<span>🔔</span> Làm nổi bật SV Cảnh báo / Nguy hiểm';
    }
}
</script>
@endsection
