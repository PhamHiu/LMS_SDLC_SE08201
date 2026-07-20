@extends('layouts.app')

@section('title', $major ? 'Sửa chuyên ngành' : 'Thêm chuyên ngành')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-bold text-slate-800">{{ $major ? 'Cập nhật: '.$major->major_name : 'Tạo chuyên ngành mới' }}</h2>

    <form method="POST" action="{{ $major ? route('admin.majors.update', $major) : route('admin.majors.store') }}" class="space-y-4">
        @csrf
        @if ($major) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tên chuyên ngành *</label>
            <input type="text" name="major_name" value="{{ old('major_name', $major?->major_name) }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mã chuyên ngành * <span class="text-xs text-slate-400">(VD: IT, SE, EC)</span></label>
            <input type="text" name="major_code" value="{{ old('major_code', $major?->major_code) }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm uppercase focus:border-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('description', $major?->description) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $major ? 'Cập nhật' : 'Tạo mới' }}
            </button>
            <a href="{{ route('admin.majors.index') }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>
@endsection
