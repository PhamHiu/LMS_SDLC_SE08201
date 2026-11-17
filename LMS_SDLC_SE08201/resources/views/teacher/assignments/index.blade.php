@extends('layouts.app')

@section('title', 'Quản lý Bài tập & Chấm bài')

@section('content')
<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#1e4f8f]/10 text-[#1e4f8f] font-bold text-lg">📝</span>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800">Quản lý Bài tập & Chấm bài</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Theo dõi tiến độ nộp bài theo môn, theo lớp, kiểm tra tỷ lệ nộp bài và gửi thông báo nhắc nhở nhanh.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative inline-block text-left" id="assignDropdownContainer">
                <button onclick="document.getElementById('assignDropdownMenu').classList.toggle('hidden')" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-5 py-2.5 text-sm font-bold text-white shadow-sm focus:outline-none transition-all cursor-pointer">
                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ GIAO BÀI TẬP MỚI</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="assignDropdownMenu" class="absolute right-0 mt-2 w-64 origin-top-right rounded-lg border border-slate-200 bg-white shadow-lg focus:outline-none hidden z-30 overflow-hidden ring-1 ring-slate-200">
                    <div class="p-2 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Chọn lớp để giao bài tập:
                    </div>
                    <div class="flex flex-col divide-y divide-slate-100 max-h-64 overflow-y-auto">
                        @foreach ($classes as $c)
                            <a href="{{ route('teacher.assignments.create', $c) }}" class="flex items-center justify-between px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#1e4f8f] transition-colors">
                                <span>{{ $c->class_name }}</span>
                                <span class="text-[11px] text-slate-400 font-normal">{{ $c->subject?->subject_code }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc theo Môn học, Lớp học và Tìm kiếm -->
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('teacher.assignments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Lọc theo Lớp học -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Lớp học:</label>
                <select name="class_id" onchange="this.form.submit()" class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-[#1e4f8f] focus:outline-none">
                    <option value="">-- Tất cả lớp học --</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->class_id }}" @selected(($selectedClassId ?? null) == $c->class_id)>
                            {{ $c->class_name }} ({{ $c->subject?->subject_code ?? 'Môn học' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Lọc theo Môn học -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Môn học:</label>
                <select name="subject_id" onchange="this.form.submit()" class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-[#1e4f8f] focus:outline-none">
                    <option value="">-- Tất cả môn học --</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->subject_id }}" @selected(($selectedSubjectId ?? null) == $s->subject_id)>
                            {{ $s->subject_code }} — {{ $s->subject_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tìm kiếm tên bài tập -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Từ khóa tìm kiếm:</label>
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Nhập tên bài tập..."
                       class="w-full rounded-md border border-slate-200 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 focus:border-[#1e4f8f] focus:outline-none">
            </div>

            <!-- Nút lọc & Reset -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-4 py-2 text-xs font-bold text-white transition shadow-sm">
                    Lọc dữ liệu
                </button>
                @if (!empty($selectedClassId) || !empty($selectedSubjectId) || !empty($search))
                    <a href="{{ route('teacher.assignments.index') }}" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 transition shadow-sm" title="Xóa bộ lọc">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng danh sách bài tập kèm Tiến độ & Tỷ lệ nộp bài -->
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5">Bài tập</th>
                    <th class="px-4 py-3.5">Môn & Lớp</th>
                    <th class="px-3 py-3.5">Loại</th>
                    <th class="px-4 py-3.5">Hạn nộp</th>
                    <th class="px-5 py-3.5 text-center min-w-[200px]">Tiến độ nộp bài (%)</th>
                    <th class="px-5 py-3.5 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assignments as $a)
                    @php
                        $totalStudents = $a->classRoom?->students->count() ?? 0;
                        $subCount = $a->submissions_count;
                        $rate = $totalStudents > 0 ? round(($subCount / $totalStudents) * 100) : 0;
                        $notSubCount = max(0, $totalStudents - $subCount);
                        $isOverdue = $a->isOverdue();
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- Tên bài tập -->
                        <td class="px-5 py-4">
                            <a href="{{ route('teacher.assignments.show', $a) }}" class="font-bold text-slate-800 hover:text-[#1e4f8f] transition text-sm">
                                {{ $a->title }}
                            </a>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                <span>Thang điểm: <strong>{{ $a->max_score }}</strong></span>
                                @if ($a->isQuiz())
                                    <span>·</span>
                                    <span>{{ $a->questions_count }} câu hỏi trắc nghiệm</span>
                                @endif
                            </div>
                        </td>

                        <!-- Môn & Lớp -->
                        <td class="px-4 py-4">
                            <div class="font-bold text-slate-800 text-xs">{{ $a->classRoom?->class_name }}</div>
                            <div class="text-[11px] text-slate-500">{{ $a->classRoom?->subject?->subject_name }}</div>
                        </td>

                        <!-- Loại bài -->
                        <td class="px-3 py-4">
                            <span class="rounded-md px-2.5 py-1 text-[11px] font-bold uppercase {{ $a->isQuiz() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-[#1e4f8f]/10 text-[#1e4f8f] border border-[#1e4f8f]/20' }}">
                                {{ $a->typeLabel() }}
                            </span>
                        </td>

                        <!-- Hạn nộp -->
                        <td class="px-4 py-4 text-xs">
                            <span class="font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ $a->due_date ? $a->due_date->format('H:i d/m/Y') : 'Không giới hạn' }}
                            </span>
                            @if ($isOverdue)
                                <div class="text-[10px] font-bold text-rose-600 mt-0.5">⚠️ Đã hết hạn</div>
                            @endif
                        </td>

                        <!-- Tiến độ nộp bài (%) -->
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-slate-800">
                                    {{ $subCount }}/{{ $totalStudents }} SV
                                </span>
                                <span class="font-extrabold {{ $rate >= 75 ? 'text-emerald-700' : ($rate >= 40 ? 'text-amber-700' : 'text-rose-700') }}">
                                    {{ $rate }}%
                                </span>
                            </div>
                            <!-- Thanh Progress Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $rate >= 75 ? 'bg-emerald-500' : ($rate >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                     style="width: {{ $rate }}%"></div>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                                <span>{{ $notSubCount }} bạn chưa nộp</span>
                                <a href="{{ route('teacher.grading.index', $a) }}#tab-not-submitted" class="text-[#1e4f8f] hover:underline font-semibold">Xem ai chưa nộp →</a>
                            </div>
                        </td>

                        <!-- Thao tác -->
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Nút Chấm bài -->
                                <a href="{{ route('teacher.grading.index', $a) }}"
                                   class="inline-flex items-center gap-1.5 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-3 py-1.5 text-xs font-bold text-white shadow-sm transition">
                                    <span>Chấm bài</span>
                                    <span class="rounded bg-white/20 px-1 py-0.2 text-[10px]">{{ $subCount }}</span>
                                </a>

                                <!-- Nút Nhắc nộp nhanh nếu còn bạn chưa nộp -->
                                @if ($notSubCount > 0)
                                    <form method="POST" action="{{ route('teacher.grading.remind', $a) }}" onsubmit="return confirm('Gửi thông báo nhắc nhở nộp bài tập đến {{ $notSubCount }} sinh viên chưa nộp?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-amber-300 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 text-xs font-semibold text-amber-800 transition shadow-sm" title="Nhắc nhở {{ $notSubCount }} bạn chưa nộp">
                                            <span>📢 Nhắc nộp</span>
                                        </button>
                                    </form>
                                @endif

                                <!-- Nút Sửa hạn nộp nhanh -->
                                <button type="button"
                                        onclick="openExtendModal('{{ route('teacher.assignments.extend-deadline', $a) }}', '{{ addslashes($a->title) }}', '{{ $a->due_date ? $a->due_date->format('Y-m-d\TH:i') : '' }}', '{{ $a->due_date ? $a->due_date->format('H:i d/m/Y') : 'Không giới hạn' }}')"
                                        class="rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:text-amber-700 px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition shadow-sm"
                                        title="Sửa / Gia hạn hạn nộp bài">
                                    ⏰ Hạn nộp
                                </button>

                                <!-- Nút Sửa chi tiết -->
                                <a href="{{ route('teacher.assignments.edit', $a) }}"
                                   class="rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition shadow-sm"
                                   title="Chỉnh sửa bài tập">
                                    ✏️
                                </a>

                                <form method="POST" action="{{ route('teacher.assignments.destroy', $a) }}" onsubmit="return confirm('Xóa bài tập này? Toàn bộ bài nộp sẽ bị xóa theo.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-md border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition" title="Xóa bài tập">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                            Không tìm thấy bài tập nào phù hợp với bộ lọc.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <div class="mt-4">{{ $assignments->links() }}</div>
</div>

<!-- ========================================================================= -->
<!-- MODAL SỬA HẠN NỘP BÀI / GIA HẠN NHANH                                   -->
<!-- ========================================================================= -->
<div id="quickExtendDeadlineModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- Header modal -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-4">
            <div class="flex items-center gap-2.5">
                <div class="rounded-lg bg-[#1e4f8f]/10 p-2 text-[#1e4f8f]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Sửa / Gia hạn hạn nộp bài</h3>
                    <p id="quickModalTitle" class="text-xs text-slate-500 mt-0.5 truncate max-w-[340px]"></p>
                </div>
            </div>
            <button type="button" onclick="closeExtendModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body Form -->
        <form id="quickExtendForm" method="POST" action="" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Hạn nộp hiện tại -->
            <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500">Hạn nộp hiện tại:</span>
                    <div id="quickModalOldDue" class="text-sm font-bold text-slate-800"></div>
                </div>
            </div>

            <!-- Input Hạn nộp mới -->
            <div>
                <label for="quick_modal_due_date_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Hạn nộp mới <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local"
                       id="quick_modal_due_date_input"
                       name="due_date"
                       required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/20 outline-none transition shadow-sm">
            </div>

            <!-- Phím tắt gia hạn nhanh -->
            <div>
                <label class="block text-xs text-slate-500 mb-1.5 font-medium">Chọn nhanh mốc gia hạn:</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="setQuickDate(1)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +1 Ngày
                    </button>
                    <button type="button" onclick="setQuickDate(3)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +3 Ngày
                    </button>
                    <button type="button" onclick="setQuickDate(7)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +1 Tuần
                    </button>
                    <button type="button" onclick="setQuickWeekend()" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        Chủ Nhật
                    </button>
                </div>
            </div>

            <!-- Checkbox gửi thông báo gia hạn cho lớp -->
            <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-3.5">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="notify_students" value="1" checked class="mt-0.5 rounded border-slate-300 text-[#1e4f8f] focus:ring-[#1e4f8f]">
                    <div class="text-xs">
                        <span class="font-bold text-slate-800">Gửi thông báo gia hạn nộp bài đến học sinh trong lớp</span>
                        <p class="text-slate-500 mt-0.5 leading-relaxed">Học sinh sẽ nhận thông báo chuông về hạn nộp mới cập nhật.</p>
                    </div>
                </label>
            </div>

            <!-- Nút hành động -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeExtendModal()" class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-600 transition shadow-sm">
                    Hủy bỏ
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#1e4f8f] hover:bg-[#163c6d] px-5 py-2.5 text-xs font-bold text-white transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Lưu hạn nộp mới
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('click', function(event) {
        var container = document.getElementById('assignDropdownContainer');
        var menu = document.getElementById('assignDropdownMenu');
        if (container && menu && !container.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });

    function openExtendModal(actionUrl, title, dueDateRaw, dueDateFormatted) {
        const modal = document.getElementById('quickExtendDeadlineModal');
        const form = document.getElementById('quickExtendForm');
        const titleEl = document.getElementById('quickModalTitle');
        const oldDueEl = document.getElementById('quickModalOldDue');
        const input = document.getElementById('quick_modal_due_date_input');

        if (form) form.action = actionUrl;
        if (titleEl) titleEl.textContent = title;
        if (oldDueEl) oldDueEl.textContent = dueDateFormatted;
        if (input) {
            input.value = dueDateRaw || formatDateTimeLocal(new Date(Date.now() + 3*86400000));
        }
        if (modal) modal.classList.remove('hidden');
    }

    function closeExtendModal() {
        const modal = document.getElementById('quickExtendDeadlineModal');
        if (modal) modal.classList.add('hidden');
    }

    function setQuickDate(days) {
        const input = document.getElementById('quick_modal_due_date_input');
        if (!input) return;

        let baseTime = new Date();
        const currentVal = input.value;
        if (currentVal) {
            const parsed = new Date(currentVal);
            if (!isNaN(parsed) && parsed > baseTime) {
                baseTime = parsed;
            }
        }

        baseTime.setDate(baseTime.getDate() + days);
        baseTime.setHours(23, 59, 0, 0);

        input.value = formatDateTimeLocal(baseTime);
    }

    function setQuickWeekend() {
        const input = document.getElementById('quick_modal_due_date_input');
        if (!input) return;

        let d = new Date();
        const day = d.getDay();
        const diff = (7 - day) % 7;
        d.setDate(d.getDate() + (diff === 0 ? 7 : diff));
        d.setHours(23, 59, 0, 0);

        input.value = formatDateTimeLocal(d);
    }

    function formatDateTimeLocal(d) {
        const pad = (n) => String(n).padStart(2, '0');
        const year = d.getFullYear();
        const month = pad(d.getMonth() + 1);
        const date = pad(d.getDate());
        const hours = pad(d.getHours());
        const minutes = pad(d.getMinutes());
        return `${year}-${month}-${date}T${hours}:${minutes}`;
    }
</script>
@endpush
@include('components.toast')
@endsection
