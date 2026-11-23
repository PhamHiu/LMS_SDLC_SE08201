@extends('layouts.app')

@section('title', 'Quản lý Ticket Hỗ trợ Học vụ')

@section('content')
<div class="w-full space-y-5">
    <div class="rounded-lg border border-slate-200 bg-white p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Quản lý Ticket Hỗ trợ Học vụ</h1>
                <p class="text-xs text-slate-500 mt-1">Tiếp nhận yêu cầu từ sinh viên, xử lý trực tiếp hoặc chuyển tiếp tới Giảng viên phụ trách lớp.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('admin.tickets.index') }}" class="rounded-md border px-3 py-1.5 font-semibold {{ !$statusFilter ? 'bg-[#1e4f8f] text-white border-[#1e4f8f]' : 'bg-white text-slate-700 border-slate-300' }}">
                    Tất cả ({{ $counts['all'] }})
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'OPEN']) }}" class="rounded-md border px-3 py-1.5 font-semibold {{ $statusFilter === 'OPEN' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-300' }}">
                    Chờ tiếp nhận ({{ $counts['OPEN'] }})
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'IN_PROGRESS']) }}" class="rounded-md border px-3 py-1.5 font-semibold {{ $statusFilter === 'IN_PROGRESS' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-300' }}">
                    Đang xử lý / Đã chuyển GV ({{ $counts['IN_PROGRESS'] }})
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'RESOLVED']) }}" class="rounded-md border px-3 py-1.5 font-semibold {{ $statusFilter === 'RESOLVED' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-300' }}">
                    Đã giải quyết ({{ $counts['RESOLVED'] }})
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($tickets as $ticket)
            @php
                $badgeClass = match (true) {
                    $ticket->status === 'OPEN' => 'bg-amber-50 text-amber-800 border-amber-200',
                    $ticket->status === 'IN_PROGRESS' && $ticket->assigned_teacher_id => 'bg-blue-50 text-blue-800 border-blue-200',
                    $ticket->status === 'IN_PROGRESS' => 'bg-sky-50 text-sky-800 border-sky-200',
                    $ticket->status === 'RESOLVED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                };
            @endphp
            <div class="rounded-lg border border-slate-200 bg-white p-5 space-y-4">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#1e4f8f] bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">#{{ $ticket->ticket_code }}</span>
                            <span class="rounded border px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $ticket->statusLabel() }}</span>
                            <span class="text-sm font-bold text-slate-900">{{ $ticket->topic }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1.5">
                            Người gửi: <strong class="text-slate-700">{{ $ticket->user?->full_name ?? 'N/A' }}</strong> ({{ $ticket->user?->email ?? '' }})
                            @if ($ticket->classRoom)
                                · Lớp: <strong class="text-slate-700">{{ $ticket->classRoom->class_name }}</strong> ({{ $ticket->classRoom->subject?->subject_name ?? '' }})
                            @endif
                            · Gửi lúc: {{ $ticket->created_at?->format('H:i d/m/Y') }}
                        </p>
                    </div>
                    @if ($ticket->assignedTeacher)
                        <div class="text-xs rounded-md bg-blue-50 border border-blue-200 px-3 py-1.5 text-blue-900 shrink-0">
                            GV phụ trách: <strong>{{ $ticket->assignedTeacher->full_name }}</strong>
                            @if ($ticket->forwarded_at)
                                <span class="text-blue-700">({{ $ticket->forwarded_at->format('H:i d/m') }})</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="rounded-md bg-slate-50 border border-slate-200 p-3.5 text-sm text-slate-800 whitespace-pre-line">{{ $ticket->description }}</div>

                @if ($ticket->teacher_reply)
                    <div class="rounded-md bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-900">
                        <p class="font-bold mb-1">Phản hồi từ Giảng viên ({{ $ticket->assignedTeacher?->full_name }} · {{ $ticket->teacher_replied_at?->format('H:i d/m/Y') }}):</p>
                        <p class="whitespace-pre-line">{{ $ticket->teacher_reply }}</p>
                    </div>
                @endif

                @if ($ticket->resolution_note && $ticket->resolution_note !== $ticket->teacher_reply)
                    <div class="rounded-md bg-slate-100 border border-slate-200 p-3 text-xs text-slate-800">
                        <p class="font-bold mb-1">Phản hồi / Kết quả chốt từ Admin:</p>
                        <p class="whitespace-pre-line">{{ $ticket->resolution_note }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                    <!-- Form 1: Chuyển tiếp cho Giảng viên phụ trách -->
                    <form method="POST" action="{{ route('admin.tickets.forward', $ticket) }}" class="rounded-md border border-slate-200 bg-slate-50/70 p-3.5 space-y-2.5">
                        @csrf
                        <p class="text-xs font-bold text-slate-800">1. Chuyển tiếp cho Giảng viên phụ trách</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <select name="class_id" class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 text-xs text-slate-800">
                                <option value="">— Lớp liên quan (tùy chọn) —</option>
                                @foreach ($classes as $c)
                                    <option value="{{ $c->class_id }}" @selected($ticket->class_id === $c->class_id)>
                                        {{ $c->class_name }} (GV: {{ $c->teacher?->full_name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            <select name="assigned_teacher_id" required class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 text-xs text-slate-800">
                                <option value="">— Chọn Giảng viên nhận —</option>
                                @foreach ($teachers as $gv)
                                    <option value="{{ $gv->user_id }}" @selected($ticket->assigned_teacher_id === $gv->user_id || (!$ticket->assigned_teacher_id && $ticket->classRoom?->user_id === $gv->user_id))>
                                        {{ $gv->full_name }} ({{ $gv->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" name="admin_note" value="{{ $ticket->admin_note }}" placeholder="Ghi chú cho Giảng viên (ví dụ: Nhờ thầy kiểm tra điểm danh / khôi phục 1/1...)"
                               class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800">
                        <button type="submit" class="rounded-md bg-[#1e4f8f] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#163c6d]">
                            Chuyển tiếp & Gửi thông báo cho Giảng viên
                        </button>
                    </form>

                    <!-- Form 2: Admin trực tiếp phản hồi & chốt kết quả -->
                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="rounded-md border border-slate-200 bg-slate-50/70 p-3.5 space-y-2.5">
                        @csrf
                        <p class="text-xs font-bold text-slate-800">2. Admin phản hồi trực tiếp cho Sinh viên</p>
                        <textarea name="resolution_note" rows="2" required placeholder="Nhập nội dung phản hồi kết quả cho sinh viên..."
                                  class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800">{{ $ticket->resolution_note }}</textarea>
                        <div class="flex items-center justify-between gap-2">
                            <select name="status" class="rounded-md border border-slate-300 bg-white px-2.5 py-2 text-xs text-slate-800">
                                <option value="RESOLVED" @selected($ticket->status === 'RESOLVED')>Đã giải quyết</option>
                                <option value="IN_PROGRESS" @selected($ticket->status === 'IN_PROGRESS')>Đang xử lý</option>
                                <option value="CLOSED" @selected($ticket->status === 'CLOSED')>Đóng phiếu</option>
                            </select>
                            <button type="submit" class="rounded-md bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                Lưu phản hồi & Báo Sinh viên
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                Chưa có ticket hỗ trợ nào.
            </div>
        @endforelse
    </div>

    <div>{{ $tickets->links() }}</div>
</div>
@endsection
