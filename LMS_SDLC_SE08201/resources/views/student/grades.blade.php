@extends('layouts.app')

@section('title', 'Bảng điểm')

@section('content')
<div class="mb-6 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white shadow">
    <p class="text-sm text-indigo-100">Điểm trung bình tất cả bài tập</p>
    <p class="mt-1 text-4xl font-extrabold">{{ $overallAvg ?: '—' }}</p>
</div>

<div class="space-y-6">
    @forelse ($byClass as $item)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-bold text-slate-800">{{ $item['class']->class_name }}</h2>
                    <p class="text-xs text-slate-400">{{ $item['class']->subject?->subject_name }}</p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-bold text-indigo-700">TB: {{ $item['avg'] ?: '—' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5">Bài tập</th>
                            <th class="px-5 py-2.5">Loại</th>
                            <th class="px-5 py-2.5">Nộp lúc</th>
                            <th class="px-5 py-2.5 text-center">Điểm</th>
                            <th class="px-5 py-2.5">Nhận xét</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item['submissions'] as $sub)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('student.assignments.show', $sub->assignment) }}" class="font-semibold text-indigo-600 hover:underline">{{ $sub->assignment?->title }}</a>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500">{{ $sub->assignment?->typeLabel() }}</td>
                                <td class="px-5 py-3 text-xs text-slate-500">{{ $sub->submitted_at?->format('H:i d/m/Y') }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if ($sub->score !== null)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">{{ $sub->score }}/{{ $sub->assignment?->max_score }}</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">Chờ chấm</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500">{{ $sub->feedback ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">Bạn chưa nộp bài tập nào.</div>
    @endforelse
</div>
@endsection
