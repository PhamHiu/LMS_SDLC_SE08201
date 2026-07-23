@extends('layouts.app')

@section('title', 'Lớp của tôi')

@section('content')

@php
    // Cấu hình màu theo trạng thái lớp
    $statusStyles = [
        \App\Models\ClassRoom::STATUS_PENDING => ['label' => 'Sắp mở', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'bar' => 'bg-amber-400'],
        \App\Models\ClassRoom::STATUS_ACTIVE  => ['label' => 'Đang học', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'bar' => 'bg-emerald-500'],
        \App\Models\ClassRoom::STATUS_CLOSED  => ['label' => 'Đã kết thúc', 'badge' => 'bg-slate-100 text-slate-600 border-slate-200', 'bar' => 'bg-slate-400'],
    ];
@endphp

<div class="mb-6">
    <h2 class="text-xl font-extrabold text-slate-800">🏫 Lớp của tôi</h2>
    <p class="mt-1 text-sm text-slate-500">Các lớp học bạn đang tham gia, giảng viên phụ trách và tiến độ học tập.</p>
</div>

@if ($classes->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <span class="text-5xl">📭</span>
        <h3 class="mt-4 text-lg font-bold text-slate-700">Bạn chưa được xếp vào lớp nào</h3>
        <p class="mt-1 max-w-md text-sm text-slate-500">Khi được ghi danh vào một lớp học, lớp đó sẽ hiển thị tại đây. Vui lòng liên hệ giáo vụ nếu bạn cho rằng đây là nhầm lẫn.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($classes as $item)
            @php
                $class = $item['class'];
                $style = $statusStyles[$class->status] ?? $statusStyles[\App\Models\ClassRoom::STATUS_PENDING];
                $teacher = $class->teacher;
            @endphp
            <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">
                {{-- Header: tên lớp + môn học + trạng thái --}}
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-bold text-slate-800" title="{{ $class->class_name }}">{{ $class->class_name }}</h3>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-500">
                                @if ($class->subject)
                                    {{ $class->subject->subject_code }} — {{ $class->subject->subject_name }}
                                @else
                                    <span class="italic text-slate-400">Chưa gán môn học</span>
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-bold {{ $style['badge'] }}">
                            {{ $style['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex flex-1 flex-col gap-4 px-5 py-4">
                    {{-- Giảng viên --}}
                    <div class="flex items-center gap-3">
                        @if ($teacher && $teacher->avatar)
                            <img src="{{ asset('storage/'.$teacher->avatar) }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-indigo-100" alt="Giảng viên">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500 text-sm font-bold text-white">
                                {{ $teacher ? mb_substr($teacher->full_name, 0, 1) : '?' }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Giảng viên</p>
                            <p class="truncate text-sm font-semibold text-slate-700">{{ $teacher->full_name ?? 'Chưa phân công' }}</p>
                        </div>
                    </div>

                    {{-- Tiến độ --}}
                    <div class="mt-auto">
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-500">Tiến độ</span>
                            <span class="font-bold text-slate-700">{{ $item['held'] }}/{{ $item['total'] ?: '—' }} buổi</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $style['bar'] }} transition-all" style="width: {{ $item['percent'] }}%"></div>
                        </div>
                        <p class="mt-1 text-right text-[11px] font-semibold text-slate-400">{{ $item['percent'] }}%</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
