@extends('layouts.app')

@section('title', 'Bài tập & Chấm bài')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500">Toàn bộ bài tập các lớp bạn phụ trách.</p>
    <div class="relative inline-block text-left" id="dropdown-giao-bai-container">
        <div>
            <button type="button" onclick="toggleGiaoBaiDropdown()" class="inline-flex w-full justify-center items-center gap-x-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:shadow-lg transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 focus:outline-none" id="menu-button" aria-expanded="true" aria-haspopup="true">
                ✨ Giao bài tập mới
                <svg class="-mr-0.5 h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div id="dropdown-giao-bai-menu" class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
            <div class="py-1" role="none">
                @foreach ($classes as $c)
                    <a href="{{ route('teacher.assignments.create', $c) }}" class="block px-4 py-2.5 text-xs text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 font-semibold border-b border-slate-50 last:border-0" role="menuitem" tabindex="-1">🏫 Lớp {{ $c->class_name }}</a>
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

<script>
    function toggleGiaoBaiDropdown() {
        const menu = document.getElementById('dropdown-giao-bai-menu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    // Close dropdown when clicking outside
    window.addEventListener('click', function(e) {
        const container = document.getElementById('dropdown-giao-bai-container');
        const menu = document.getElementById('dropdown-giao-bai-menu');
        if (container && menu && !container.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
@endsection
