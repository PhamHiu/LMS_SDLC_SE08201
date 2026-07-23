@extends('layouts.app')

@section('title', $class->class_name)

@section('content')

@php
    $statusStyles = [
        \App\Models\ClassRoom::STATUS_PENDING => ['label' => 'Sắp mở', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'bar' => 'bg-amber-400'],
        \App\Models\ClassRoom::STATUS_ACTIVE  => ['label' => 'Đang học', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'bar' => 'bg-emerald-500'],
        \App\Models\ClassRoom::STATUS_CLOSED  => ['label' => 'Đã kết thúc', 'badge' => 'bg-slate-100 text-slate-600 border-slate-200', 'bar' => 'bg-slate-400'],
    ];
    $style = $statusStyles[$class->status] ?? $statusStyles[\App\Models\ClassRoom::STATUS_PENDING];
    $teacher = $class->teacher;
    $weekdays = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];

    $attStyles = [
        \App\Models\Attendance::PRESENT => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        \App\Models\Attendance::LATE    => 'bg-amber-50 text-amber-700 border-amber-200',
        \App\Models\Attendance::ABSENT  => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

{{-- Breadcrumb / back --}}
<a href="{{ route('student.classes.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition-colors hover:text-indigo-600">
    ← Quay lại Lớp của tôi
</a>

{{-- ===== Header lớp ===== --}}
<div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-extrabold text-slate-800">{{ $class->class_name }}</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">
                    @if ($class->subject)
                        {{ $class->subject->subject_code }} — {{ $class->subject->subject_name }}
                    @else
                        <span class="italic text-slate-400">Chưa gán môn học</span>
                    @endif
                </p>
            </div>
            <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-bold {{ $style['badge'] }}">{{ $style['label'] }}</span>
        </div>
    </div>
    <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
        {{-- Giảng viên --}}
        <div class="flex items-center gap-3">
            @if ($teacher && $teacher->avatar)
                <img src="{{ asset('storage/'.$teacher->avatar) }}" class="h-11 w-11 rounded-full object-cover ring-2 ring-indigo-100" alt="Giảng viên">
            @else
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-500 text-base font-bold text-white">{{ $teacher ? mb_substr($teacher->full_name, 0, 1) : '?' }}</span>
            @endif
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Giảng viên</p>
                <p class="truncate text-sm font-semibold text-slate-700">{{ $teacher->full_name ?? 'Chưa phân công' }}</p>
            </div>
        </div>
        {{-- Tiến độ --}}
        <div>
            <div class="mb-1.5 flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-500">Tiến độ</span>
                <span class="font-bold text-slate-700">{{ $held }}/{{ $total ?: '—' }} buổi · {{ $percent }}%</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full {{ $style['bar'] }} transition-all" style="width: {{ $percent }}%"></div>
            </div>
            <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-semibold">
                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700">Có mặt: {{ $attendance['present'] }}</span>
                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-amber-700">Đi muộn: {{ $attendance['late'] }}</span>
                <span class="rounded-md bg-rose-50 px-2 py-0.5 text-rose-700">Vắng: {{ $attendance['absent'] }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ===== Tabs ===== --}}
