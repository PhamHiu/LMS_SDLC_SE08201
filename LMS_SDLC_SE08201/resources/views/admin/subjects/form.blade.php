@extends('layouts.app')

@section('title', $subject ? 'Sửa môn học' : 'Thêm môn học')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-bold text-slate-800">{{ $subject ? 'Cập nhật: '.$subject->subject_name : 'Tạo môn học mới' }}</h2>

    <form method="POST" action="{{ $subject ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}" class="space-y-4">
        @csrf
        @if ($subject) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tên môn học *</label>
            <input type="text" name="subject_name" value="{{ old('subject_name', $subject?->subject_name) }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mã môn học * <span class="text-xs text-slate-400">(VD: WEB301)</span></label>
            <input type="text" name="subject_code" value="{{ old('subject_code', $subject?->subject_code) }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm uppercase focus:border-indigo-500 outline-none">
        </div>
        <div class="relative" id="majorSelectContainer">
            <label class="block text-sm font-medium text-slate-700 mb-2">Thuộc các chuyên ngành</label>
            @php($selected = collect(old('major_ids', $subject?->majors?->pluck('major_id')->all() ?? [])))
            
            <div id="majorSelectDisplay" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:border-indigo-500 bg-white flex items-center justify-between">
                <span id="majorSelectText" class="text-slate-400">Chọn chuyên ngành...</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>

            <div id="majorSelectDropdown" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                <div class="p-2 grid gap-1">
                    @foreach ($majors as $major)
                        <label class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="major_ids[]" value="{{ $major->major_id }}" data-code="{{ $major->major_code }}"
                                   @checked($selected->contains($major->major_id))
                                   class="major-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            {{ $major->major_name }} <span class="text-xs text-slate-400">({{ $major->major_code }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('description', $subject?->description) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $subject ? 'Cập nhật' : 'Tạo mới' }}
            </button>
            <a href="{{ route('admin.subjects.index') }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('majorSelectContainer');
        const display = document.getElementById('majorSelectDisplay');
        const dropdown = document.getElementById('majorSelectDropdown');
        const text = document.getElementById('majorSelectText');
        const checkboxes = document.querySelectorAll('.major-checkbox');

        function updateDisplay() {
            const selectedCodes = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.dataset.code);
            
            if (selectedCodes.length > 0) {
                text.textContent = selectedCodes.join(', ');
                text.classList.remove('text-slate-400');
                text.classList.add('text-slate-800');
            } else {
                text.textContent = 'Chọn chuyên ngành...';
                text.classList.remove('text-slate-800');
                text.classList.add('text-slate-400');
            }
        }

        display.addEventListener('click', function(e) {
            dropdown.classList.toggle('hidden');
            e.stopPropagation();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateDisplay);
        });

        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Cập nhật text ban đầu nếu đã có checkbox được check
        updateDisplay();
    });
</script>
@endsection
