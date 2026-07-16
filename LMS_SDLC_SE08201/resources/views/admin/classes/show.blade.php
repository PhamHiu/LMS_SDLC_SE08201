@extends('layouts.app')

@section('title', 'Lớp '.$class->class_name)

@section('content')
{{-- Thông tin lớp --}}
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-800">{{ $class->class_name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $class->subject?->subject_code }} — {{ $class->subject?->subject_name }} · Ngành {{ $class->major?->major_name }}</p>
            <p class="mt-1 text-sm text-slate-500">👨‍🏫 GV: <b class="text-slate-700">{{ $class->teacher?->full_name }}</b>
               · 🗓️ {{ $class->start_date?->format('d/m/Y') }} → {{ $class->end_date?->format('d/m/Y') }}
               · {{ $class->total_sessions }} buổi</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $class->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($class->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $class->statusLabel() }}</span>
            <a href="{{ route('admin.classes.edit', $class) }}" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Sửa lớp</a>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Danh sách sinh viên --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-bold text-slate-800">Sinh viên trong lớp ({{ $class->students->count() }})</h3>
        </div>
        <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($class->students as $sv)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">{{ mb_substr($sv->full_name, 0, 1) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-700">{{ $sv->full_name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $sv->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.classes.students.remove', [$class, $sv]) }}" onsubmit="return confirm('Xóa {{ $sv->full_name }} khỏi lớp?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                    </form>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Chưa có sinh viên nào trong lớp.</li>
            @endforelse
        </ul>
    </div>

    {{-- Xếp sinh viên vào lớp --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-bold text-slate-800">Xếp sinh viên vào lớp</h3>
        </div>
        <div class="px-5 pt-4 flex gap-3">
            <input type="text" id="student_search" placeholder="Tìm tên, email..." class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 outline-none">
            <select id="student_major_filter" class="w-1/3 rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-indigo-500 outline-none">
                <option value="">Tất cả ngành</option>
                @foreach ($majors as $major)
                    <option value="{{ $major->major_id }}" {{ $class->major_id == $major->major_id ? 'selected' : '' }}>{{ $major->major_code }}</option>
                @endforeach
            </select>
        </div>
        <form method="POST" action="{{ route('admin.classes.students.enroll', $class) }}" class="p-5 flex-1 flex flex-col">
            @csrf
            <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-3" id="student_list">
                @forelse ($availableStudents as $sv)
                    <label class="student-item flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50"
                           data-name="{{ strtolower($sv->full_name) }}"
                           data-email="{{ strtolower($sv->email) }}"
                           data-major="{{ $sv->major_id }}">
                        <input type="checkbox" name="student_ids[]" value="{{ $sv->user_id }}" class="student-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="font-medium text-slate-700">{{ $sv->full_name }}</span>
                        <span class="text-xs text-slate-400">{{ $sv->email }} @if($sv->major) · {{ $sv->major->major_code }} @endif</span>
                    </label>
                @empty
                    <p class="py-4 text-center text-sm text-slate-400" id="empty_message">Tất cả sinh viên đã được xếp lớp.</p>
                @endforelse
                <p class="py-4 text-center text-sm text-slate-400 hidden" id="no_result_message">Không tìm thấy sinh viên phù hợp.</p>
            </div>
            @if ($availableStudents->isNotEmpty())
                <div class="mt-4 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-600 select-none cursor-pointer hover:text-slate-800">
                        <input type="checkbox" id="select_all_students" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Chọn tất cả đang hiển thị
                    </label>
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Xếp vào lớp</button>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Danh sách buổi học --}}
<div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between bg-slate-50">
        <h3 class="font-bold text-slate-800">Lịch các buổi học ({{ $class->sessions->count() }})</h3>
        <!-- Thêm nút Tự động xếp lại toàn bộ TKB (optional) -->
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="bg-white text-slate-500 border-b border-slate-200">
                    <th class="px-5 py-3 font-medium">Buổi số</th>
                    <th class="px-5 py-3 font-medium">Ngày học</th>
                    <th class="px-5 py-3 font-medium">Ca học</th>
                    <th class="px-5 py-3 font-medium">Phòng học</th>
                    <th class="px-5 py-3 font-medium text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($class->sessions as $i => $session)
                    <tr class="hover:bg-slate-50 transition-colors {{ $session->session_date->isPast() ? 'bg-slate-50/50' : '' }}">
                        <td class="px-5 py-3 font-semibold text-slate-700">Buổi {{ $i + 1 }}</td>
                        <td class="px-5 py-3 text-slate-600">
                            {{ $session->session_date->format('d/m/Y') }} 
                            <span class="text-xs text-slate-400 block">{{ $session->session_date->dayName }}</span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">
                            @if($session->slot)
                                {{ $session->slot->label }} ({{ \Carbon\Carbon::parse($session->slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($session->slot->end_time)->format('H:i') }})
                            @else
                                <span class="text-slate-400 italic">Chưa xếp ca</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">
                            @if($session->room)
                                {{ $session->room->room_name }}
                            @else
                                <span class="text-slate-400 italic">Chưa xếp phòng</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="#" class="inline-flex items-center gap-1 rounded bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 edit-session-btn" 
                               data-session-id="{{ $session->session_id }}"
                               data-date="{{ $session->session_date->format('Y-m-d') }}"
                               data-slot="{{ $session->slot_id }}"
                               data-room="{{ $session->room_id }}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Sửa
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Sửa Buổi Học -->
<div id="editSessionModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm transition-opacity items-center justify-center flex">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl relative">
        <button type="button" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 modal-close">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-lg font-bold text-slate-800 mb-4">Cập nhật buổi học</h3>
        
        <div id="conflict_alert" class="mb-4 hidden rounded-lg bg-rose-50 p-3 text-sm text-rose-600 border border-rose-200">
            <!-- Conflict message -->
        </div>

        <form id="editSessionForm" class="space-y-4">
            <input type="hidden" id="edit_session_id">
            <input type="hidden" id="class_teacher_id" value="{{ $class->user_id }}">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ngày học</label>
                <input type="date" id="edit_date" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ca học</label>
                <select id="edit_slot" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-indigo-500 outline-none">
                    <!-- Sẽ load qua JS, hoặc truyền trực tiếp nếu slots toàn cầu -->
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phòng học</label>
                <select id="edit_room" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-indigo-500 outline-none">
                    <!-- Load qua JS -->
                </select>
            </div>
            
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 modal-close">Hủy</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 flex items-center gap-2">
                    <span id="btn_text">Lưu thay đổi</span>
                    <svg id="btn_loader" class="h-4 w-4 animate-spin hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('editSessionModal');
        const form = document.getElementById('editSessionForm');
        const alertBox = document.getElementById('conflict_alert');
        const btnLoader = document.getElementById('btn_loader');
        const btnText = document.getElementById('btn_text');
        
        // Fetch slots & rooms for modal (could also pass from controller to blade directly)
        let slots = [];
        let rooms = [];
        
        async function loadOptions() {
            try {
                // Ta có thể tạo 2 API mỏng để lấy options
                const [resSlots, resRooms] = await Promise.all([
                    fetch('/admin/classes/api/slots'),
                    fetch('/admin/classes/api/rooms')
                ]);
                slots = await resSlots.json();
                rooms = await resRooms.json();
                
                const slotSelect = document.getElementById('edit_slot');
                slotSelect.innerHTML = slots.map(s => `<option value="${s.slot_id}">${s.label}</option>`).join('');
                
                const roomSelect = document.getElementById('edit_room');
                roomSelect.innerHTML = rooms.map(r => `<option value="${r.room_id}">${r.room_name} (${r.capacity})</option>`).join('');
            } catch (e) {
                console.error('Failed to load slots and rooms');
            }
        }
        loadOptions();

        document.querySelectorAll('.edit-session-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('edit_session_id').value = this.dataset.sessionId;
                document.getElementById('edit_date').value = this.dataset.date;
                
                // wait for options to load if not yet
                setTimeout(() => {
                    document.getElementById('edit_slot').value = this.dataset.slot;
                    document.getElementById('edit_room').value = this.dataset.room;
                }, 100);
                
                alertBox.classList.add('hidden');
                modal.classList.remove('hidden');
            });
        });
        
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.classList.add('hidden'));
        });
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            btnLoader.classList.remove('hidden');
            btnText.textContent = 'Đang lưu...';
            
            const sessionId = document.getElementById('edit_session_id').value;
            const data = {
                session_date: document.getElementById('edit_date').value,
                slot_id: document.getElementById('edit_slot').value,
                room_id: document.getElementById('edit_room').value,
                teacher_id: document.getElementById('class_teacher_id').value,
                _token: '{{ csrf_token() }}'
            };
            
            try {
                const res = await fetch(`/admin/classes/api/sessions/${sessionId}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await res.json();
                
                if (!res.ok) {
                    alertBox.textContent = result.message || 'Lỗi hệ thống';
                    alertBox.classList.remove('hidden');
                } else {
                    if (result.is_conflict) {
                        alertBox.innerHTML = result.messages.join('<br>');
                        alertBox.classList.remove('hidden');
                    } else {
                        // Success
                        window.location.reload();
                    }
                }
            } catch(e) {
                alertBox.textContent = 'Lỗi kết nối';
                alertBox.classList.remove('hidden');
            } finally {
                btnLoader.classList.add('hidden');
                btnText.textContent = 'Lưu thay đổi';
            }
        });

        // Student filter logic
        const studentSearch = document.getElementById('student_search');
        const studentMajor = document.getElementById('student_major_filter');
        const studentItems = document.querySelectorAll('.student-item');
        const noResultMessage = document.getElementById('no_result_message');
        const selectAllCheckbox = document.getElementById('select_all_students');
        
        function filterStudents() {
            if (!studentSearch) return;
            const text = studentSearch.value.toLowerCase();
            const major = studentMajor.value;
            let visibleCount = 0;
            
            studentItems.forEach(item => {
                const nameMatch = item.dataset.name.includes(text) || item.dataset.email.includes(text);
                const majorMatch = major === "" || item.dataset.major === major;
                
                if (nameMatch && majorMatch) {
                    item.classList.remove('hidden');
                    item.classList.add('flex');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                    item.classList.remove('flex');
                }
            });
            
            if (visibleCount === 0 && studentItems.length > 0) {
                noResultMessage.classList.remove('hidden');
            } else if (noResultMessage) {
                noResultMessage.classList.add('hidden');
            }
            
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
        }
        
        if (studentSearch) {
            studentSearch.addEventListener('input', filterStudents);
            studentMajor.addEventListener('change', filterStudents);
            filterStudents();
        }
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                studentItems.forEach(item => {
                    if (!item.classList.contains('hidden')) {
                        item.querySelector('input[type="checkbox"]').checked = isChecked;
                    }
                });
            });
        }
    });
</script>
@endsection
