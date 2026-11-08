@extends('layouts.app')

@section('title', 'Lớp '.$class->class_name)

@section('content')
{{-- Header lớp --}}
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-800">{{ $class->class_name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $class->subject?->subject_code }} — {{ $class->subject?->subject_name }} · {{ $class->students->count() }} sinh viên · Đã học {{ $doneSessions ?? $doneSessionIds->count() }}/{{ $class->total_sessions }} buổi</p>
        </div>
        <a href="{{ route('teacher.assignments.create', $class) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Giao bài tập</a>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-5">
    {{-- Buổi học & điểm danh --}}
    <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm h-fit">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">🗓️ Buổi học & Điểm danh</h3></div>
        <ul class="max-h-[480px] divide-y divide-slate-100 overflow-y-auto">
            @foreach ($sessions ?? $class->sessions as $i => $session)
                @php($marked = $session->attendances->isNotEmpty())
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Buổi {{ $i + 1 }} — {{ $session->session_date->format('d/m/Y') }}</p>
                        <p class="text-xs text-slate-400">{{ $session->room?->room_name ?? 'Chưa xếp phòng' }} · {{ $session->slot?->label ?? 'Chưa xếp ca' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($marked)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Đã điểm danh</span>
                        @elseif ($session->session_date->isFuture())
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-400">Chưa diễn ra</span>
                        @endif
                        <a href="{{ route('teacher.attendance.edit', [$class, $session]) }}"
                           class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $marked ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                            {{ $marked ? 'Sửa' : 'Điểm danh' }}
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Danh sách sinh viên + chuyên cần --}}
    <div class="xl:col-span-3 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-800">👥 Danh sách sinh viên</h3></div>
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
                                <p class="text-xs text-slate-400">{{ $sv->email }}</p>
                            </td>
                            <td class="px-3 py-3 text-center"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $sum['present'] }}</span></td>
                            <td class="px-3 py-3 text-center"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">{{ $sum['late'] }}</span></td>
                            <td class="px-3 py-3 text-center"><span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">{{ $sum['absent'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">Lớp chưa có sinh viên.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Bài tập của lớp --}}
<div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-bold text-slate-800">📝 Bài tập của lớp ({{ $class->assignments->count() }})</h3>
        <a href="{{ route('teacher.assignments.create', $class) }}" class="text-sm font-medium text-indigo-600 hover:underline">+ Giao bài mới</a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($class->assignments->sortByDesc('assignment_id') as $a)
            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                <div>
                    <a href="{{ route('teacher.assignments.show', $a) }}" class="text-sm font-semibold text-indigo-600 hover:underline">{{ $a->title }}</a>
                    <p class="text-xs text-slate-400">{{ $a->typeLabel() }} · Hạn: {{ $a->due_date->format('H:i d/m/Y') }} · Thang điểm {{ $a->max_score }}</p>
                </div>
                <a href="{{ route('teacher.grading.index', $a) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Bài nộp / Chấm điểm</a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-slate-400">Chưa giao bài tập nào cho lớp.</li>
        @endforelse
    </ul>
</div>
@include('components.toast')
@endsection
