@extends('layouts.app')

@section('title', 'Bài tập & Chấm bài')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500">Toàn bộ bài tập các lớp bạn phụ trách.</p>
    <div class="relative inline-block text-left" id="assignDropdownContainer">
        <button onclick="document.getElementById('assignDropdownMenu').classList.toggle('hidden')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-500 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-violet-600 focus:outline-none transition-all">
            ✨ Giao bài tập mới
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="assignDropdownMenu" class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-slate-100 bg-white shadow-xl focus:outline-none hidden z-10 overflow-hidden">
            <div class="flex flex-col divide-y divide-slate-100">
                @foreach ($classes as $c)
                    <a href="{{ route('teacher.assignments.create', $c) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-violet-600 transition-colors">
                        🏫 Lớp {{ $c->class_name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Bài tập</th>
                <th class="px-5 py-3">Lớp</th>
                <th class="px-5 py-3">Loại</th>
                <th class="px-5 py-3">Hạn nộp</th>
                <th class="px-5 py-3 text-center">Đã nộp</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($assignments as $a)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('teacher.assignments.show', $a) }}" class="font-semibold text-indigo-600 hover:underline">{{ $a->title }}</a>
                        @if ($a->isQuiz())<p class="text-xs text-slate-400">{{ $a->questions_count }} câu hỏi</p>@endif
                    </td>
                    <td class="px-5 py-3 text-slate-600">{{ $a->classRoom?->class_name }}</td>
                    <td class="px-5 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $a->isQuiz() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $a->typeLabel() }}</span>
                    </td>
                    <td class="px-5 py-3 text-xs {{ $a->isOverdue() ? 'text-rose-500 font-medium' : 'text-slate-500' }}">
                        {{ $a->due_date->format('H:i d/m/Y') }} {{ $a->isOverdue() ? '(đã hết hạn)' : '' }}
                    </td>
                    <td class="px-5 py-3 text-center"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $a->submissions_count }}</span></td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('teacher.grading.index', $a) }}" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Chấm bài</a>
                            <form method="POST" action="{{ route('teacher.assignments.destroy', $a) }}" onsubmit="return confirm('Xóa bài tập này? Toàn bộ bài nộp sẽ bị xóa theo.')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Chưa có bài tập nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $assignments->links() }}</div>

@push('scripts')
<script>
    // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', function(event) {
        var container = document.getElementById('assignDropdownContainer');
        var menu = document.getElementById('assignDropdownMenu');
        if (container && menu && !container.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
@endpush
@include('components.toast')
@endsection
