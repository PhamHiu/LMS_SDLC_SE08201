@extends('layouts.app')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm tên hoặc email..."
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 outline-none">
        <select name="role" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white outline-none">
            <option value="">Tất cả vai trò</option>
            <option value="ADMIN" @selected($role === 'ADMIN')>Quản trị viên</option>
            <option value="TEACHER" @selected($role === 'TEACHER')>Giảng viên</option>
            <option value="STUDENT" @selected($role === 'STUDENT')>Sinh viên</option>
        </select>
        <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Lọc</button>
    </form>
    <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Thêm tài khoản</a>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Người dùng</th>
                <th class="px-5 py-3">Vai trò</th>
                <th class="px-5 py-3">Chuyên ngành</th>
                <th class="px-5 py-3">SĐT</th>
                <th class="px-5 py-3 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">{{ mb_substr($user->full_name, 0, 1) }}</span>
                            <div>
                                <p class="font-semibold text-slate-700">{{ $user->full_name }}</p>
                                <p class="text-xs text-slate-400">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium
                            {{ $user->role === 'ADMIN' ? 'bg-rose-100 text-rose-700' : ($user->role === 'TEACHER' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                            {{ $user->roleLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-600">{{ $user->major?->major_code ?? '—' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $user->phone ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Sửa</a>
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Reset mật khẩu của {{ $user->full_name }}?')">
                                @csrf
                                <button class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">Reset MK</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Xóa tài khoản {{ $user->full_name }}? Hành động không thể hoàn tác.')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Không tìm thấy người dùng nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
