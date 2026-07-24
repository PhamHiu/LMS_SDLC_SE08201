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

    // Thứ trong tuần (Carbon dayOfWeek: 0 = CN ... 6 = Thứ 7)
    $weekdays = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
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
    {{-- ===== Thanh KPI tổng quan ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Lớp đang học</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ $summary['active'] }}<span class="text-base font-semibold text-slate-400">/{{ $summary['total'] }}</span></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tiến độ trung bình</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ $summary['avg_percent'] }}%</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Buổi đã học</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ $summary['held'] }}<span class="text-base font-semibold text-slate-400">/{{ $summary['sessions_total'] ?: '—' }}</span></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sắp đến hạn</p>
            <p class="mt-1 text-2xl font-extrabold {{ $summary['deadlines'] > 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ $summary['deadlines'] }}</p>
        </div>
    </div>

    {{-- ===== Bộ lọc + tìm kiếm ===== --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="tablist">
            <button type="button" data-filter="all" class="filter-btn rounded-lg px-3.5 py-1.5 text-sm font-semibold text-slate-600 transition-colors">Tất cả</button>
            <button type="button" data-filter="{{ \App\Models\ClassRoom::STATUS_ACTIVE }}" class="filter-btn rounded-lg px-3.5 py-1.5 text-sm font-semibold text-slate-600 transition-colors">Đang học</button>
            <button type="button" data-filter="{{ \App\Models\ClassRoom::STATUS_CLOSED }}" class="filter-btn rounded-lg px-3.5 py-1.5 text-sm font-semibold text-slate-600 transition-colors">Đã kết thúc</button>
        </div>
        <div class="relative sm:w-72">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
            <input id="classSearch" type="text" placeholder="Tìm theo tên lớp, môn, giảng viên…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
        </div>
    </div>

    <div id="classGrid" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($classes as $item)
            @php
                $class = $item['class'];
                $style = $statusStyles[$class->status] ?? $statusStyles[\App\Models\ClassRoom::STATUS_PENDING];
                $teacher = $class->teacher;
                $next = $item['next_session'];
                $deadline = $item['next_deadline'];
                $searchText = mb_strtolower(trim($class->class_name.' '.optional($class->subject)->subject_code.' '.optional($class->subject)->subject_name.' '.optional($teacher)->full_name));
            @endphp
            <a href="{{ route('student.classes.show', $class->class_id) }}"
               class="class-card flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"
               data-status="{{ $class->status }}" data-search="{{ $searchText }}">
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

                    {{-- Buổi học tiếp theo --}}
                    <div class="flex items-start gap-2.5 rounded-xl bg-indigo-50/60 px-3 py-2.5">
                        <span class="text-base leading-5">📅</span>
                        <div class="min-w-0 text-xs">
                            <p class="font-semibold uppercase tracking-wide text-indigo-400">Buổi học tiếp theo</p>
                            @if ($next)
                                <p class="mt-0.5 font-semibold text-slate-700">
                                    {{ $weekdays[$next->session_date->dayOfWeek] }}, {{ $next->session_date->format('d/m/Y') }}
                                    @if ($next->slot && $next->slot->start_time)
                                        · {{ \Illuminate\Support\Str::of($next->slot->start_time)->substr(0, 5) }}
                                    @endif
                                    @if ($next->room)
                                        · {{ $next->room->room_name }}
                                    @endif
                                </p>
                            @else
                                <p class="mt-0.5 font-medium italic text-slate-400">Chưa có lịch sắp tới</p>
                            @endif
                        </div>
                    </div>

                    {{-- Bài tập sắp đến hạn --}}
                    @if ($deadline)
                        @php $overdueSoon = $deadline->due_date->diffInHours(now()) <= 48; @endphp
                        <div class="flex items-start gap-2.5 rounded-xl px-3 py-2.5 {{ $overdueSoon ? 'bg-rose-50/70' : 'bg-slate-50' }}">
                            <span class="text-base leading-5">⏰</span>
                            <div class="min-w-0 text-xs">
                                <p class="font-semibold uppercase tracking-wide {{ $overdueSoon ? 'text-rose-400' : 'text-slate-400' }}">Sắp đến hạn</p>
                                <p class="mt-0.5 truncate font-semibold text-slate-700" title="{{ $deadline->title }}">{{ $deadline->title }}</p>
                                <p class="{{ $overdueSoon ? 'text-rose-600' : 'text-slate-500' }} font-medium">
                                    Hạn {{ $deadline->due_date->format('d/m H:i') }} · còn {{ $deadline->due_date->diffForHumans(now(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                    @if ($item['pending_count'] > 1)
                                        <span class="text-slate-400">(+{{ $item['pending_count'] - 1 }} bài khác)</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

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

                    {{-- Xem chi tiết lớp --}}
                    <div class="flex items-center justify-end pt-1 text-sm font-semibold text-indigo-600">
                        Xem chi tiết lớp
                        <span class="ml-1 transition-transform group-hover:translate-x-0.5">→</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Trạng thái rỗng khi lọc/tìm không ra kết quả --}}
    <div id="emptyResult" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
        <span class="text-4xl">🔍</span>
        <p class="mt-3 text-sm font-semibold text-slate-500">Không tìm thấy lớp nào phù hợp.</p>
    </div>

    @push('scripts')
    <script>
        (function () {
            const grid    = document.getElementById('classGrid');
            const cards   = Array.from(grid.querySelectorAll('.class-card'));
            const search  = document.getElementById('classSearch');
            const buttons = Array.from(document.querySelectorAll('.filter-btn'));
            const empty   = document.getElementById('emptyResult');

            const activeCls   = ['bg-indigo-600', 'text-white', 'shadow-sm'];
            const inactiveCls = ['text-slate-600'];
            let currentFilter = 'all';

            function setActive(btn) {
                buttons.forEach(b => {
                    b.classList.remove(...activeCls);
                    b.classList.add(...inactiveCls);
                });
                btn.classList.remove(...inactiveCls);
                btn.classList.add(...activeCls);
            }

            function apply() {
                const q = search.value.trim().toLowerCase();
                let visible = 0;
                cards.forEach(card => {
                    const okStatus = currentFilter === 'all' || card.dataset.status === currentFilter;
                    const okSearch = !q || card.dataset.search.includes(q);
                    const show = okStatus && okSearch;
                    card.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                empty.classList.toggle('hidden', visible !== 0);
            }

            buttons.forEach(btn => btn.addEventListener('click', () => {
                currentFilter = btn.dataset.filter;
                setActive(btn);
                apply();
            }));
            search.addEventListener('input', apply);

            setActive(buttons[0]);
        })();
    </script>
    @endpush
@endif

@endsection
