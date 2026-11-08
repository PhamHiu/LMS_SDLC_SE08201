@extends('layouts.app')

@section('title', 'Lớp '.$class->class_name)

@section('content')
{{-- Thông tin lớp --}}
<div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl p-6 shadow-xl glass-card">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.classes.index') }}" class="text-xs font-semibold text-slate-400 hover:text-[#D4FF24] transition">← Danh sách lớp</a>
            </div>
            <h2 class="text-2xl font-black !text-white flex items-center gap-2.5">
                <svg class="w-6 h-6 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
                <span>{{ $class->class_name }}</span>
            </h2>
            <p class="mt-1 text-sm !text-slate-300">{{ $class->subject?->subject_code }} — {{ $class->subject?->subject_name }} · Ngành {{ $class->major?->major_name }}</p>
            <p class="mt-2 text-sm !text-slate-300 flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>GV: <b class="!text-white">{{ $class->teacher?->full_name }}</b></span>
                </span>
                <span class="text-slate-500">·</span>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ $class->start_date?->format('d/m/Y') }} → {{ $class->end_date?->format('d/m/Y') }}</span>
                </span>
                <span class="text-slate-500">·</span>
                <span>{{ $class->total_sessions }} buổi</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full px-3 py-1 text-xs font-bold border {{ $class->status === 1 ? 'bg-emerald-500/20 border-emerald-400/30 text-emerald-300' : ($class->status === 0 ? 'bg-amber-500/20 border-amber-400/30 text-amber-300' : 'bg-white/10 border-white/10 text-slate-400') }}">{{ $class->statusLabel() }}</span>
            <a href="{{ route('admin.classes.edit', $class) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-semibold !text-white shadow-sm transition">
                <svg class="w-4 h-4 text-white inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Sửa lớp</span>
            </a>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Danh sách sinh viên --}}
    <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl shadow-xl glass-card">
        <div class="border-b border-white/10 px-5 py-4">
            <h3 class="font-bold !text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Sinh viên trong lớp ({{ $class->students->count() }})</span>
            </h3>
        </div>
        <ul class="max-h-96 divide-y divide-white/5 overflow-y-auto">
            @forelse ($class->students as $sv)
                <li class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-white/5 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#1558C9]/30 border border-[#1558C9]/50 text-xs font-black text-[#D4FF24]">{{ mb_substr($sv->full_name, 0, 1) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold !text-white">{{ $sv->full_name }}</p>
                            <p class="truncate text-xs !text-slate-300">
                                @if($sv->student_code) <span class="font-semibold text-sky-400">{{ $sv->student_code }}</span> • @endif
                                {{ $sv->email }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.classes.students.remove', [$class, $sv]) }}" onsubmit="return confirm('Xóa {{ $sv->full_name }} khỏi lớp?')">
                        @csrf @method('DELETE')
                        <button class="inline-flex items-center gap-1 rounded-lg bg-rose-600 hover:bg-rose-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition">
                            <svg class="w-3.5 h-3.5 text-white inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Xóa</span>
                        </button>
                    </form>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm !text-slate-400">Chưa có sinh viên nào trong lớp.</li>
            @endforelse
        </ul>
    </div>

    {{-- Xếp sinh viên vào lớp --}}
    <div class="rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl shadow-xl glass-card flex flex-col">
        <div class="border-b border-white/10 px-5 py-4">
            <h3 class="font-bold !text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Xếp sinh viên vào lớp</span>
            </h3>
        </div>
        <div class="px-5 pt-4 flex gap-3">
            <input type="text" id="student_search" placeholder="Tìm mã, tên, email..." class="flex-1 rounded-xl border border-white/15 bg-[#030407]/90 px-3.5 py-2 text-sm !text-white placeholder-slate-400 focus:border-[#1558C9] outline-none">
            <select id="student_major_filter" class="w-1/3 rounded-xl border border-white/15 bg-[#030407]/90 px-3 py-2 text-sm !text-white focus:border-[#1558C9] outline-none">
                <option value="" class="bg-[#030407] text-slate-300">Tất cả ngành</option>
                @foreach ($majors as $major)
                    <option value="{{ $major->major_id }}" class="bg-[#030407] text-white" {{ $class->major_id == $major->major_id ? 'selected' : '' }}>{{ $major->major_code }}</option>
                @endforeach
            </select>
        </div>
        <form method="POST" action="{{ route('admin.classes.students.enroll', $class) }}" class="p-5 flex-1 flex flex-col">
            @csrf
            <div class="max-h-72 space-y-1 overflow-y-auto rounded-xl border border-white/10 bg-white/5 p-3" id="student_list">
                @forelse ($availableStudents as $sv)
                    <label class="student-item flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm hover:bg-white/10 transition cursor-pointer"
                           data-code="{{ strtolower($sv->student_code ?? '') }}"
                           data-name="{{ strtolower($sv->full_name) }}"
                           data-email="{{ strtolower($sv->email) }}"
                           data-major="{{ $sv->major_id }}">
                        <input type="checkbox" name="student_ids[]" value="{{ $sv->user_id }}" class="student-checkbox rounded border-white/20 text-[#1558C9] focus:ring-[#1558C9]">
                        <span class="font-medium !text-white">
                            @if($sv->student_code) <span class="text-sky-400 font-semibold mr-1">[{{ $sv->student_code }}]</span> @endif
                            {{ $sv->full_name }}
                        </span>
                        <span class="text-xs !text-slate-300">{{ $sv->email }} @if($sv->major) · {{ $sv->major->major_code }} @endif</span>
                    </label>
                @empty
                    <p class="py-4 text-center text-sm !text-slate-400" id="empty_message">Tất cả sinh viên đã được xếp lớp.</p>
                @endforelse
                <p class="py-4 text-center text-sm !text-slate-400 hidden" id="no_result_message">Không tìm thấy sinh viên phù hợp.</p>
            </div>
            @if ($availableStudents->isNotEmpty())
                <div class="mt-4 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm !text-slate-200 select-none cursor-pointer hover:!text-white">
                        <input type="checkbox" id="select_all_students" class="rounded border-white/20 text-[#1558C9] focus:ring-[#1558C9]">
                        Chọn tất cả đang hiển thị
                    </label>
                    <button class="rounded-xl bg-[#1558C9] hover:bg-[#1558C9]/85 px-5 py-2.5 text-sm font-semibold !text-white shadow-lg shadow-[#1558C9]/30 transition">Xếp vào lớp</button>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Danh sách buổi học --}}
