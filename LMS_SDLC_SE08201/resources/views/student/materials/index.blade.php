@extends('layouts.app')

@section('title', 'Học liệu')

@section('content')
<form method="GET" class="mb-5 flex gap-2">
    <select name="subject_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white outline-none">
        <option value="">Tất cả môn học</option>
        @foreach ($subjects as $s)
            <option value="{{ $s->subject_id }}" @selected($subjectId == $s->subject_id)>{{ $s->subject_name }}</option>
        @endforeach
    </select>
    <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Lọc</button>
</form>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($materials as $m)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl">{{ $m->isVideo() ? '🎬' : '📄' }}</span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-700 line-clamp-2">{{ $m->title }}</p>
                    <p class="text-xs text-slate-400">{{ $m->subject?->subject_code }} · {{ strtoupper($m->extension()) }}</p>
                </div>
            </div>
            @if ($m->description)
                <p class="mt-3 text-xs text-slate-500 line-clamp-2">{{ $m->description }}</p>
            @endif
            <div class="mt-4 flex items-center justify-between">
                <span class="text-xs text-slate-400">👨‍🏫 {{ $m->uploader?->full_name }}</span>
                <a href="{{ route('student.materials.download', $m) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ Tải về</a>
            </div>
        </div>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400">Chưa có học liệu nào cho các môn bạn đang học.</div>
    @endforelse
</div>

<div class="mt-4">{{ $materials->links() }}</div>
@endsection
