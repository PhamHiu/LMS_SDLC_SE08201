@extends('layouts.app')

@section('title', 'Học liệu')

@section('content')
{{-- Thanh bộ lọc mới --}}
<div class="mb-6 inline-block max-w-full rounded-2xl border border-white/10 bg-[#030407]/90 p-4 shadow-2xl backdrop-blur-xl">
    <form method="GET" action="{{ route('student.materials.index') }}" id="material-filter-form" class="flex flex-wrap items-center gap-4">
        <div class="w-full sm:w-64">
            <label class="sr-only">Chọn môn học</label>
            <select name="subject_id" onchange="document.getElementById('material-filter-form').submit()" class="w-full rounded-xl border border-white/15 bg-slate-900/90 px-4 py-2.5 text-sm font-semibold !text-white shadow-inner focus:border-[#1558C9] focus:outline-none focus:ring-1 focus:ring-[#1558C9] cursor-pointer">
                <option value="" class="bg-slate-900 text-white">Tất cả môn học</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->subject_id }}" @selected($subjectId == $s->subject_id) class="bg-slate-900 text-white">
                        {{ $s->subject_code }} — {{ $s->subject_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-80 relative">
            <label class="sr-only">Tìm kiếm</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ $searchQuery ?? '' }}" placeholder="Tìm theo mã môn, tiêu đề..." oninput="debounceSearch()" class="w-full rounded-xl border border-white/15 bg-slate-900/90 pl-10 pr-4 py-2.5 text-sm !text-white placeholder-slate-400 shadow-inner focus:border-[#1558C9] focus:outline-none focus:ring-1 focus:ring-[#1558C9] transition-all">
        </div>
        <a href="{{ route('student.materials.index') }}" class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-300 transition-all hover:bg-white/10 hover:border-white/20 hover:text-white" title="Làm mới">
            <svg class="h-5 w-5 text-[#D4FF24]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </a>
    </form>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($materials as $m)
        <div class="rounded-2xl border border-white/10 bg-[#030407]/90 p-5 shadow-xl backdrop-blur-md transition-all duration-300 hover:border-[#1558C9]/50 hover:shadow-[0_10px_25px_rgba(21,88,201,0.2)]">
            <div class="flex items-start gap-3">
                @if ($m->isVideo())
                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-purple-500/20 border border-purple-400/40 shadow-[0_0_12px_rgba(168,85,247,0.4)] shrink-0">
                        <svg class="w-6 h-6 text-purple-300 filter drop-shadow-[0_0_6px_rgba(168,85,247,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </span>
                @else
                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-[#1558C9]/20 border border-[#1558C9]/40 shadow-[0_0_12px_rgba(21,88,201,0.4)] shrink-0">
                        <svg class="w-6 h-6 text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="font-bold !text-white line-clamp-2">{{ $m->title }}</p>
                    <p class="text-xs font-semibold !text-slate-300 mt-1">{{ $m->subject?->subject_code }} · <span class="uppercase font-bold text-[#D4FF24]">{{ $m->extension() }}</span></p>
                </div>
            </div>
            @if ($m->description)
                <p class="mt-3 text-xs !text-slate-300 line-clamp-2 bg-white/[0.03] p-2.5 rounded-lg border border-white/10">{{ $m->description }}</p>
            @endif
            <div class="mt-4 flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium !text-slate-300">
                    <svg class="w-4 h-4 text-[#D4FF24] inline-block filter drop-shadow-[0_0_4px_rgba(212,255,36,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                    <span>{{ $m->uploader?->full_name }}</span>
                </span>
                <a href="{{ route('student.materials.download', $m) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#1558C9] hover:bg-blue-600 border border-[#D4FF24]/60 px-3.5 py-1.5 text-xs font-black text-white hover:border-[#D4FF24] shadow-[0_0_10px_rgba(21,88,201,0.4)] hover:shadow-[0_0_15px_rgba(212,255,36,0.5)] transition-all">
                    <svg class="w-3.5 h-3.5 text-[#D4FF24] filter drop-shadow-[0_0_4px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Tải về</span>
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-white/20 bg-white/[0.02] p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 border border-white/10 mb-3 shadow-inner">
                <svg class="h-8 w-8 text-[#1558C9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <p class="text-base font-bold !text-slate-200">Chưa có học liệu nào phù hợp với tìm kiếm của bạn.</p>
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
        }, 1000);
    }
</script>
@endsection
