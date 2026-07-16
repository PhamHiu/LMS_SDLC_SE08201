@extends('layouts.app')

@section('title', 'Quản lý lớp học')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm tên lớp..."
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 outline-none">
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white outline-none">
            <option value="">Tất cả trạng thái</option>
            <option value="0" @selected($status === '0')>Chưa mở</option>
            <option value="1" @selected($status === '1')>Đang học</option>
            <option value="2" @selected($status === '2')>Đã đóng</option>
        </select>
        <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Lọc</button>
    </form>
    <a href="{{ route('admin.classes.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Tạo lớp học</a>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Lớp</th>
                <th class="px-5 py-3">Môn học</th>
                <th class="px-5 py-3">Giảng viên</th>
                <th class="px-5 py-3 text-center">Sĩ số</th>
                <th class="px-5 py-3">Thời gian</th>
                <th class="px-5 py-3">Trạng thái</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($classes as $class)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('admin.classes.show', $class) }}" class="font-bold text-indigo-600 hover:underline">{{ $class->class_name }}</a>
                        <p class="text-xs text-slate-400">{{ $class->major?->major_code }}</p>
                    </td>
                    <td class="px-5 py-3 text-slate-600">{{ $class->subject?->subject_name }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $class->teacher?->full_name }}</td>
                    <td class="px-5 py-3 text-center"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $class->enrollments_count }}</span></td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $class->start_date?->format('d/m/Y') }} → {{ $class->end_date?->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $class->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($class->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $class->statusLabel() }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.classes.show', $class) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Quản lý</a>
                            <a href="{{ route('admin.classes.edit', $class) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.classes.destroy', $class) }}" onsubmit="return confirm('Xóa lớp {{ $class->class_name }}? Toàn bộ dữ liệu liên quan sẽ bị xóa.')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Chưa có lớp học nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $classes->links() }}</div>
@endsection
