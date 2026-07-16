@extends('layouts.app')

@section('title', 'Lớp '.$class->class_name)

@section('content')
{{-- Thông tin lớp --}}
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-800">{{ $class->class_name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $class->subject?->subject_code }} — {{ $class->subject?->subject_name }} · Ngành {{ $class->major?->major_name }}</p>
            <p class="mt-1 text-sm text-slate-500">👨‍🏫 GV: <b class="text-slate-700">{{ $class->teacher?->full_name }}</b>
               · 🗓️ {{ $class->start_date?->format('d/m/Y') }} → {{ $class->end_date?->format('d/m/Y') }}
               · {{ $class->total_sessions }} buổi</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $class->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($class->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $class->statusLabel() }}</span>
            <a href="{{ route('admin.classes.edit', $class) }}" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Sửa lớp</a>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Danh sách sinh viên --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-bold text-slate-800">Sinh viên trong lớp ({{ $class->students->count() }})</h3>
        </div>
        <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($class->students as $sv)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">{{ mb_substr($sv->full_name, 0, 1) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-700">{{ $sv->full_name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $sv->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.classes.students.remove', [$class, $sv]) }}" onsubmit="return confirm('Xóa {{ $sv->full_name }} khỏi lớp?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                    </form>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-400">Chưa có sinh viên nào trong lớp.</li>
            @endforelse
        </ul>
    </div>

    {{-- Xếp sinh viên vào lớp --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-bold text-slate-800">Xếp sinh viên vào lớp</h3>
        </div>
        <form method="POST" action="{{ route('admin.classes.students.enroll', $class) }}" class="p-5">
            @csrf
            <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-3">
                @forelse ($availableStudents as $sv)
                    <label class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                        <input type="checkbox" name="student_ids[]" value="{{ $sv->user_id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="font-medium text-slate-700">{{ $sv->full_name }}</span>
                        <span class="text-xs text-slate-400">{{ $sv->email }} @if($sv->major) · {{ $sv->major->major_code }} @endif</span>
                    </label>
                @empty
                    <p class="py-4 text-center text-sm text-slate-400">Tất cả sinh viên đã được xếp lớp.</p>
                @endforelse
            </div>
            @if ($availableStudents->isNotEmpty())
                <button class="mt-4 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Xếp vào lớp</button>
            @endif
        </form>
    </div>
</div>

{{-- Danh sách buổi học --}}
<div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h3 class="font-bold text-slate-800">Lịch các buổi học ({{ $class->sessions->count() }})</h3>
    </div>
    <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($class->sessions as $i => $session)
            <div class="rounded-xl border px-4 py-3 text-sm {{ $session->session_date->isPast() ? 'border-slate-200 bg-slate-50 text-slate-500' : 'border-indigo-200 bg-indigo-50/50 text-slate-700' }}">
                <p class="font-semibold">Buổi {{ $i + 1 }} — {{ $session->session_date->format('d/m/Y') }}</p>
                <p class="text-xs text-slate-400">{{ $session->room }} · {{ $session->shift }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
