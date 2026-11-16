@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
<div class="w-full space-y-4">
    @forelse ($notifications as $n)
        <div class="glass-card-3d p-5 rounded-lg relative overflow-hidden transition-all duration-300 border {{ $n->isSystemWide() ? 'border-[#1e4f8f]/30 hover:border-[#1e4f8f]' : 'border-emerald-200 hover:border-emerald-200' }}">
            {{-- Glowing ambient accent --}}
            <div class="absolute -right-12 -top-12 w-36 h-36 {{ $n->isSystemWide() ? 'bg-[#1e4f8f]/10' : 'bg-emerald-50' }} rounded-full hidden pointer-events-none"></div>

            <div class="flex items-start gap-4 relative z-10">
                {{-- Surreal 3D Icon Container --}}
                <div class="relative shrink-0">
                    <div class="w-12 h-12 rounded-lg p-0.5 shadow-sm {{ $n->isSystemWide() ? ' bg-[#1e4f8f] ' : ' bg-emerald-600 ' }}">
                        <div class="w-full h-full rounded-[14px] bg-white flex items-center justify-center">
                            @if ($n->isSystemWide())
                                <svg class="w-6 h-6 text-[#1e4f8f] filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-emerald-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-base text-slate-900 tracking-wide">{{ $n->title }}</h3>
                        @if ($n->isSystemWide())
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-[#1e4f8f]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#1e4f8f]"></span>
                                Toàn trường
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">
                                Lớp {{ $n->classRoom?->class_name }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600 leading-relaxed font-normal">{{ $n->body }}</p>
                    <div class="mt-3 flex items-center gap-2 text-xs text-slate-500 font-medium">
                        <span class="text-slate-600 font-semibold">{{ $n->author?->full_name }}</span>
                        <span>•</span>
                        <span>{{ $n->created_at?->format('H:i d/m/Y') }}</span>
                        <span class="text-[#1e4f8f]">({{ $n->created_at?->diffForHumans() }})</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="glass-card-3d p-12 text-center text-slate-500 flex flex-col items-center justify-center">
            <div class="w-16 h-16 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <p class="font-medium text-sm text-slate-500">Hiện chưa có thông báo nào dành cho bạn.</p>
        </div>
    @endforelse

    {{ $notifications->links() }}
</div>
@endsection