<div class="mt-6 rounded-2xl border border-white/10 bg-[#0a0d17]/80 backdrop-blur-xl shadow-xl glass-card overflow-hidden">
    <div class="border-b border-white/10 px-5 py-4 flex items-center justify-between bg-white/5">
        <h3 class="font-bold !text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Lịch các buổi học ({{ $class->sessions->count() }})</span>
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="bg-white/5 !text-slate-200 border-b border-white/10">
                    <th class="px-5 py-3.5 font-bold">Buổi số</th>
                    <th class="px-5 py-3.5 font-bold">Ngày học</th>
                    <th class="px-5 py-3.5 font-bold">Ca học</th>
                    <th class="px-5 py-3.5 font-bold">Phòng học</th>
                    <th class="px-5 py-3.5 font-bold text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach ($class->sessions as $i => $session)
                    <tr class="hover:bg-white/5 transition duration-150 {{ $session->session_date->isPast() ? 'bg-white/[0.02]' : '' }}">
                        <td class="px-5 py-3.5 font-semibold !text-white">Buổi {{ $i + 1 }}</td>
                        <td class="px-5 py-3.5 !text-slate-200">
                            {{ $session->session_date->format('d/m/Y') }} 
                            <span class="text-xs !text-slate-400 block">{{ $session->session_date->dayName }}</span>
                        </td>
                        <td class="px-5 py-3.5 !text-slate-200">
                            @if($session->slot)
                                <span class="inline-flex items-center rounded-lg bg-indigo-500/20 border border-indigo-400/30 px-2.5 py-0.5 text-xs font-semibold text-indigo-300">
                                    {{ $session->slot->label }} ({{ \Carbon\Carbon::parse($session->slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($session->slot->end_time)->format('H:i') }})
                                </span>
                            @else
                                <span class="!text-slate-400 italic">Chưa xếp ca</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 !text-slate-200">
                            @if($session->room)
                                <span class="px-2 py-0.5 rounded-md bg-white/10 border border-white/10 text-xs !text-slate-200">{{ $session->room->room_name }}</span>
                            @else
                                <span class="!text-slate-400 italic">Chưa xếp phòng</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="#" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-semibold !text-white shadow-sm transition edit-session-btn" 
                               data-session-id="{{ $session->session_id }}"
                               data-date="{{ $session->session_date->format('Y-m-d') }}"
                               data-slot="{{ $session->slot_id }}"
                               data-room="{{ $session->room_id }}">
                                <svg class="h-3.5 w-3.5 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                <span>Sửa</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Sửa Buổi Học -->
<div id="editSessionModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md transition-opacity items-center justify-center flex p-4">
    <div class="w-full max-w-md rounded-2xl bg-[#0a0d17] border border-white/15 p-6 shadow-2xl relative !text-white">
        <button type="button" class="absolute top-4 right-4 text-slate-400 hover:text-white modal-close">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-lg font-bold !text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-[#D4FF24] filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Cập nhật buổi học</span>
        </h3>
        
        <div id="conflict_alert" class="mb-4 hidden rounded-xl bg-rose-500/20 p-3 text-sm text-rose-300 border border-rose-400/30">
            <!-- Conflict message -->
        </div>

        <form id="editSessionForm" class="space-y-4">
            <input type="hidden" id="edit_session_id">
            <input type="hidden" id="class_teacher_id" value="{{ $class->user_id }}">
            
            <div>
                <label class="block text-sm font-medium !text-slate-200 mb-1">Ngày học</label>
                <input type="date" id="edit_date" required class="w-full rounded-xl border border-white/15 bg-[#030407] px-3.5 py-2 text-sm !text-white focus:border-[#1558C9] outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium !text-slate-200 mb-1">Ca học</label>
                <select id="edit_slot" required class="w-full rounded-xl border border-white/15 bg-[#030407] px-3.5 py-2 text-sm !text-white focus:border-[#1558C9] outline-none">
                    <!-- Sẽ load qua JS -->
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium !text-slate-200 mb-1">Phòng học</label>
                <select id="edit_room" required class="w-full rounded-xl border border-white/15 bg-[#030407] px-3.5 py-2 text-sm !text-white focus:border-[#1558C9] outline-none">
                    <!-- Load qua JS -->
                </select>
            </div>
            
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium !text-white hover:bg-white/20 modal-close transition">Hủy</button>
                <button type="submit" class="rounded-xl bg-[#1558C9] hover:bg-[#1558C9]/85 px-4 py-2 text-sm font-semibold !text-white shadow-md shadow-[#1558C9]/30 flex items-center gap-2 transition">
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
        
        let slots = [];
        let rooms = [];
        
        async function loadOptions() {
            try {
                const [resSlots, resRooms] = await Promise.all([
                    fetch('/admin/classes/api/slots'),
                    fetch('/admin/classes/api/rooms')
                ]);
                slots = await resSlots.json();
                rooms = await resRooms.json();
                
                const slotSelect = document.getElementById('edit_slot');
                slotSelect.innerHTML = slots.map(s => `<option value="${s.slot_id}" class="bg-[#030407] text-white">${s.label}</option>`).join('');
                
                const roomSelect = document.getElementById('edit_room');
                roomSelect.innerHTML = rooms.map(r => `<option value="${r.room_id}" class="bg-[#030407] text-white">${r.room_name} (${r.capacity})</option>`).join('');
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
                const nameMatch = item.dataset.name.includes(text) || item.dataset.email.includes(text) || item.dataset.code.includes(text);
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
