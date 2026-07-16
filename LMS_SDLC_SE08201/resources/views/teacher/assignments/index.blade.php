@extends('layouts.app')

@section('title', 'Bài tập & Chấm bài')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500">Toàn bộ bài tập các lớp bạn phụ trách.</p>
    <div class="flex flex-wrap gap-2">
        @foreach ($classes as $c)
            <a href="{{ route('teacher.assignments.create', $c) }}" class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Giao bài cho {{ $c->class_name }}</a>
        @endforeach
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
@endsection
