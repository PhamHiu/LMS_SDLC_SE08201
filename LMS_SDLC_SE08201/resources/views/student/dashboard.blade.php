@extends('layouts.app')

@section('title', 'Dashboard sinh viên')

@section('content')
{{-- ===== KPI CARDS (4 cards) ===== --}}
<div class="grid gap-4 grid-cols-2 xl:grid-cols-4">
    {{-- Card 1: Lớp đang học --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-indigo-100">Lớp đang học</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">🏫</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $enrolledCount }}</p>
    </div>
    {{-- Card 2: Điểm trung bình --}}
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-emerald-100">Điểm trung bình</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">⭐</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $avgScore ?: '—' }}</p>
    </div>
    {{-- Card 3: Chuyên cần --}}
    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-amber-100">Chuyên cần</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">✅</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</p>
    </div>
    {{-- Card 4: Bài tập chưa nộp --}}
    <div class="rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-sm text-rose-100">Bài tập chưa nộp</p>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg">📝</span>
        </div>
        <p class="mt-2 text-3xl font-extrabold">{{ $pending_count }}</p>
        <a href="{{ route('student.assignments.index') }}" class="mt-1 inline-block text-xs text-rose-100 underline hover:text-white transition">Xem ngay →</a>
    </div>
</div>

{{-- ===== ROW 2: 2 Biểu đồ ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Line Chart: Trend điểm số --}}
    <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <h2 class="font-bold text-slate-800 mb-4">📈 Lịch sử điểm số</h2>
        <div class="relative w-full" style="height:260px">
            <canvas id="scoreTrendChart"></canvas>
        </div>
    </div>
    {{-- Doughnut: Chuyên cần cá nhân --}}
    <div class="w-full rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <h2 class="font-bold text-slate-800 mb-4">🥧 Chuyên cần cá nhân</h2>
        <div class="flex flex-col sm:flex-row items-center gap-6">
            {{-- Container vuông: giữ tỷ lệ tròn --}}
            <div class="w-full max-w-[200px] mx-auto sm:mx-0 sm:w-48 shrink-0 aspect-square relative">
                <canvas id="attendanceChart"></canvas>
            </div>
            <div class="flex-1 w-full space-y-3">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 text-white text-sm font-bold">P</span>
                    <div>
                        <p class="text-xs text-slate-500">Có mặt</p>
                        <p class="text-xl font-extrabold text-emerald-600">{{ $attendance_breakdown['present'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-rose-50">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500 text-white text-sm font-bold">A</span>
                    <div>
                        <p class="text-xs text-slate-500">Vắng mặt</p>
                        <p class="text-xl font-extrabold text-rose-600">{{ $attendance_breakdown['absent'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 text-white text-sm font-bold">L</span>
                    <div>
                        <p class="text-xs text-slate-500">Đi muộn</p>
                        <p class="text-xl font-extrabold text-amber-600">{{ $attendance_breakdown['late'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 3: Bài tập sắp hạn + Lịch học ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Bài tập sắp đến hạn --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">⏰ Bài tập sắp đến hạn</h2>
            <a href="{{ route('student.assignments.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Tất cả →</a>
        </div>
        <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
            @forelse ($upcomingAssignments as $a)
                <li class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 transition">
                    <div class="min-w-0">
                        <a href="{{ route('student.assignments.show', $a) }}" class="block truncate text-sm font-semibold text-indigo-600 hover:underline">{{ $a->title }}</a>
                        <p class="text-xs text-slate-400">{{ $a->classRoom?->class_name }} · {{ $a->typeLabel() }}</p>
                    </div>
                    @php($hoursLeft = now()->diffInHours($a->due_date, false))
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $hoursLeft < 24 ? 'bg-rose-100 text-rose-700 animate-pulse' : ($hoursLeft < 48 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                        {{ $a->due_date->diffForHumans() }}
                    </span>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">🎉 Không có bài tập nào chờ nộp.</li>
            @endforelse
        </ul>
    </div>

    {{-- Lịch học tuần này --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">🗓️ Lịch học tuần này</h2>
            <a href="{{ route('student.schedule.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">TKB →</a>
        </div>
        <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
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
                                {{ $s->slot->label }}
                            @endif
                            @if ($s->room) · {{ $s->room->room_name }} @endif
                        </p>
                    </div>
                    @if ($s->session_date->isToday())
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hôm nay</span>
                    @endif
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Tuần này không có lịch học.</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- ===== ROW 4: Tiến trình bài tập + Kết quả & Thông báo ===== --}}
<div class="mt-6 w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Tiến trình hoàn thành bài tập --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-800">📊 Tiến trình hoàn thành bài tập</h2>
        </div>
        <ul class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
            @forelse ($assignment_progress as $ap)
                <li class="px-5 py-3 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-semibold text-slate-700">{{ $ap['class_name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $ap['subject_name'] }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ $ap['done'] }}/{{ $ap['total'] }} bài</span>
                    </div>
                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full transition-all duration-500 {{ $ap['percent'] >= 80 ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : ($ap['percent'] >= 50 ? 'bg-gradient-to-r from-amber-400 to-amber-600' : 'bg-gradient-to-r from-rose-400 to-rose-600') }}" style="width: {{ $ap['percent'] }}%"></div>
                    </div>
                    <p class="mt-1 text-right text-xs font-semibold {{ $ap['percent'] >= 80 ? 'text-emerald-500' : ($ap['percent'] >= 50 ? 'text-amber-500' : 'text-rose-500') }}">{{ $ap['percent'] }}%</p>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Chưa có dữ liệu tiến trình.</li>
            @endforelse
        </ul>
    </div>

    {{-- Kết quả + Thông báo --}}
    <div class="space-y-6">
        {{-- Kết quả gần đây --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-800">🏆 Kết quả gần đây</h2>
                <a href="{{ route('student.grades.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Bảng điểm →</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentGrades as $g)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 transition">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-700">{{ $g->assignment?->title }}</p>
                            <p class="text-xs text-slate-400">{{ $g->assignment?->classRoom?->class_name }}</p>
                        </div>
                        @php($scorePercent = $g->assignment?->max_score > 0 ? $g->score / $g->assignment->max_score * 100 : 0)
                        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $scorePercent >= 80 ? 'bg-emerald-100 text-emerald-700' : ($scorePercent >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">{{ $g->score }}/{{ $g->assignment?->max_score }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-400">Chưa có kết quả nào.</li>
                @endforelse
            </ul>
        </div>

        {{-- Thông báo --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-800">🔔 Thông báo</h2>
                <a href="{{ route('student.notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Tất cả →</a>
            </div>
            <ul class="divide-y divide-slate-100 max-h-52 overflow-y-auto">
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Line Chart: Trend điểm số ===
    const scoreData = @json($score_history);
    new Chart(document.getElementById('scoreTrendChart'), {
        type: 'line',
        data: {
            labels: scoreData.map(d => d.date),
            datasets: [{
                label: 'Điểm',
                data: scoreData.map(d => d.score),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: scoreData.map(d => {
                    const pct = d.max_score > 0 ? d.score / d.max_score * 100 : 0;
                    return pct >= 80 ? '#10b981' : pct >= 50 ? '#f59e0b' : '#ef4444';
                }),
                pointRadius: 6,
                pointHoverRadius: 8,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e1b4b',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        title: (items) => scoreData[items[0].dataIndex]?.title || '',
                        label: (item) => {
                            const d = scoreData[item.dataIndex];
                            return `Điểm: ${d.score}/${d.max_score}`;
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, max: 10, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { maxRotation: 45 } }
            }
        }
    });

    // === Doughnut: Chuyên cần ===
    const att = @json($attendance_breakdown);
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Có mặt', 'Vắng', 'Muộn'],
            datasets: [{
                data: [att.present, att.absent, att.late],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 8 }
            },
            cutout: '65%'
        }
    });
});
</script>
@endpush
