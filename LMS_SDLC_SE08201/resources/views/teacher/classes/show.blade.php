@extends('layouts.app')

@section('title', 'Lớp '.$class->class_name)

@section('content')
{{-- Header lớp --}}
<div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">{{ $class->class_name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $class->subject?->subject_code }} — {{ $class->subject?->subject_name }} · {{ $class->students->count() }} sinh viên · Đã học {{ $doneSessions ?? $doneSessionIds->count() }}/{{ $class->total_sessions }} buổi</p>
        </div>
        <a href="{{ route('teacher.assignments.create', $class) }}" class="rounded-lg bg-[#1e4f8f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#163c6d]">+ Giao bài tập</a>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-5">
    {{-- Buổi học & điểm danh --}}
    <div class="xl:col-span-2 rounded-lg border border-slate-200 bg-white shadow-sm h-fit">
        <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
            <h3 class="font-bold text-slate-800">Buổi học & Điểm danh</h3>
            <span class="text-xs text-slate-500"><span class="inline-block w-2.5 h-2.5 rounded-full bg-[#1e4f8f] mr-1"></span>Xanh đậm: Đã học & điểm danh</span>
        </div>
        <ul class="max-h-[480px] divide-y divide-slate-100 overflow-y-auto">
            @foreach ($sessions ?? $class->sessions as $i => $session)
                @php
                    $isToday = $session->isToday();
                    $isDone = $session->isLearnedAndMarked();
                    $marked = $session->attendances->isNotEmpty();
                    $slotDetail = $session->getSlotTimeDisplay();
                @endphp
                <li class="flex items-center justify-between gap-3 px-5 py-3 transition
                    @if ($isToday)
                        bg-emerald-50/50 border-l-4 border-l-emerald-600
                    @elseif ($isDone)
                        bg-blue-50/40 border-l-4 border-l-[#1e4f8f]
                    @else
                        hover:bg-slate-50
                    @endif">
                    <div>
                        <p class="text-sm font-bold
                            @if ($isToday)
                                text-emerald-800
                            @elseif ($isDone)
                                text-[#1e4f8f]
                            @else
                                text-slate-700
                            @endif">
                            Buổi {{ $i + 1 }} — {{ $session->session_date->format('d/m/Y') }}
                            @if ($isToday)
                                <span class="ml-1 text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">Hôm nay</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $session->room?->room_name ?? 'Phòng Lab' }} · <span class="font-semibold
                            @if ($isToday)
                                text-emerald-700
                            @elseif ($isDone)
                                text-[#1e4f8f]
                            @else
                                text-slate-600
                            @endif">{{ $slotDetail }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($isToday)
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 shadow-sm">📍 Hôm nay</span>
                        @elseif ($isDone)
                            <span class="rounded-full bg-[#1e4f8f] px-2.5 py-0.5 text-[11px] font-bold text-white shadow-sm">✓ Đã điểm danh</span>
                        @elseif ($session->session_date->isFuture())
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Chưa diễn ra</span>
                        @endif
                        <a href="{{ route('teacher.attendance.edit', [$class, $session]) }}"
                           class="rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm transition
                           @if ($isToday)
                               bg-emerald-600 text-white hover:bg-emerald-700
                           @elseif ($isDone)
                               bg-[#1e4f8f] text-white hover:bg-[#163c6d]
                           @else
                               bg-slate-100 text-slate-700 hover:bg-slate-200
                           @endif">
                            {{ $isToday ? 'Điểm danh hôm nay' : ($isDone ? 'Xem / Sửa' : 'Chi tiết') }}
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Danh sách sinh viên + chuyên cần --}}
    <div class="xl:col-span-3 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">Danh sách sinh viên</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Sinh viên</th>
                        <th class="px-3 py-3 text-center">Có mặt</th>
                        <th class="px-3 py-3 text-center">Muộn</th>
                        <th class="px-3 py-3 text-center">Vắng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($class->students as $sv)
                        @php($sum = $attendanceSummary[$sv->user_id] ?? ['present' => 0, 'late' => 0, 'absent' => 0])
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-700">{{ $sv->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $sv->email }}</p>
                            </td>
                            <td class="px-3 py-3 text-center"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $sum['present'] }}</span></td>
                            <td class="px-3 py-3 text-center"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">{{ $sum['late'] }}</span></td>
                            <td class="px-3 py-3 text-center"><span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">{{ $sum['absent'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">Lớp chưa có sinh viên.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Bài tập của lớp --}}
<div class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-bold text-slate-800">Bài tập của lớp ({{ $class->assignments->count() }})</h3>
        <a href="{{ route('teacher.assignments.create', $class) }}" class="text-sm font-medium text-[#1e4f8f] hover:underline">+ Giao bài mới</a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($class->assignments->sortByDesc('assignment_id') as $a)
            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                <div>
                    <a href="{{ route('teacher.assignments.show', $a) }}" class="text-sm font-semibold text-[#1e4f8f] hover:underline">{{ $a->title }}</a>
                    <p class="text-xs text-slate-500">{{ $a->typeLabel() }} · Hạn: {{ $a->due_date->format('H:i d/m/Y') }} · Thang điểm {{ $a->max_score }}</p>
                </div>
                <a href="{{ route('teacher.grading.index', $a) }}" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-[#1e4f8f] hover:bg-blue-100">Bài nộp / Chấm điểm</a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-slate-500">Chưa giao bài tập nào cho lớp.</li>
        @endforelse
    </ul>
</div>
@include('components.toast')
@endsection
