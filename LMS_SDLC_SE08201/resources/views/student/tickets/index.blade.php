@extends('layouts.app')

@section('title', 'Phiếu hỗ trợ học vụ của tôi')

@section('content')
<div class="w-full space-y-5">
    <div class="rounded-lg border border-slate-200 bg-white p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Phiếu hỗ trợ học vụ của tôi</h1>
            <p class="text-xs text-slate-500 mt-1">Theo dõi trạng thái tiếp nhận và phản hồi chính thức từ Quản trị viên / Phòng Đào tạo.</p>
        </div>
        <button type="button" onclick="const btn = document.getElementById('btec-ai-open-btn'); if(btn) btn.click(); setTimeout(() => { const t = document.getElementById('btec-open-ticket-dialog'); if(t) t.click(); }, 150);"
                class="rounded-md bg-[#1e4f8f] px-4 py-2 text-xs font-semibold text-white hover:bg-[#163c6d]">
            + Gửi phiếu hỗ trợ mới
        </button>
    </div>

    <div class="space-y-3.5">
        @forelse ($tickets as $ticket)
            @php
                $studentStatusLabel = match ($ticket->status) {
                    'OPEN' => 'Chờ tiếp nhận',
                    'IN_PROGRESS' => 'Đang xử lý',
                    'RESOLVED' => 'Đã giải quyết',
                    'CLOSED' => 'Đã đóng',
                    default => $ticket->status,
                };
                $badgeClass = match ($ticket->status) {
                    'OPEN' => 'bg-amber-50 text-amber-800 border-amber-200',
                    'IN_PROGRESS' => 'bg-sky-50 text-sky-800 border-sky-200',
                    'RESOLVED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                };
            @endphp
            <div class="rounded-lg border border-slate-200 bg-white p-5 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-bold text-[#1e4f8f] bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">#{{ $ticket->ticket_code }}</span>
                        <span class="rounded border px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $studentStatusLabel }}</span>
                        <span class="text-sm font-bold text-slate-900">{{ $ticket->topic }}</span>
                    </div>
                    <span class="text-xs text-slate-400">{{ $ticket->created_at?->format('H:i d/m/Y') }}</span>
                </div>

                @if ($ticket->classRoom)
                    <p class="text-xs text-slate-600">
                        Lớp: <strong>{{ $ticket->classRoom->class_name }}</strong> ({{ $ticket->classRoom->subject?->subject_name ?? '' }})
                    </p>
                @endif

                <div class="rounded-md bg-slate-50 border border-slate-200 p-3 text-sm text-slate-800 whitespace-pre-line">{{ $ticket->description }}</div>

                @if ($ticket->resolution_note)
                    <div class="rounded-md bg-blue-50 border border-blue-200 p-3 text-xs text-blue-900">
                        <p class="font-bold mb-1">Phản hồi từ Quản trị viên{{ $ticket->resolved_at ? ' (' . $ticket->resolved_at->format('H:i d/m/Y') . ')' : '' }}:</p>
                        <p class="whitespace-pre-line">{{ $ticket->resolution_note }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                Bạn chưa gửi phiếu hỗ trợ học vụ nào.
            </div>
        @endforelse
    </div>

    <div>{{ $tickets->links() }}</div>
</div>
@endsection
