@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
<div class="w-full space-y-3">
    @forelse ($notifications as $n)
        <div class="rounded-2xl border bg-white p-5 shadow-sm {{ $n->isSystemWide() ? 'border-indigo-200' : 'border-emerald-200' }}">
            <div class="flex items-start gap-3">
                <span class="text-xl">{{ $n->isSystemWide() ? '📣' : '🏫' }}</span>
                <div>
                    <h3 class="font-bold text-slate-800">{{ $n->title }}
                        @if ($n->isSystemWide())
                            <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] font-medium text-indigo-600">Toàn trường</span>
                        @else
                            <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[11px] font-medium text-emerald-600">Lớp {{ $n->classRoom?->class_name }}</span>
                        @endif
                    </h3>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $n->body }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->format('H:i d/m/Y') }} ({{ $n->created_at?->diffForHumans() }})</p>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">Không có thông báo nào.</div>
    @endforelse

    {{ $notifications->links() }}
</div>
@endsection
