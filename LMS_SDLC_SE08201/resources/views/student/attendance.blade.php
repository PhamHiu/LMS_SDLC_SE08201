@extends('layouts.app')

@section('title', 'Lịch sử điểm danh')

@section('content')
<div class="space-y-6">
    @forelse ($summary as $item)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-bold text-slate-800">{{ $item['class']->class_name }}</h2>
                    <p class="text-xs text-slate-400">{{ $item['class']->subject?->subject_name }}</p>
                </div>
                <div class="flex gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">✅ Có mặt: {{ $item['present'] }}</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-amber-700">🕐 Muộn: {{ $item['late'] }}</span>
                    <span class="rounded-full bg-rose-50 px-3 py-1.5 text-rose-700">❌ Vắng: {{ $item['absent'] }}</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5">Buổi học</th>
                            <th class="px-5 py-2.5">Trạng thái</th>
                            <th class="px-5 py-2.5">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item['records'] as $att)
                            <tr>
                                <td class="px-5 py-2.5 text-slate-600">{{ $att->session?->session_date?->format('d/m/Y') }} · {{ $att->session?->shift }}</td>
                                <td class="px-5 py-2.5">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $att->status === 'P' ? 'bg-emerald-100 text-emerald-700' : ($att->status === 'L' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">{{ $att->statusLabel() }}</span>
                                </td>
                                <td class="px-5 py-2.5 text-xs text-slate-400">{{ $att->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">Chưa có dữ liệu điểm danh.</div>
    @endforelse
</div>
@endsection
