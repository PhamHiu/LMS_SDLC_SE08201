@extends('layouts.app')

@section('title', 'Học liệu')

@section('content')
{{-- Thanh bộ lọc mới --}}
<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
    <form method="GET" action="{{ route('student.materials.index') }}" id="material-filter-form" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="sr-only">Chọn môn học</label>
            <select name="subject_id" onchange="document.getElementById('material-filter-form').submit()" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">Tất cả môn học</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->subject_id }}" @selected($subjectId == $s->subject_id)>
                        {{ $s->subject_code }} — {{ $s->subject_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex-auto min-w-[300px] relative">
            <label class="sr-only">Tìm kiếm</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ $searchQuery ?? '' }}" placeholder="Tìm theo mã môn, tiêu đề..." oninput="debounceSearch()" class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all">
        </div>
    </form>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($materials as $m)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl">{{ $m->isVideo() ? '🎬' : '📄' }}</span>
                <div class="min-w-0">
                    <p class="font-bold text-slate-800 line-clamp-2">{{ $m->title }}</p>
                    <p class="text-xs font-medium text-slate-500 mt-1">{{ $m->subject?->subject_code }} · <span class="uppercase font-bold">{{ $m->extension() }}</span></p>
                </div>
            </div>
            @if ($m->description)
                <p class="mt-3 text-xs text-slate-600 line-clamp-2 bg-slate-50 p-2 rounded-lg border border-slate-100">{{ $m->description }}</p>
            @endif
            <div class="mt-4 flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">👨‍🏫 {{ $m->uploader?->full_name }}</span>
                <a href="{{ route('student.materials.download', $m) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700 shadow-sm">⬇ Tải về</a>
            </div>
        </div>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-3">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-600">Chưa có học liệu nào phù hợp với tìm kiếm của bạn.</p>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $materials->links() }}</div>

<script>
    let timeout = null;
    function debounceSearch() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            document.getElementById('material-filter-form').submit();
        }, 400);
    }
</script>
@endsection
