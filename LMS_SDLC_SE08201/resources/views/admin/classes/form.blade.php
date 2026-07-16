@extends('layouts.app')

@section('title', $class ? 'Sửa lớp học' : 'Tạo lớp học')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-1 text-base font-bold text-slate-800">{{ $class ? 'Cập nhật: '.$class->class_name : 'Tạo lớp học mới' }}</h2>
    @unless ($class)
        <p class="mb-5 text-sm text-slate-500">Thiết lập thông tin và thời khóa biểu để hệ thống tự động sinh buổi học.</p>
    @endunless

    <!-- Display Validation Errors if any -->
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-600">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="class_form" method="POST" action="{{ $class ? route('admin.classes.update', $class) : route('admin.classes.store') }}" class="mt-4 space-y-6">
        @csrf
        @if ($class) @method('PUT') @endif

        <!-- Khu vực 1: Thông tin lớp học -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tên lớp *</label>
                <input type="text" name="class_name" value="{{ old('class_name', $class?->class_name) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Chuyên ngành *</label>
                <select name="major_id" id="major_select" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Chọn ngành —</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->major_id }}" @selected(old('major_id', $class?->major_id) == $major->major_id)>{{ $major->major_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Môn học *</label>
                <select name="subject_id" id="subject_select" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-slate-50 outline-none" disabled data-selected="{{ old('subject_id', $class?->subject_id) }}">
                    <option value="">— Vui lòng chọn ngành trước —</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Giảng viên phụ trách *</label>
                <select name="user_id" id="teacher_select" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-slate-50 outline-none" disabled data-selected="{{ old('user_id', $class?->user_id) }}">
                    <option value="">— Vui lòng chọn ngành trước —</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái *</label>
                <select name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="0" @selected(old('status', (string) $class?->status) === '0')>Chưa mở</option>
                    <option value="1" @selected(old('status', (string) $class?->status) === '1')>Đang học</option>
                    <option value="2" @selected(old('status', (string) $class?->status) === '2')>Đã đóng</option>
                </select>
            </div>
            
            @if ($class)
                <!-- Edit mode only shows end date from DB -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ngày kết thúc</label>
                    <input type="date" value="{{ $class->end_date?->format('Y-m-d') }}" disabled
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm bg-slate-100 outline-none cursor-not-allowed text-slate-500">
                </div>
            @endif
        </div>

        @unless ($class)
        <!-- Khu vực 2: Thời khóa biểu (Chỉ dành cho tạo mới) -->
        <div class="border-t border-slate-200 pt-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Cài đặt Thời khóa biểu</h3>
            
            <div class="grid gap-4 sm:grid-cols-3 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phòng học (Mặc định)</label>
                    <select name="room_id" id="room_select" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                        <option value="">— Chọn phòng học —</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->room_id }}" @selected(old('room_id') == $room->room_id)>{{ $room->room_name }} (Sức chứa: {{ $room->capacity }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ngày bắt đầu *</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Số buổi học *</label>
                    <input type="number" name="total_sessions" id="total_sessions" value="{{ old('total_sessions', 12) }}" min="1" max="60" required
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex items-end justify-between mb-2">
                <div>
                    <span class="text-sm font-medium text-slate-700">Lưới thời khóa biểu hàng tuần</span>
                    <p class="text-xs text-slate-500 mt-1">Click vào các ô trống để chọn ca học. Có thể chọn nhiều ca trong một tuần.</p>
                </div>
                <div class="text-right">
                    <span class="text-sm text-slate-600">Ngày kết thúc dự kiến: </span>
                    <span id="end_date_display" class="font-semibold text-indigo-600">...</span>
                </div>
            </div>

            <!-- Bảng TKB dạng lưới -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full min-w-[700px] border-collapse bg-white text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-32">Ca / Thứ</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 2</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 3</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 4</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 5</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 6</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 7</th>
                            <th class="border-b border-slate-200 py-3 px-2 font-medium w-[12%] text-rose-600">Chủ nhật</th>
                        </tr>
                    </thead>
                    <tbody id="timetable_body">
                        @foreach ($slots as $slot)
                            <tr>
                                <td class="border-b border-r border-slate-200 bg-slate-50 py-2 px-3 text-center text-xs font-medium text-slate-600">
                                    {{ $slot->label }}
                                </td>
                                @for ($day = 1; $day <= 7; $day++)
                                    <td class="border-b border-r border-slate-200 p-1 last:border-r-0 relative group">
                                        <button type="button" 
                                            class="tkb-cell w-full h-16 rounded flex flex-col items-center justify-center border border-dashed border-slate-300 bg-slate-50 hover:border-indigo-400 hover:bg-indigo-50 transition-colors disabled:cursor-not-allowed disabled:opacity-75 disabled:hover:border-slate-300 disabled:hover:bg-slate-50"
                                            data-day="{{ $day }}" 
                                            data-slot-id="{{ $slot->slot_id }}"
                                            title="Ca {{ $slot->slot_number }} - Thứ {{ $day === 7 ? 'Chủ nhật' : $day + 1 }}">
                                            <span class="text-xs text-slate-400 font-medium group-hover:text-indigo-500 pointer-events-none cell-text">+ Chọn</span>
                                        </button>
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 flex items-center justify-between text-xs">
                <div class="flex gap-4">
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-50 border border-slate-300 border-dashed"></span> Trống</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-indigo-500 border border-indigo-600"></span> Đã chọn</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-300"></span> Xung đột</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-200 border border-slate-300"></span> Đã có lớp khác</div>
                </div>
                <div class="font-medium text-slate-700" id="selection_counter">
                    Đã chọn: <span class="text-indigo-600 font-bold" id="selected_count">0</span> ca/tuần
                </div>
            </div>

            <!-- Hidden input store selected slots JSON -->
            <input type="hidden" name="selected_slots" id="selected_slots" value="{{ old('selected_slots', '[]') }}">
        </div>
        @endunless

        <div class="flex gap-3 pt-4 border-t border-slate-100">
            <button type="submit" id="submit_btn" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                {{ $class ? 'Lưu thay đổi' : 'Tạo lớp học' }}
            </button>
            <a href="{{ route('admin.classes.index') }}" class="rounded-lg bg-slate-100 px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200 transition-colors">Hủy</a>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const majorSelect = document.getElementById('major_select');
        const subjectSelect = document.getElementById('subject_select');
        const teacherSelect = document.getElementById('teacher_select');
        const roomSelect = document.getElementById('room_select');
        const startDateInput = document.getElementById('start_date');
        const totalSessionsInput = document.getElementById('total_sessions');
        const endDateDisplay = document.getElementById('end_date_display');
        const tkbCells = document.querySelectorAll('.tkb-cell');
        const selectedSlotsInput = document.getElementById('selected_slots');
        const selectedCountDisplay = document.getElementById('selected_count');
        const classForm = document.getElementById('class_form');
        
        let existingTimetable = [];
        let selectedSlots = selectedSlotsInput ? JSON.parse(selectedSlotsInput.value || '[]') : [];
        
        majorSelect.addEventListener('change', async function() {
            const majorId = this.value;
            
            subjectSelect.innerHTML = '<option value="">— Chọn môn học —</option>';
            teacherSelect.innerHTML = '<option value="">— Chọn giảng viên —</option>';
            
            if (!majorId) {
                subjectSelect.disabled = true;
                teacherSelect.disabled = true;
                return;
            }
            
            subjectSelect.disabled = false;
            teacherSelect.disabled = false;
            
            fetch(`/admin/classes/api/subjects-by-major?major_id=${majorId}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(sub => {
                        const option = new Option(`${sub.subject_code} — ${sub.subject_name}`, sub.subject_id);
                        subjectSelect.add(option);
                    });
                    if (subjectSelect.dataset.selected) {
                        subjectSelect.value = subjectSelect.dataset.selected;
                    }
                });
                
            fetch(`/admin/classes/api/teachers-by-major?major_id=${majorId}`)
                .then(res => res.json())
                .then(data => {
                    let hasNullMajor = false;
                    data.forEach(t => {
                        if (t.major_id === null && !hasNullMajor) {
                            const groupOpt = document.createElement('optgroup');
                            groupOpt.label = '— Chưa phân ngành —';
                            teacherSelect.appendChild(groupOpt);
                            hasNullMajor = true;
                        }
                        const option = new Option(t.full_name, t.user_id);
                        if (t.major_id === null) {
                            teacherSelect.lastChild.appendChild(option);
                        } else {
                            teacherSelect.add(option);
                        }
                    });
                    if (teacherSelect.dataset.selected) {
                        teacherSelect.value = teacherSelect.dataset.selected;
                    }
                });
        });
        
        if (majorSelect.value) {
            majorSelect.dispatchEvent(new Event('change'));
        }

        async function loadExistingTimetable() {
            if (!startDateInput) return;
            const startDate = startDateInput.value;
            const teacherId = teacherSelect.value;
            const roomId = roomSelect.value;
            
            if (!startDate) return;
            
            let url = `/admin/classes/api/timetable?start_date=${startDate}`;
            if (teacherId) url += `&teacher_id=${teacherId}`;
            if (roomId) url += `&room_id=${roomId}`;
            
            const res = await fetch(url);
            existingTimetable = await res.json();
            
            renderTimetable();
        }
        
        if (startDateInput) {
            startDateInput.addEventListener('change', loadExistingTimetable);
            teacherSelect.addEventListener('change', loadExistingTimetable);
            roomSelect.addEventListener('change', loadExistingTimetable);
            totalSessionsInput.addEventListener('input', calculateEndDate);
        }
        
        function renderTimetable() {
            tkbCells.forEach(cell => {
                cell.classList.remove('bg-indigo-500', 'border-indigo-600', 'bg-rose-100', 'border-rose-300', 'bg-slate-200', 'border-slate-300');
                cell.classList.add('bg-slate-50', 'border-slate-300');
                cell.title = '';
                cell.querySelector('.cell-text').textContent = '+ Chọn';
                cell.querySelector('.cell-text').classList.replace('text-white', 'text-slate-400');
                cell.querySelector('.cell-text').classList.replace('text-rose-700', 'text-slate-400');
                cell.dataset.status = 'empty';
                
                const day = parseInt(cell.dataset.day);
                const slotId = parseInt(cell.dataset.slotId);
                
                const existing = existingTimetable.find(e => e.day_of_week === day && e.slot_id === slotId);
                if (existing) {
                    cell.classList.replace('bg-slate-50', 'bg-slate-200');
                    cell.querySelector('.cell-text').textContent = existing.class_name;
                    cell.title = `Đã có lớp ${existing.class_name}`;
                    cell.dataset.status = 'occupied';
                }
                
                const isSelected = selectedSlots.some(s => s.day_of_week === day && s.slot_id === slotId);
                if (isSelected) {
                    if (cell.dataset.status === 'occupied') {
                        cell.classList.replace('bg-slate-200', 'bg-rose-100');
                        cell.classList.replace('border-slate-300', 'border-rose-300');
                        cell.querySelector('.cell-text').classList.replace('text-slate-400', 'text-rose-700');
                        cell.dataset.status = 'conflict';
                        cell.title = 'Xung đột: ' + cell.title;
                    } else {
                        cell.classList.replace('bg-slate-50', 'bg-indigo-500');
                        cell.classList.replace('border-slate-300', 'border-indigo-600');
                        cell.querySelector('.cell-text').classList.replace('text-slate-400', 'text-white');
                        cell.querySelector('.cell-text').textContent = 'Đã chọn';
                        cell.dataset.status = 'selected';
                    }
                }
            });
            
            updateCounter();
            calculateEndDate();
        }
        
        tkbCells.forEach(cell => {
            cell.addEventListener('click', async function() {
                const day = parseInt(this.dataset.day);
                const slotId = parseInt(this.dataset.slotId);
                
                const idx = selectedSlots.findIndex(s => s.day_of_week === day && s.slot_id === slotId);
                if (idx > -1) {
                    selectedSlots.splice(idx, 1);
                } else {
                    selectedSlots.push({day_of_week: day, slot_id: slotId});
                }
                
                selectedSlotsInput.value = JSON.stringify(selectedSlots);
                
                if (idx === -1 && teacherSelect.value && startDateInput.value) {
                    await loadExistingTimetable();
                } else {
                    renderTimetable();
                }
            });
        });
        
        function updateCounter() {
            if (selectedCountDisplay) {
                selectedCountDisplay.textContent = selectedSlots.length;
            }
        }
        
        function calculateEndDate() {
            if (!startDateInput || !startDateInput.value || selectedSlots.length === 0) {
                if (endDateDisplay) endDateDisplay.textContent = '...';
                return;
            }
            
            const startDate = new Date(startDateInput.value);
            const total = parseInt(totalSessionsInput.value) || 12;
            let count = 0;
            let current = new Date(startDate);
            let end = new Date(startDate);
            
            const jsDayToOurDay = (d) => d === 0 ? 7 : d;
            
            for (let i = 0; i < 365; i++) {
                const ourDay = jsDayToOurDay(current.getDay());
                const slotsThisDay = selectedSlots.filter(s => s.day_of_week === ourDay);
                
                if (slotsThisDay.length > 0) {
                    count += slotsThisDay.length;
                    end = new Date(current);
                }
                if (count >= total) break;
                
                current.setDate(current.getDate() + 1);
            }
            
            if (endDateDisplay) {
                endDateDisplay.textContent = end.toLocaleDateString('vi-VN');
            }
        }
        
        if (startDateInput && startDateInput.value) {
            loadExistingTimetable();
        }

        if (classForm) {
            classForm.addEventListener('submit', function(e) {
                if (selectedSlotsInput && !selectedSlotsInput.disabled && selectedSlots.length === 0) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất 1 ca học trên lưới thời khóa biểu!');
                    return;
                }
                
                const hasConflict = Array.from(tkbCells).some(c => c.dataset.status === 'conflict');
                if (hasConflict) {
                    if (!confirm('Có ca học đang bị xung đột lịch. Bạn có chắc chắn muốn tiếp tục tạo lớp?')) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
</script>
@endsection
