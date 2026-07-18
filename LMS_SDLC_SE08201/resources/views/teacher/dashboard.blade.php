@extends('layouts.app')

@section('title', 'Dashboard giảng viên')

@section('content')
{{-- ===== KPI CARDS (4 cards) ===== --}}
<div class="grid gap-4 grid-cols-2 xl:grid-cols-4">
    {{-- Card 1: Lớp phụ trách --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-indigo-100">Lớp phụ trách</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">🏫</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $classes->count() }}</p>
    </div>
    {{-- Card 2: Tổng sinh viên --}}
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-emerald-100">Tổng sinh viên</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">👥</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $totalStudents }}</p>
    </div>
    {{-- Card 3: Bài chờ chấm --}}
    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-amber-100">Bài chờ chấm</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">📝</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $pendingGrading }}</p>
        <a href="{{ route('teacher.assignments.index') }}" class="mt-1 inline-block text-xs text-amber-100 underline hover:text-white transition">Chấm ngay →</a>
    </div>
    {{-- Card 4: Chuyên cần TB --}}
    <div class="rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-600 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-cyan-100">Chuyên cần TB</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">✅</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $attendance_avg !== null ? $attendance_avg.'%' : '—' }}</p>
    </div>
</div>

{{-- ===== ROW 2: 2 Biểu đồ ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Bar Chart: Phân bố điểm --}}
    <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <h2 class="font-bold text-slate-800 mb-4">📊 Phân bố điểm số</h2>
        <div class="relative w-full" style="height:260px">
            <canvas id="scoreDistChart"></canvas>
        </div>
    </div>
    {{-- Doughnut: Tỷ lệ nộp bài --}}
    <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <h2 class="font-bold text-slate-800 mb-4">🍩 Tỷ lệ nộp bài</h2>
        <div class="flex flex-col sm:flex-row items-center gap-6">
            {{-- Container vuông: biểu đồ tròn luôn giữ được tỷ lệ --}}
            <div class="w-full max-w-[200px] mx-auto sm:mx-0 sm:w-48 shrink-0 aspect-square relative">
                <canvas id="submissionChart"></canvas>
            </div>
            <div class="flex-1 w-full space-y-3">
                <div class="p-3 rounded-xl bg-emerald-50">
                    <p class="text-xs text-slate-500">Tỷ lệ nộp bài</p>
                    <p class="text-2xl font-extrabold text-emerald-600">{{ $submission_rate !== null ? $submission_rate.'%' : '—' }}</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-50">
                    <p class="text-xs text-slate-500">Chờ chấm</p>
                    <p class="text-2xl font-extrabold text-amber-600">{{ $pendingGrading }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 3: Lịch dạy + Tiến độ lớp ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Lịch dạy tuần này --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">🗓️ Lịch dạy tuần này</h2>
        </div>
        <ul class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
            @forelse ($weekSessions as $s)
                <li class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition">
                    <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl {{ $s->session_date->isToday() ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700' }}">
                        <span class="text-xs font-medium">{{ $s->session_date->isoFormat('dd') }}</span>
                        <span class="text-sm font-extrabold">{{ $s->session_date->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $s->classRoom?->class_name }} — {{ $s->classRoom?->subject?->subject_name }}</p>
                        <p class="text-xs text-slate-400">
                            @if ($s->slot)
                                {{ $s->slot->label }} ({{ \Carbon\Carbon::parse($s->slot->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->slot->end_time)->format('H:i') }})
                            @endif
                            @if ($s->room) · {{ $s->room->room_name }} @endif
                        </p>
                    </div>
                    @if ($s->session_date->isToday())
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hôm nay</span>
                    @endif
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Tuần này không có lịch dạy.</li>
            @endforelse
        </ul>
    </div>

    {{-- Tiến độ lớp --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">📈 Tiến độ các lớp</h2>
        </div>
        <ul class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
            @forelse ($classes as $c)
                @php($pct = $c->total_sessions > 0 ? min(100, round($c->done_sessions_count / $c->total_sessions * 100)) : 0)
                <li class="px-5 py-3 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between text-sm">
                        <a href="{{ route('teacher.classes.show', $c) }}" class="font-semibold text-indigo-600 hover:underline">{{ $c->class_name }}</a>
                        <span class="text-xs text-slate-400">{{ $c->done_sessions_count }}/{{ $c->total_sessions }} buổi · {{ $c->enrollments_count }} SV</span>
                    </div>
                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                    <p class="mt-1 text-right text-xs font-semibold {{ $pct >= 80 ? 'text-emerald-500' : ($pct >= 50 ? 'text-amber-500' : 'text-slate-400') }}">{{ $pct }}%</p>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Bạn chưa được phân công lớp nào.</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- ===== ROW 4: Thảo luận + Thông báo ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Thảo luận gần đây --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">💬 Thảo luận gần đây</h2>
        </div>
        <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
            @forelse ($recent_discussions as $d)
                <li class="px-5 py-3 hover:bg-slate-50 transition">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-sm font-bold text-cyan-600">{{ mb_substr($d->author?->full_name ?? '?', 0, 1) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-700 line-clamp-2">{{ $d->comment }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $d->author?->full_name }} · {{ $d->assignment?->title }} · {{ $d->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Chưa có thảo luận mới.</li>
            @endforelse
        </ul>
    </div>

    {{-- Thông báo --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">🔔 Thông báo mới</h2>
            <a href="{{ route('teacher.notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Tất cả →</a>
        </div>
        <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
            @forelse ($notifications as $n)
                <li class="px-5 py-3 hover:bg-slate-50 transition">
                    <p class="text-sm font-semibold text-slate-700">{{ $n->title }}
                        @if ($n->isSystemWide())
                            <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] text-indigo-600">Toàn trường</span>
                        @else
                            <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] text-emerald-600">{{ $n->classRoom?->class_name }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->diffForHumans() }}</p>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-slate-400">Không có thông báo mới.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Bar Chart: Phân bố điểm ===
    const scoreDist = @json($score_distribution);
    new Chart(document.getElementById('scoreDistChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(scoreDist),
            datasets: [{
                label: 'Số lượt',
                data: Object.values(scoreDist),
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e', '#6366f1'],
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });

    // === Doughnut: Tỷ lệ nộp bài ===
    const subRate = {{ $submission_rate ?? 0 }};
    new Chart(document.getElementById('submissionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Đã nộp', 'Chưa nộp'],
            datasets: [{
                data: [subRate, Math.max(0, 100 - subRate)],
                backgroundColor: ['#10b981', '#e2e8f0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } },
                tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }
            },
            cutout: '70%'
        }
    });
});
</script>
@endpush
