@extends('layouts.app')

@section('title', 'Ticket được Admin chuyển tiếp')

@section('content')
<div class="w-full space-y-5">
    <div class="rounded-lg border border-slate-200 bg-white p-5">
        <h1 class="text-xl font-bold text-slate-900">Ticket Học vụ được chuyển tiếp</h1>
        <p class="text-xs text-slate-500 mt-1">Các yêu cầu khiếu nại điểm danh, xin khôi phục điểm danh (1 lần/môn) hoặc phúc khảo điểm do Admin chuyển tới bạn.</p>
    </div>

    @if (session('success'))
        <div class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($tickets as $ticket)
            <div class="rounded-lg border border-slate-200 bg-white p-5 space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#1e4f8f] bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">#{{ $ticket->ticket_code }}</span>
                            <span class="rounded border px-2 py-0.5 text-xs font-semibold {{ $ticket->status === 'RESOLVED' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-blue-50 text-blue-800 border-blue-200' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                            <span class="text-sm font-bold text-slate-900">{{ $ticket->topic }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span>Sinh viên:</span>
                            <span class="inline-flex items-center gap-1 rounded bg-slate-100 border border-slate-200 px-1.5 py-0.5 font-mono text-[11px] font-bold text-slate-700" title="ID tài khoản đã khóa xác thực">🔒 ID #{{ $ticket->user_id }}</span>
                            <strong class="text-slate-800">{{ $ticket->user?->full_name ?? 'N/A' }}</strong>
                            <span>({{ $ticket->user?->student_code ?? $ticket->user?->email }})</span>
                            @if ($ticket->classRoom)
                                <span>· Lớp: <strong class="text-slate-700">{{ $ticket->classRoom->class_name }}</strong> ({{ $ticket->classRoom->subject?->subject_name ?? '' }})</span>
                            @endif
                            <span>· Chuyển lúc: {{ $ticket->forwarded_at?->format('H:i d/m/Y') }}</span>
                        </p>
                    </div>

                    @if ($ticket->classRoom)
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('teacher.attendance.index', ['class_id' => $ticket->class_id]) }}"
                               class="rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                Mở Điểm danh lớp
                            </a>
                            <a href="{{ route('teacher.assignments.index') }}"
                               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Mở Bài tập / Chấm điểm
                            </a>
                        </div>
                    @endif
                </div>

                <div class="rounded-md bg-slate-50 border border-slate-200 p-3.5 text-sm text-slate-800 whitespace-pre-line">{{ $ticket->description }}</div>

                @if ($ticket->admin_note)
                    <div class="rounded-md bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
                        <strong>Ghi chú từ Admin:</strong> {{ $ticket->admin_note }}
                    </div>
                @endif

                <form method="POST" action="{{ route('teacher.tickets.reply', $ticket) }}" class="space-y-2.5 pt-2 border-t border-slate-100">
                    @csrf
                    <label class="block text-xs font-bold text-slate-700">Báo cáo kết quả kiểm tra về cho Quản trị viên (Admin sẽ phản hồi chính thức tới Sinh viên)</label>
                    <textarea name="teacher_reply" rows="2" required placeholder="Ví dụ: Đã khôi phục điểm danh (1/1 lượt môn) buổi ngày ..., hoặc đã rà soát lại bài chấm..."
                              class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs text-slate-800">{{ $ticket->teacher_reply }}</textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                            Gửi báo cáo về cho Admin
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                Hiện chưa có ticket nào được Admin chuyển tiếp cho bạn.
            </div>
        @endforelse
    </div>

    <div>{{ $tickets->links() }}</div>
</div>
@endsection
