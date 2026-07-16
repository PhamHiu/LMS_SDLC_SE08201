@extends('layouts.app')

@section('title', 'Quản lý chuyên ngành')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm chuyên ngành..."
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 outline-none">
        <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Tìm</button>
    </form>
    <a href="{{ route('admin.majors.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Thêm chuyên ngành</a>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Mã</th>
                <th class="px-5 py-3">Tên chuyên ngành</th>
                <th class="px-5 py-3 text-center">Môn học</th>
                <th class="px-5 py-3 text-center">Người dùng</th>
                <th class="px-5 py-3 text-center">Lớp</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($majors as $major)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><span class="rounded bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">{{ $major->major_code }}</span></td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-slate-700">{{ $major->major_name }}</p>
                        <p class="text-xs text-slate-400 line-clamp-1">{{ $major->description }}</p>
                    </td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $major->subjects_count }}</td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $major->users_count }}</td>
                    <td class="px-5 py-3 text-center text-slate-600">{{ $major->classes_count }}</td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.majors.edit', $major) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.majors.destroy', $major) }}" onsubmit="return confirm('Xóa chuyên ngành {{ $major->major_name }}?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Chưa có chuyên ngành nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $majors->links() }}</div>
@endsection
