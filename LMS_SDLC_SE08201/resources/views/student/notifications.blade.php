@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
<div class="w-full space-y-4">
    <!-- Header Thông báo & Nút đánh dấu đã đọc -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1e4f8f]/10 text-[#1e4f8f]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-slate-800">Hộp thư thông báo của bạn</h2>
                <p class="text-xs text-slate-500">Bấm vào thông báo để chuyển ngay đến bài tập hoặc lớp học tương ứng</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Chú thích màu sắc: Trắng = Chưa đọc, Xanh nhạt = Đã đọc -->
            <div class="hidden md:flex items-center gap-3 text-xs text-slate-500 mr-2">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded border border-slate-300 bg-white inline-block shadow-xs"></span> Chưa đọc</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded border border-blue-200 bg-blue-50 inline-block shadow-xs"></span> Đã đọc</span>
            </div>

            <form method="POST" action="{{ route('student.notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition shadow-sm">
                    ✓ Đánh dấu tất cả đã đọc
                </button>
            </form>
        </div>
    </div>

    <!-- Danh sách thông báo -->
    @forelse ($notifications as $n)
        @php
            $isRead = $n->isReadBy(auth()->user());
        @endphp
        <a href="{{ route('student.notifications.read', $n) }}"
           class="group block rounded-lg p-5 transition-all duration-200 border relative overflow-hidden shadow-sm hover:shadow-md {{ $isRead ? 'bg-sky-50/70 border-sky-200 hover:bg-sky-100/70' : 'bg-white border-slate-200 hover:border-[#1e4f8f]/40 hover:bg-slate-50/50' }}">

            <div class="flex items-start gap-4 relative z-10">
                <!-- Icon thông báo -->
                <div class="relative shrink-0">
                    <div class="w-11 h-11 rounded-lg p-0.5 shadow-sm {{ $isRead ? 'bg-sky-200 text-sky-700' : 'bg-[#1e4f8f] text-white' }} flex items-center justify-center">
                        @if ($n->assignment_id)
                            <svg class="w-5 h-5 {{ $isRead ? 'text-[#1e4f8f]' : 'text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        @elseif ($n->isSystemWide())
                            <svg class="w-5 h-5 {{ $isRead ? 'text-[#1e4f8f]' : 'text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        @else
                            <svg class="w-5 h-5 {{ $isRead ? 'text-[#1e4f8f]' : 'text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        @endif
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-base {{ $isRead ? 'text-slate-800' : 'text-slate-900 font-extrabold' }} group-hover:text-[#1e4f8f] transition-colors">
                                {{ $n->title }}
                            </h3>

                            @if ($n->isSystemWide())
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 px-2 py-0.2 text-[10px] font-bold uppercase tracking-wider text-[#1e4f8f]">
                                    Toàn trường
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.2 text-[10px] font-bold text-emerald-700">
                                    {{ $n->classRoom?->class_name }}
                                </span>
                            @endif

                            @if ($n->assignment_id)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2 py-0.2 text-[10px] font-bold text-amber-800">
                                    📝 Bài tập
                                </span>
                            @endif
                        </div>

                        <!-- Badge trạng thái đã đọc / chưa đọc -->
                        <div>
                            @if ($isRead)
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 text-[10px] font-bold">
                                    ✓ Đã đọc
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 text-[10px] font-bold">
                                    ● Chưa đọc
                                </span>
                            @endif
                        </div>
                    </div>

                    <p class="mt-2 whitespace-pre-line text-sm {{ $isRead ? 'text-slate-600' : 'text-slate-800 font-medium' }} leading-relaxed">
                        {{ $n->body }}
                    </p>

                    <div class="mt-3 flex items-center justify-between gap-2 text-xs flex-wrap">
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <span class="text-slate-700 font-semibold">{{ $n->author?->full_name }}</span>
                            <span>•</span>
                            <span>{{ $n->created_at?->format('H:i d/m/Y') }}</span>
                            <span class="text-[#1e4f8f]">({{ $n->created_at?->diffForHumans() }})</span>
                        </div>

                        <!-- Nút điều hướng gợi ý -->
                        <div class="inline-flex items-center gap-1 text-xs font-bold text-[#1e4f8f] group-hover:translate-x-1 transition-transform">
                            @if ($n->assignment_id)
                                <span>Chuyển đến phần nộp bài tập</span> →
                            @elseif ($n->class_id)
                                <span>Chuyển đến lớp học</span> →
                            @else
                                <span>Xem chi tiết</span> →
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-lg border border-slate-200 bg-white p-12 text-center text-slate-500 flex flex-col items-center justify-center shadow-sm">
            <div class="w-16 h-16 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <p class="font-medium text-sm text-slate-500">Hiện chưa có thông báo nào dành cho bạn.</p>
        </div>
    @endforelse

    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
@include('components.toast')
@endsection
