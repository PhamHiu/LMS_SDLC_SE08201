@extends('layouts.app')

@section('title', 'Quản lý học liệu')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    {{-- Form upload --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm h-fit">
        <h2 class="mb-4 text-base font-bold text-slate-800">⬆️ Đăng tải học liệu</h2>
        <form method="POST" action="{{ route('teacher.materials.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Môn học *</label>
                <select name="subject_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Chọn môn —</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->subject_id }}" @selected(old('subject_id') == $s->subject_id)>{{ $s->subject_code }} — {{ $s->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả</label>
                <textarea name="description" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">File * <span class="text-xs text-slate-400">(PDF/Word/PPT/Video, ≤50MB)</span></label>
                <input type="file" name="file" required class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700">
            </div>
            <button class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Đăng tải</button>
        </form>
    </div>

    {{-- Danh sách học liệu --}}
    <div class="lg:col-span-2">
        <form method="GET" class="mb-4 flex gap-2">
            <select name="subject_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white outline-none">
                <option value="">Tất cả môn học</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->subject_id }}" @selected($subjectId == $s->subject_id)>{{ $s->subject_name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Lọc</button>
        </form>

        <div class="space-y-3">
            @forelse ($materials as $m)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl">{{ $m->isVideo() ? '🎬' : '📄' }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-700">{{ $m->title }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $m->subject?->subject_code }} · {{ strtoupper($m->extension()) }} · Đăng bởi {{ $m->uploader?->full_name }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('teacher.materials.download', $m) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Tải về</a>
                        @if ($m->user_id === auth()->id())
                            <form method="POST" action="{{ route('teacher.materials.destroy', $m) }}" onsubmit="return confirm('Xóa học liệu này?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-400">Chưa có học liệu nào.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $materials->links() }}</div>
    </div>
</div>
@include('components.toast')
@endsection
