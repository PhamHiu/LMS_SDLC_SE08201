@extends('layouts.app')

@section('title', 'Quản lý môn học')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm môn học..."
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 outline-none">
        <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Tìm</button>
    </form>
    <a href="{{ route('admin.subjects.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Thêm môn học</a>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Mã môn</th>
                <th class="px-5 py-3">Tên môn học</th>
                <th class="px-5 py-3">Thuộc chuyên ngành</th>
                <th class="px-5 py-3 text-center">Lớp</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($subjects as $subject)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><span class="rounded bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700">{{ $subject->subject_code }}</span></td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-slate-700">{{ $subject->subject_name }}</p>
                        <p class="text-xs text-slate-400 line-clamp-1">{{ $subject->description }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($subject->majors as $mj)
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-600">{{ $mj->major_code }}</span>
                            @empty
                                <span class="text-xs text-slate-400">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $subject->classes_count }}</td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.subjects.edit', $subject) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('Xóa môn {{ $subject->subject_name }}?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Chưa có môn học nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $subjects->links() }}</div>
@endsection