<div id="classTabs">
    <div class="mb-5 inline-flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
        <button type="button" data-tab="sessions" class="tab-btn rounded-lg px-4 py-1.5 text-sm font-semibold text-slate-600 transition-colors">📅 Buổi học ({{ $sessions->count() }})</button>
        <button type="button" data-tab="assignments" class="tab-btn rounded-lg px-4 py-1.5 text-sm font-semibold text-slate-600 transition-colors">📝 Bài tập & Quiz ({{ $assignments->count() }})</button>
        <button type="button" data-tab="materials" class="tab-btn rounded-lg px-4 py-1.5 text-sm font-semibold text-slate-600 transition-colors">📚 Học liệu ({{ $materials->count() }})</button>
    </div>

    {{-- ===== Tab: Buổi học ===== --}}
    <div data-panel="sessions" class="tab-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse ($sessions as $s)
            @php $isPast = $s->session_date && ! $s->session_date->isAfter(today()); @endphp
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 last:border-b-0 {{ $isPast ? '' : 'bg-indigo-50/30' }}">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg {{ $isPast ? 'bg-slate-100 text-slate-500' : 'bg-indigo-100 text-indigo-600' }}">
                        <span class="text-[10px] font-bold uppercase leading-none">{{ $s->session_date ? $s->session_date->format('m') : '--' }}</span>
                        <span class="text-sm font-extrabold leading-none">{{ $s->session_date ? $s->session_date->format('d') : '--' }}</span>
                    </span>
                    <div class="text-sm">
                        <p class="font-semibold text-slate-700">
                            {{ $s->session_date ? $weekdays[$s->session_date->dayOfWeek].', '.$s->session_date->format('d/m/Y') : 'Chưa xếp lịch' }}
                        </p>
                        <p class="text-xs text-slate-500">
                            @if ($s->slot)
                                {{ $s->slot->label ?? ('Slot '.$s->slot->slot_number) }}
                                @if ($s->slot->start_time) · {{ \Illuminate\Support\Str::of($s->slot->start_time)->substr(0, 5) }}@if ($s->slot->end_time)–{{ \Illuminate\Support\Str::of($s->slot->end_time)->substr(0, 5) }}@endif @endif
                            @endif
                            @if ($s->room) · 🏠 {{ $s->room->room_name }} @endif
                        </p>
                    </div>
                </div>
                @if ($s->my_attendance)
                    <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $attStyles[$s->my_attendance->status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">{{ $s->my_attendance->statusLabel() }}</span>
                @elseif ($isPast)
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-400">Chưa điểm danh</span>
                @else
                    <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-500">Sắp diễn ra</span>
                @endif
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-slate-400">Lớp chưa có buổi học nào được xếp lịch.</p>
        @endforelse
    </div>

    {{-- ===== Tab: Bài tập ===== --}}
    <div data-panel="assignments" class="tab-panel space-y-3">
        @forelse ($assignments as $a)
            @php
                $sub = $a->my_submission;
                $overdue = $a->due_date && $a->due_date->isPast();
            @endphp
            <a href="{{ route('student.assignments.show', $a->assignment_id) }}"
               class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm transition-all hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">{{ $a->typeLabel() }}</span>
                        <h3 class="truncate text-sm font-bold text-slate-800">{{ $a->title }}</h3>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        @if ($a->due_date)
                            Hạn nộp: {{ $a->due_date->format('H:i d/m/Y') }}
                        @else
                            Không có hạn nộp
                        @endif
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    @if ($sub && $sub->isGraded())
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ rtrim(rtrim(number_format($sub->score, 2), '0'), '.') }}/{{ $a->max_score }} điểm</span>
                    @elseif ($sub)
                        <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700">Đã nộp</span>
                    @elseif ($overdue)
                        <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Quá hạn</span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Chưa nộp</span>
                    @endif
                </div>
            </a>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-400">Lớp chưa có bài tập hoặc quiz nào.</p>
        @endforelse
    </div>

    {{-- ===== Tab: Học liệu ===== --}}
    <div data-panel="materials" class="tab-panel grid grid-cols-1 gap-3 sm:grid-cols-2">
        @forelse ($materials as $m)
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-lg">{{ $m->isVideo() ? '🎬' : '📄' }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700" title="{{ $m->title }}">{{ $m->title }}</p>
                        <p class="truncate text-xs text-slate-400">{{ strtoupper($m->extension() ?: 'FILE') }}@if ($m->uploader) · {{ $m->uploader->full_name }}@endif</p>
                    </div>
                </div>
                <a href="{{ route('student.materials.download', $m->material_id) }}" class="shrink-0 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-indigo-700">Tải về</a>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-400 sm:col-span-2">Môn học này chưa có học liệu.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const root = document.getElementById('classTabs');
        const btns = Array.from(root.querySelectorAll('.tab-btn'));
        const panels = Array.from(root.querySelectorAll('.tab-panel'));
        const activeCls = ['bg-indigo-600', 'text-white', 'shadow-sm'];

        function show(name) {
            btns.forEach(b => {
                const on = b.dataset.tab === name;
                b.classList.toggle('text-slate-600', !on);
                on ? b.classList.add(...activeCls) : b.classList.remove(...activeCls);
            });
            panels.forEach(p => p.classList.toggle('hidden', p.dataset.panel !== name));
        }

        btns.forEach(b => b.addEventListener('click', () => show(b.dataset.tab)));
        show('sessions');
    })();
</script>
@endpush

@endsection
