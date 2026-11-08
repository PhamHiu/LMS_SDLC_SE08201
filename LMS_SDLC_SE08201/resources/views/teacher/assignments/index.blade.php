@extends('layouts.app')

@section('title', 'Bài tập & Chấm bài')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-400">Toàn bộ bài tập các lớp bạn phụ trách giảng dạy.</p>
    <div class="relative inline-block text-left" id="assignDropdownContainer">
        <button onclick="document.getElementById('assignDropdownMenu').classList.toggle('hidden')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#D4FF24] hover:bg-[#c2ee12] px-5 py-2.5 text-sm font-black text-[#030407] shadow-lg shadow-[#D4FF24]/20 focus:outline-none transition-all transform hover:scale-105 active:scale-95 cursor-pointer">
            <svg class="h-4 w-4 text-[#030407]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>GIAO BÀI TẬP MỚI</span>
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="assignDropdownMenu" class="absolute right-0 mt-2 w-60 origin-top-right rounded-2xl border border-white/15 bg-[#030407]/95 backdrop-blur-xl shadow-2xl focus:outline-none hidden z-20 overflow-hidden ring-1 ring-white/10">
            <div class="flex flex-col divide-y divide-white/10">
                @foreach ($classes as $c)
                    <a href="{{ route('teacher.assignments.create', $c) }}" class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-[#1558C9]/30 hover:text-[#D4FF24] transition-colors">
                        <svg class="w-4 h-4 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Lớp {{ $c->class_name }}</span>
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
                            <a href="{{ route('teacher.grading.index', $a) }}" class="tool-btn !bg-[#1558C9]/30 !text-[#D4FF24] !border-[#D4FF24]/40 hover:!bg-[#1558C9] hover:!text-white">
                                <svg class="w-3.5 h-3.5 text-[#D4FF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                <span>Chấm bài</span>
                            </a>
                            <form method="POST" action="{{ route('teacher.assignments.destroy', $a) }}" onsubmit="return confirm('Xóa bài tập này? Toàn bộ bài nộp sẽ bị xóa theo.')">
                                @csrf @method('DELETE')
                                <button class="tool-btn !text-rose-400 !border-rose-500/30 hover:!bg-rose-500/20">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Xóa</span>
                                </button>
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
