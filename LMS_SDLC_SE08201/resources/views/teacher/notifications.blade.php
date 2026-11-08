@extends('layouts.app')

@section('title', 'Thông báo lớp')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    {{-- Form gửi thông báo --}}
    <div class="glass-card-3d p-6 h-fit border border-white/10 relative overflow-hidden">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-xl bg-[#1558C9]/30 border border-[#D4FF24]/40 flex items-center justify-center text-[#D4FF24]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <h2 class="text-base font-extrabold text-white">Gửi thông báo lớp</h2>
        </div>
        <p class="mb-5 text-xs text-slate-400">Chỉ sinh viên trong lớp được chọn nhìn thấy thông báo.</p>
        <form method="POST" action="{{ route('teacher.notifications.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Lớp nhận *</label>
                <select name="class_id" required class="w-full rounded-xl border border-white/15 px-3 py-2.5 text-sm bg-[#030407]/90 text-white outline-none focus:border-[#D4FF24]">
                    <option value="">— Chọn lớp —</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->class_id }}" @selected(old('class_id') == $c->class_id)>{{ $c->class_name }} — {{ $c->subject?->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Tiêu đề *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full rounded-xl border border-white/15 px-4 py-2.5 text-sm text-white bg-[#030407]/90 focus:border-[#D4FF24] outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Nội dung *</label>
                <textarea name="body" rows="5" required
                          class="w-full rounded-xl border border-white/15 px-4 py-2.5 text-sm text-white bg-[#030407]/90 focus:border-[#D4FF24] outline-none">{{ old('body') }}</textarea>
            </div>
            <button class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#D4FF24] hover:bg-[#c4ee14] py-3 text-sm font-black text-[#030407] shadow-lg shadow-[#D4FF24]/20 transition transform hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-4 h-4 text-[#030407]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span>GỬI THÔNG BÁO</span>
            </button>
        </form>
    </div>

    {{-- Danh sách --}}
    <div class="lg:col-span-2 space-y-4">
        @forelse ($notifications as $n)
            <div class="glass-card-3d p-5 rounded-2xl relative overflow-hidden border border-white/10 hover:border-[#D4FF24]/50 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-[#D4FF24] p-0.5 shrink-0 shadow-md">
                            <div class="w-full h-full rounded-[10px] bg-[#030407]/90 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-white text-base">{{ $n->title }}</h3>
                                <span class="rounded-full bg-emerald-500/20 border border-emerald-400/40 px-2 py-0.5 text-[11px] font-bold text-emerald-300">Lớp {{ $n->classRoom?->class_name }}</span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ $n->body }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->format('H:i d/m/Y') }}</p>
                        </div>
                    </div>
                    @if ($n->user_id === auth()->id())
                        <form method="POST" action="{{ route('teacher.notifications.destroy', $n) }}" onsubmit="return confirm('Xóa thông báo này?')">
                            @csrf @method('DELETE')
                            <button class="tool-btn !text-rose-400 !border-rose-500/30 hover:!bg-rose-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Xóa</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="glass-card-3d p-12 text-center text-sm text-slate-400">Chưa có thông báo lớp nào.</div>
        @endforelse

        {{ $notifications->links() }}
    </div>
</div>
@endsection
