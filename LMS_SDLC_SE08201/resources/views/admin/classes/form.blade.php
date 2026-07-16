@extends('layouts.app')

@section('title', $class ? 'Sửa lớp học' : 'Tạo lớp học')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-1 text-base font-bold text-slate-800">{{ $class ? 'Cập nhật: '.$class->class_name : 'Tạo lớp học mới' }}</h2>
    @unless ($class)
        <p class="mb-5 text-sm text-slate-500">Thiết lập thông tin và thời khóa biểu để hệ thống tự động sinh buổi học.</p>
    @endunless

    <!-- Display Validation Errors if any -->
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-600">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="class_form" method="POST" action="{{ $class ? route('admin.classes.update', $class) : route('admin.classes.store') }}" class="mt-4 space-y-6">
        @csrf
        @if ($class) @method('PUT') @endif

        <!-- Khu vực 1: Thông tin lớp học -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tên lớp *</label>
                <input type="text" name="class_name" value="{{ old('class_name', $class?->class_name) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Chuyên ngành *</label>
                <select name="major_id" id="major_select" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="">— Chọn ngành —</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->major_id }}" @selected(old('major_id', $class?->major_id) == $major->major_id)>{{ $major->major_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Môn học *</label>
                <select name="subject_id" id="subject_select" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-slate-50 outline-none" disabled data-selected="{{ old('subject_id', $class?->subject_id) }}">
                    <option value="">— Vui lòng chọn ngành trước —</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Giảng viên phụ trách *</label>
                <select name="user_id" id="teacher_select" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-slate-50 outline-none" disabled data-selected="{{ old('user_id', $class?->user_id) }}">
                    <option value="">— Vui lòng chọn ngành trước —</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái *</label>
                <select name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="0" @selected(old('status', (string) $class?->status) === '0')>Chưa mở</option>
                    <option value="1" @selected(old('status', (string) $class?->status) === '1')>Đang học</option>
                    <option value="2" @selected(old('status', (string) $class?->status) === '2')>Đã đóng</option>
                </select>
            </div>
            
            @if ($class)
                <!-- Edit mode only shows end date from DB -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ngày kết thúc</label>
                    <input type="date" value="{{ $class->end_date?->format('Y-m-d') }}" disabled
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm bg-slate-100 outline-none cursor-not-allowed text-slate-500">
                </div>
            @endif
        </div>

        @unless ($class)
        <!-- Khu vực 2: Thời khóa biểu (Chỉ dành cho tạo mới) -->
        <div class="border-t border-slate-200 pt-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Cài đặt Thời khóa biểu</h3>
            
            <div class="grid gap-4 sm:grid-cols-3 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phòng học (Mặc định)</label>
                    <select name="room_id" id="room_select" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                        <option value="">— Chọn phòng học —</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->room_id }}" @selected(old('room_id') == $room->room_id)>{{ $room->room_name }} (Sức chứa: {{ $room->capacity }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ngày bắt đầu *</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Số buổi học *</label>
                    <input type="number" name="total_sessions" id="total_sessions" value="{{ old('total_sessions', 12) }}" min="1" max="60" required
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex items-end justify-between mb-2">
                <div>
                    <span class="text-sm font-medium text-slate-700">Lưới thời khóa biểu hàng tuần</span>
                    <p class="text-xs text-slate-500 mt-1">Click vào các ô trống để chọn ca học. Có thể chọn nhiều ca trong một tuần.</p>
                </div>
                <div class="text-right">
                    <span class="text-sm text-slate-600">Ngày kết thúc dự kiến: </span>
                    <span id="end_date_display" class="font-semibold text-indigo-600">...</span>
                </div>
            </div>

            <!-- Bảng TKB dạng lưới -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full min-w-[700px] border-collapse bg-white text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-32">Ca / Thứ</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 2</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 3</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 4</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 5</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 6</th>
                            <th class="border-b border-r border-slate-200 py-3 px-2 font-medium w-[12%]">Thứ 7</th>
                            <th class="border-b border-slate-200 py-3 px-2 font-medium w-[12%] text-rose-600">Chủ nhật</th>
                        </tr>
                    </thead>
                    <tbody id="timetable_body">
                        @foreach ($slots as $slot)
                            <tr>
                                <td class="border-b border-r border-slate-200 bg-slate-50 py-2 px-3 text-center text-xs font-medium text-slate-600">
                                    {{ $slot->label }}
                                </td>
                                @for ($day = 1; $day <= 7; $day++)
                                    <td class="border-b border-r border-slate-200 p-1 last:border-r-0 relative group">
                                        <button type="button" 
                                            class="tkb-cell w-full h-16 rounded flex flex-col items-center justify-center border border-dashed border-slate-300 bg-slate-50 hover:border-indigo-400 hover:bg-indigo-50 transition-colors disabled:cursor-not-allowed disabled:opacity-75 disabled:hover:border-slate-300 disabled:hover:bg-slate-50"
                                            data-day="{{ $day }}" 
                                            data-slot-id="{{ $slot->slot_id }}"
                                            title="Ca {{ $slot->slot_number }} - Thứ {{ $day === 7 ? 'Chủ nhật' : $day + 1 }}">
                                            <span class="text-xs text-slate-400 font-medium group-hover:text-indigo-500 pointer-events-none cell-text">+ Chọn</span>
                                        </button>
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 flex items-center justify-between text-xs">
                <div class="flex gap-4">
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-50 border border-slate-300 border-dashed"></span> Trống</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-indigo-500 border border-indigo-600"></span> Đã chọn</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-300"></span> Xung đột</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-200 border border-slate-300"></span> Đã có lớp khác</div>
                </div>
                <div class="font-medium text-slate-700" id="selection_counter">
                    Đã chọn: <span class="text-indigo-600 font-bold" id="selected_count">0</span> ca/tuần
                </div>
            </div>

            <!-- Hidden input store selected slots JSON -->
            <input type="hidden" name="selected_slots" id="selected_slots" value="{{ old('selected_slots', '[]') }}">
        </div>
        @endunless

        <div class="flex gap-3 pt-4 border-t border-slate-100">
            <button type="submit" id="submit_btn" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                {{ $class ? 'Lưu thay đổi' : 'Tạo lớp học' }}
            </button>
            <a href="{{ route('admin.classes.index') }}" class="rounded-lg bg-slate-100 px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200 transition-colors">Hủy</a>
        </div>
    </form>
</div>

@endsection
