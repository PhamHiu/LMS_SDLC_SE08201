@extends('layouts.app')

@section('title', $class ? 'Sửa lớp học' : 'Tạo lớp học')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-1 text-base font-bold text-slate-800">{{ $class ? 'Cập nhật: '.$class->class_name : 'Tạo lớp học mới' }}</h2>
    @unless ($class)
        <p class="mb-5 text-sm text-slate-500">Hệ thống sẽ tự động sinh danh sách buổi học hàng tuần theo tổng số buổi.</p>
    @endunless

    <form method="POST" action="{{ $class ? route('admin.classes.update', $class) : route('admin.classes.store') }}" class="mt-4 space-y-4">
        @csrf
        @if ($class) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tên lớp * <span class="text-xs text-slate-400">(VD: WD19301)</span></label>
                <input type="text" name="class_name" value="{{ old('class_name', $class?->class_name) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Giảng viên phụ trách *</label>
                <select name="user_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Chọn giảng viên —</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->user_id }}" @selected(old('user_id', $class?->user_id) == $teacher->user_id)>{{ $teacher->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Môn học *</label>
                <select name="subject_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Chọn môn học —</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->subject_id }}" @selected(old('subject_id', $class?->subject_id) == $subject->subject_id)>{{ $subject->subject_code }} — {{ $subject->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Chuyên ngành *</label>
                <select name="major_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Chọn ngành —</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->major_id }}" @selected(old('major_id', $class?->major_id) == $major->major_id)>{{ $major->major_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ngày bắt đầu *</label>
                <input type="date" name="start_date" value="{{ old('start_date', $class?->start_date?->format('Y-m-d')) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ngày kết thúc</label>
                <input type="date" name="end_date" value="{{ old('end_date', $class?->end_date?->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tổng số buổi học *</label>
                <input type="number" name="total_sessions" value="{{ old('total_sessions', $class?->total_sessions ?? 12) }}" min="1" max="60" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái *</label>
                <select name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="0" @selected(old('status', (string) $class?->status) === '0')>Chưa mở</option>
                    <option value="1" @selected(old('status', (string) $class?->status) === '1')>Đang học</option>
                    <option value="2" @selected(old('status', (string) $class?->status) === '2')>Đã đóng</option>
                </select>
            </div>
            @unless ($class)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phòng học</label>
                    <input type="text" name="room" value="{{ old('room') }}" placeholder="VD: P301"
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ca học</label>
                    <input type="text" name="shift" value="{{ old('shift') }}" placeholder="VD: Ca 1 (07:15 - 09:15)"
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
            @endunless
        </div>

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $class ? 'Cập nhật' : 'Tạo lớp học' }}
            </button>
            <a href="{{ route('admin.classes.index') }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>
@endsection
