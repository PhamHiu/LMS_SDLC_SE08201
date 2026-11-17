@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="glass-card-3d p-6 relative overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.classes.show', $assignment->classRoom) }}" class="tool-btn !p-2" title="Quay lại">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-xl font-semibold text-slate-900">{{ $assignment->title }}</h2>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase {{ $assignment->isQuiz() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-[#1e4f8f]/10 text-[#1e4f8f] border border-[#1e4f8f]/30' }}">{{ $assignment->typeLabel() }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600">Lớp {{ $assignment->classRoom?->class_name }} · Hạn nộp: <strong class="text-slate-900">{{ $assignment->due_date->format('H:i d/m/Y') }}</strong> · Thang điểm <span class="font-bold text-[#1e4f8f]">{{ $assignment->max_score }}</span></p>
            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                <svg class="w-4 h-4 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>{{ $submissionCount }}/{{ $studentCount }}</strong> sinh viên đã nộp</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openExtendDeadlineModal()" class="tool-btn !bg-amber-50 hover:!bg-amber-100 !text-amber-800 !border-amber-200" title="Sửa hạn nộp bài">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Sửa hạn nộp</span>
            </button>
            <a href="{{ route('teacher.assignments.edit', $assignment) }}" class="tool-btn !bg-slate-50 hover:!bg-slate-100 !text-slate-900">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Sửa bài tập</span>
            </a>
            <a href="{{ route('teacher.grading.index', $assignment) }}" class="tool-btn !bg-[#1e4f8f] !text-white !border-[#1e4f8f]/30">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Xem bài nộp / Chấm điểm</span>
            </a>
        </div>
    </div>
    @if ($assignment->description)
        <div class="mt-4 whitespace-pre-line rounded-md bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700 leading-relaxed">{{ $assignment->description }}</div>
    @endif
    @if ($assignment->attachment_path)
        <div class="mt-4 flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tài liệu đính kèm:</span>
            <a href="{{ route('teacher.assignments.download-attachment', $assignment) }}" class="tool-btn !bg-slate-50 hover:!bg-slate-100 !text-[#1e4f8f]">
                <svg class="w-4 h-4 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Tải xuống</span>
            </a>
        </div>
    @endif
</div>

@if ($assignment->isQuiz())
    <div class="mt-6 glass-card-3d">
        <div class="border-b border-slate-200 px-5 py-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="font-semibold text-slate-900">Bộ câu hỏi trắc nghiệm ({{ $assignment->questions->count() }})</h3>
        </div>
        <div class="space-y-4 p-5">
            @foreach ($assignment->questions as $i => $q)
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Câu {{ $i + 1 }}: {{ $q->content }}</p>
                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2 text-sm">
                        @foreach ($q->options() as $key => $option)
                            <p class="rounded-lg px-3 py-1.5 {{ $key === $q->correct_answer ? 'bg-emerald-50 font-bold text-emerald-700 border border-emerald-200' : 'bg-slate-50 text-slate-600' }}">
                                {{ $key }}. {{ $option }} {{ $key === $q->correct_answer ? '✓' : '' }}
                            </p>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mt-6">
    @include('shared.discussions', ['storeRoute' => route('teacher.discussions.store', $assignment)])
</div>

<!-- ========================================================================= -->
<!-- MODAL SỬA HẠN NỘP BÀI / GIA HẠN NỘP BÀI TẬP                             -->
<!-- ========================================================================= -->
<div id="extendDeadlineModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- Header modal -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-4">
            <div class="flex items-center gap-2.5">
                <div class="rounded-lg bg-[#1e4f8f]/10 p-2 text-[#1e4f8f]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Sửa / Gia hạn hạn nộp bài tập</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $assignment->title }} ({{ $assignment->classRoom?->class_name }})</p>
                </div>
            </div>
            <button type="button" onclick="closeExtendDeadlineModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body Form -->
        <form method="POST" action="{{ route('teacher.assignments.extend-deadline', $assignment) }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Hạn nộp hiện tại -->
            <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500">Hạn nộp hiện tại:</span>
                    <div class="text-sm font-bold {{ $assignment->isOverdue() ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $assignment->due_date ? $assignment->due_date->format('H:i, d/m/Y') : 'Không giới hạn' }}
                        @if ($assignment->isOverdue())
                            <span class="ml-1 text-xs font-semibold text-rose-500">(Đã quá hạn)</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-500">Đã nộp:</span>
                    <div class="text-sm font-extrabold text-[#1e4f8f]">{{ $submissionCount }}/{{ $studentCount }} SV</div>
                </div>
            </div>

            <!-- Input Hạn nộp mới -->
            <div>
                <label for="modal_due_date_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Hạn nộp mới <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local"
                       id="modal_due_date_input"
                       name="due_date"
                       value="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : now()->addDays(3)->format('Y-m-d\TH:i') }}"
                       required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/20 outline-none transition shadow-sm">
            </div>

            <!-- Phím tắt gia hạn nhanh -->
            <div>
                <label class="block text-xs text-slate-500 mb-1.5 font-medium">Chọn nhanh mốc gia hạn:</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="setQuickDeadline(1)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +1 Ngày
                    </button>
                    <button type="button" onclick="setQuickDeadline(3)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +3 Ngày
                    </button>
                    <button type="button" onclick="setQuickDeadline(7)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +1 Tuần
                    </button>
                    <button type="button" onclick="setWeekendDeadline()" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        Chủ Nhật
                    </button>
                </div>
            </div>

            <!-- Checkbox gửi thông báo gia hạn cho lớp -->
            <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-3.5">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="notify_students" value="1" checked class="mt-0.5 rounded border-slate-300 text-[#1e4f8f] focus:ring-[#1e4f8f]">
                    <div class="text-xs">
                        <span class="font-bold text-slate-800">Gửi thông báo gia hạn nộp bài đến toàn bộ sinh viên trong lớp</span>
                        <p class="text-slate-500 mt-0.5 leading-relaxed">Hệ thống sẽ tạo thông báo chuông cho sinh viên kèm thông tin hạn nộp mới để các em kịp thời nộp bài.</p>
                    </div>
                </label>
            </div>

            <!-- Nút hành động -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeExtendDeadlineModal()" class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-600 transition shadow-sm">
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

<script>
function openExtendDeadlineModal() {
    const modal = document.getElementById('extendDeadlineModal');
    if (modal) modal.classList.remove('hidden');
}

function closeExtendDeadlineModal() {
    const modal = document.getElementById('extendDeadlineModal');
    if (modal) modal.classList.add('hidden');
}

function setQuickDeadline(days) {
    const input = document.getElementById('modal_due_date_input');
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

function setWeekendDeadline() {
    const input = document.getElementById('modal_due_date_input');
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
@include('components.toast')
@endsection
