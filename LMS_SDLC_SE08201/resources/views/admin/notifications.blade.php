@extends('layouts.app')

@section('title', 'Thông báo hệ thống')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    {{-- Form gửi thông báo --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm h-fit">
        <h2 class="mb-1 text-base font-bold text-slate-800">Gửi thông báo toàn trường</h2>
        <p class="mb-4 text-xs text-slate-400">Mọi tài khoản đều nhìn thấy thông báo này trên Dashboard.</p>
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nội dung *</label>
                <textarea name="body" rows="5" required
                          class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('body') }}</textarea>
            </div>
            <button class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">📢 Gửi thông báo</button>
        </form>
    </div>

    {{-- Danh sách thông báo --}}
    <div class="lg:col-span-2 space-y-3">
        @forelse ($notifications as $n)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800">{{ $n->title }}</h3>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $n->body }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ $n->author?->full_name }} · {{ $n->created_at?->format('H:i d/m/Y') }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.notifications.destroy', $n) }}" onsubmit="return confirm('Xóa thông báo này?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-400">Chưa có thông báo hệ thống nào.</div>
        @endforelse

        {{ $notifications->links() }}
    </div>
</div>
@endsection
