@extends('layouts.app')
<!-- ( Đăng thêm ) Tạo giao diện sửa bài tập -->

@section('title', 'Sửa bài tập - ' . $class->class_name)

@section('content')
<div class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors" title="Quay lại">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
            </a>
            <h2 class="text-base font-bold text-slate-800">Sửa bài tập: {{ $assignment->title }}</h2>
        </div>
    </div>

    <!-- ( Đăng sửa ) Sử dụng phương thức PUT và enctype multipart/form-data để hỗ trợ upload file khi cập nhật, novalidate để hiện lỗi ui -->
    <form method="POST" action="{{ route('teacher.assignments.update', $assignment) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
            <input type="text" name="title" value="{{ old('title', $assignment->title) }}" required
                   class="w-full rounded-lg border @error('title') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-[#1e4f8f] outline-none">
            @error('title')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hình thức</label>
                <!-- Không cho phép sửa hình thức -->
                <input type="text" disabled value="{{ $assignment->typeLabel() }}" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 outline-none">
                <!-- ( Đăng thêm ) Input ẩn để qua mặt validation require type -->
                <input type="hidden" name="type" value="{{ $assignment->type }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hạn nộp *</label>
                <input type="datetime-local" id="edit_due_date_input" name="due_date" value="{{ old('due_date', $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '') }}" required
                       class="w-full rounded-lg border @error('due_date') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-[#1e4f8f] outline-none">
                
                <!-- Phím tắt gia hạn nhanh -->
                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-slate-600">
                    <span class="text-[11px] text-slate-400">Gia hạn nhanh:</span>
                    <button type="button" onclick="addDaysToDueDate(1)" class="rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:text-[#1e4f8f] px-2 py-0.5 text-[11px] font-medium transition">
                        +1 Ngày
                    </button>
                    <button type="button" onclick="addDaysToDueDate(3)" class="rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:text-[#1e4f8f] px-2 py-0.5 text-[11px] font-medium transition">
                        +3 Ngày
                    </button>
                    <button type="button" onclick="addDaysToDueDate(7)" class="rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:text-[#1e4f8f] px-2 py-0.5 text-[11px] font-medium transition">
                        +1 Tuần
                    </button>
                    <button type="button" onclick="setSundayDueDate()" class="rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:text-[#1e4f8f] px-2 py-0.5 text-[11px] font-medium transition">
                        Chủ Nhật (23:59)
                    </button>
                </div>

                @error('due_date')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Thang điểm *</label>
                <input type="number" name="max_score" value="{{ old('max_score', $assignment->max_score) }}" required min="1" max="100" step="0.5"
                       class="w-full rounded-lg border @error('max_score') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-[#1e4f8f] outline-none">
                @error('max_score')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả / Đề bài</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-[#1e4f8f] outline-none">{{ old('description', $assignment->description) }}</textarea>
        </div>

        <!-- ( Đăng sửa ) Thêm trường input cho file đính kèm -->
        @if(!$assignment->isQuiz())
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tài liệu đính kèm (nếu muốn thay đổi)</label>
            @if($assignment->attachment_path)
                <p class="mb-2 text-sm text-[#1e4f8f]">Đã có file: <a href="{{ route('teacher.assignments.download-attachment', $assignment) }}" class="underline">Tải xuống</a></p>
            @endif
            <input type="file" name="attachment_file" class="w-full rounded-lg border @error('attachment_file') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm bg-white outline-none">
            @error('attachment_file')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
        @endif

        @if($assignment->isQuiz())
        {{-- Khu vực câu hỏi Quiz --}}
        <div id="quizSection" class="mt-4 rounded-md border border-blue-200 bg-blue-50/40 p-4">
            <h3 class="mb-4 font-bold text-slate-800">Bộ câu hỏi trắc nghiệm</h3>

            <!-- ( Đăng sửa ) Checkbox cấu hình hiển thị đáp án được đưa vào trong Quiz và có UI cảnh báo -->
            <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="show_answers" value="1" @checked(old('show_answers', $assignment->show_answers)) class="mt-1 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-bold text-amber-900">Cho phép học sinh xem đáp án đúng sau khi nộp bài</span>
                        <p class="mt-1 text-xs text-amber-700">⚠️ <span class="font-bold">Chú ý bảo mật:</span> Nếu bật tính năng này, học sinh sẽ thấy ngay đáp án đúng của tất cả các câu hỏi ngay sau khi nhấn nộp bài. Điều này có thể dẫn đến việc học sinh làm bài xong trước chia sẻ đáp án cho các học sinh chưa làm bài. Hãy cân nhắc tắt tính năng này nếu đây là bài thi chính thức.</p>
                    </div>
                </label>
            </div>

            <div class="mb-3 flex items-center justify-end gap-2">
                <button type="button" onclick="openAiModal()" class="flex items-center gap-1.5 rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-600 hover:bg-amber-200 transition-colors">Tạo bằng AI</button>
                <button type="button" onclick="addQuestion()" class="flex items-center gap-1.5 rounded-lg bg-[#1e4f8f] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#163c6d] transition-colors">+ Thêm câu hỏi</button>
            </div>
            
            <div id="questionList" class="space-y-4">
                @foreach(old('questions', $assignment->questions) as $index => $q)
                    @php
                        // Hỗ trợ hiển thị đúng cả khi form lỗi (old) và khi load lần đầu (Model)
                        $content = is_array($q) ? ($q['content'] ?? '') : $q->content;
                        $optA = is_array($q) ? ($q['option_a'] ?? '') : $q->option_a;
                        $optB = is_array($q) ? ($q['option_b'] ?? '') : $q->option_b;
                        $optC = is_array($q) ? ($q['option_c'] ?? '') : $q->option_c;
                        $optD = is_array($q) ? ($q['option_d'] ?? '') : $q->option_d;
                        $correct = is_array($q) ? ($q['correct_answer'] ?? '') : $q->correct_answer;
                        $qtype = is_array($q) ? ($q['question_type'] ?? 'mcq') : ($q->question_type ?? 'mcq');
                        $correctText = is_array($q) ? ($q['correct_text'] ?? '') : ($q->correct_text ?? '');
                        $explanation = is_array($q) ? ($q['explanation'] ?? '') : ($q->explanation ?? '');
                    @endphp
                    <div class="question-item relative rounded-lg border border-slate-200 bg-white p-4 pt-8 shadow-sm">
                        <button type="button" onclick="this.closest('.question-item').remove(); updateQuestionIndexes();"
                                class="absolute right-3 top-3 text-slate-500 hover:text-red-500" title="Xóa câu hỏi">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>

                        <div class="mb-2 ml-10">
                            <select name="questions[{{ $index }}][question_type]" onchange="toggleType(this)" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm bg-white outline-none">
                                <option value="mcq" @selected($qtype==='mcq')>Trắc nghiệm (A/B/C/D)</option>
                                <option value="true_false" @selected($qtype==='true_false')>Đúng/Sai</option>
                                <option value="fill_blank" @selected($qtype==='fill_blank')>Điền khuyết</option>
                                <option value="code_reading" @selected($qtype==='code_reading')>Đọc code</option>
                            </select>
                        </div>

                        <div class="mb-3 flex items-center gap-3">
                            <span class="question-index font-bold text-slate-700">Câu {{ $index + 1 }}</span>
                            <div class="flex-1">
                                <input type="text" name="questions[{{ $index }}][content]" value="{{ $content }}" required placeholder="Nhập câu hỏi..." class="w-full rounded-lg border @error('questions.'.$index.'.content') border-red-500 @else border-slate-300 @enderror px-3 py-1.5 text-sm outline-none focus:border-[#1e4f8f]">
                                @error('questions.'.$index.'.content')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="choice-fields grid gap-2 sm:grid-cols-2 ml-10 {{ $qtype==='fill_blank' ? 'hidden' : '' }}">
                            @foreach(['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD] as $char => $val)
                            <div class="flex items-start gap-2 {{ $char==='C' ? 'opt-c' : ($char==='D' ? 'opt-d' : '') }}">
                                <input type="radio" name="questions[{{ $index }}][correct_answer]" value="{{ $char }}" @checked($correct == $char) class="mt-1 h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-medium text-slate-600 mt-1">{{ $char }}.</span>
                                <div class="flex-1">
                                    <input type="text" name="questions[{{ $index }}][option_{{ strtolower($char) }}]" value="{{ $val }}" placeholder="Đáp án {{ $char }}" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm outline-none focus:border-[#1e4f8f]">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="fillblank-fields ml-10 {{ $qtype==='fill_blank' ? '' : 'hidden' }}">
                            <input type="text" name="questions[{{ $index }}][correct_text]" value="{{ $correctText }}" placeholder="Đáp án đúng (nhiều đáp án cách nhau bằng dấu |)" class="w-full rounded-md border @error('questions.'.$index.'.correct_text') border-red-500 @else border-slate-200 @enderror px-2 py-1.5 text-sm outline-none focus:border-[#1e4f8f]">
                            @error('questions.'.$index.'.correct_text') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="ml-10 mt-2">
                            <input type="text" name="questions[{{ $index }}][explanation]" value="{{ $explanation }}" placeholder="Giải thích đáp án (tùy chọn)" class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs text-slate-600 outline-none focus:border-[#1e4f8f]">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex items-center gap-3 pt-4">
            <button type="submit" class="rounded-lg bg-[#1e4f8f] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#163c6d]">Cập nhật bài tập</button>
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>

@if($assignment->isQuiz())
<script>
    let qIndex = {{ count(old('questions', $assignment->questions)) }};

    function addQuestion() {
        const i = qIndex;
        const tpl = `
            <div class="question-item relative rounded-lg border border-slate-200 bg-white p-4 pt-8 shadow-sm">
                <button type="button" onclick="this.closest('.question-item').remove(); updateQuestionIndexes();"
                        class="absolute right-3 top-3 text-slate-500 hover:text-red-500" title="Xóa câu hỏi">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>

                <div class="mb-2 ml-10">
                    <select name="questions[${i}][question_type]" onchange="toggleType(this)" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm bg-white outline-none">
                        <option value="mcq">Trắc nghiệm (A/B/C/D)</option>
                        <option value="true_false">Đúng/Sai</option>
                        <option value="fill_blank">Điền khuyết</option>
                        <option value="code_reading">Đọc code</option>
                    </select>
                </div>

                <div class="mb-3 flex items-center gap-3">
                    <span class="question-index font-bold text-slate-700">Câu ${i + 1}</span>
                    <input type="text" name="questions[${i}][content]" required placeholder="Nhập câu hỏi..." class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-[#1e4f8f]">
                </div>

                <div class="choice-fields grid gap-2 sm:grid-cols-2 ml-10">
                    ${['A','B','C','D'].map(char => `
                        <div class="flex items-center gap-2 ${char==='C'?'opt-c':(char==='D'?'opt-d':'')}">
                            <input type="radio" name="questions[${i}][correct_answer]" value="${char}" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-medium text-slate-600">${char}.</span>
                            <input type="text" name="questions[${i}][option_${char.toLowerCase()}]" placeholder="Đáp án ${char}" class="flex-1 rounded-md border border-slate-200 px-2 py-1 text-sm outline-none focus:border-[#1e4f8f]">
                        </div>
                    `).join('')}
                </div>

                <div class="fillblank-fields ml-10 hidden">
                    <input type="text" name="questions[${i}][correct_text]" placeholder="Đáp án đúng (nhiều đáp án cách nhau bằng dấu |)" class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-[#1e4f8f]">
                </div>

                <div class="ml-10 mt-2">
                    <input type="text" name="questions[${i}][explanation]" placeholder="Giải thích đáp án (tùy chọn)" class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs text-slate-600 outline-none focus:border-[#1e4f8f]">
                </div>
            </div>
        `;
        document.getElementById('questionList').insertAdjacentHTML('beforeend', tpl);
        qIndex++;
        updateQuestionIndexes();
    }

    // Hiện/ẩn các trường theo loại câu hỏi
    function toggleType(sel) {
        const item = sel.closest('.question-item');
        const type = sel.value;
        const choice = item.querySelector('.choice-fields');
        const fill = item.querySelector('.fillblank-fields');
        const optC = item.querySelector('.opt-c');
        const optD = item.querySelector('.opt-d');
        const optA = item.querySelector('input[name$="[option_a]"]');
        const optB = item.querySelector('input[name$="[option_b]"]');

        if (type === 'fill_blank') {
            choice.classList.add('hidden');
            fill.classList.remove('hidden');
            return;
        }
        choice.classList.remove('hidden');
        fill.classList.add('hidden');
        if (type === 'true_false') {
            if (optA && !optA.value.trim()) optA.value = 'Đúng';
            if (optB && !optB.value.trim()) optB.value = 'Sai';
            if (optC) optC.classList.add('hidden');
            if (optD) optD.classList.add('hidden');
        } else {
            if (optC) optC.classList.remove('hidden');
            if (optD) optD.classList.remove('hidden');
        }
    }

    function updateQuestionIndexes() {
        document.querySelectorAll('.question-item').forEach((item, index) => {
            item.querySelector('.question-index').textContent = 'Câu ' + (index + 1);

            // Cập nhật lại thuộc tính name cho chuẩn array index
            item.querySelector('input[name$="[content]"]').name = `questions[${index}][content]`;
            ['A','B','C','D'].forEach(char => {
                const optKey = `option_${char.toLowerCase()}`;
                const el = item.querySelector(`input[name$="[${optKey}]"]`);
                if (el) el.name = `questions[${index}][${optKey}]`;
            });
            item.querySelectorAll('input[type="radio"]').forEach(r => {
                r.name = `questions[${index}][correct_answer]`;
            });
            const qt = item.querySelector('select[name$="[question_type]"]');
            if (qt) qt.name = `questions[${index}][question_type]`;
            const ct = item.querySelector('input[name$="[correct_text]"]');
            if (ct) ct.name = `questions[${index}][correct_text]`;
            const ex = item.querySelector('input[name$="[explanation]"]');
            if (ex) ex.name = `questions[${index}][explanation]`;
        });
        qIndex = document.querySelectorAll('.question-item').length;
    }

    // Khởi tạo trạng thái hiển thị cho các câu hỏi đã render sẵn
    document.querySelectorAll('#questionList select[name$="[question_type]"]').forEach(toggleType);

    // AI Modal logic
    function openAiModal() {
        document.getElementById('aiModal').classList.remove('hidden');
    }
    function closeAiModal() {
        document.getElementById('aiModal').classList.add('hidden');
    }

    async function generateQuestionsWithAi(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const originalBtnText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Đang sinh...`;
        
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('topic', document.getElementById('aiTopic').value);
        formData.append('count', document.getElementById('aiCount').value);

        const checkedTypes = [...document.querySelectorAll('.ai-type:checked')].map(c => c.value);
        if (checkedTypes.length === 0) checkedTypes.push('mcq');
        checkedTypes.forEach(t => formData.append('types[]', t));
        const diff = document.getElementById('aiDifficulty');
        if (diff && diff.value) formData.append('difficulty', diff.value);

        const fileInput = document.getElementById('aiFile');
        if (fileInput.files.length > 0) {
            formData.append('document', fileInput.files[0]);
        }

        try {
            const response = await fetch('{{ route("teacher.assignments.ai-generate") }}', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                // Thêm các câu hỏi vào giao diện
                result.data.forEach(q => {
                    addQuestion();
                    const items = document.querySelectorAll('.question-item');
                    const newItem = items[items.length - 1];

                    const typeSel = newItem.querySelector('select[name$="[question_type]"]');
                    typeSel.value = q.question_type || 'mcq';
                    toggleType(typeSel);

                    newItem.querySelector('input[name$="[content]"]').value = q.content || '';
                    newItem.querySelector('input[name$="[explanation]"]').value = q.explanation || '';

                    if ((q.question_type || 'mcq') === 'fill_blank') {
                        newItem.querySelector('input[name$="[correct_text]"]').value = q.correct_text || '';
                    } else {
                        newItem.querySelector('input[name$="[option_a]"]').value = q.option_a || '';
                        newItem.querySelector('input[name$="[option_b]"]').value = q.option_b || '';
                        newItem.querySelector('input[name$="[option_c]"]').value = q.option_c || '';
                        newItem.querySelector('input[name$="[option_d]"]').value = q.option_d || '';
                        const radio = newItem.querySelector(`input[type="radio"][value="${(q.correct_answer || 'A').toUpperCase()}"]`);
                        if (radio) radio.checked = true;
                    }
                });
                closeAiModal();
                showToast(`Đã sinh thành công ${result.data.length} câu hỏi!`, 'success');
            } else {
                showToast('Lỗi: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Đã xảy ra lỗi khi kết nối với AI. Vui lòng thử lại sau.', 'error');
            console.error(error);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
        }
    }

    function addDaysToDueDate(days) {
        const input = document.getElementById('edit_due_date_input');
        if (!input) return;

        let baseTime = new Date();
        const currentVal = input.value;
        if (currentVal) {
            const parsed = new Date(currentVal);
            if (!isNaN(parsed) && parsed > baseTime) {
                baseTime = parsed;
            }
        }

        baseTime.setDate(baseTime.getDate() + days);
        baseTime.setHours(23, 59, 0, 0);

        const pad = (n) => String(n).padStart(2, '0');
        const year = baseTime.getFullYear();
        const month = pad(baseTime.getMonth() + 1);
        const date = pad(baseTime.getDate());
        const hours = pad(baseTime.getHours());
        const minutes = pad(baseTime.getMinutes());
        input.value = `${year}-${month}-${date}T${hours}:${minutes}`;
    }

    function setSundayDueDate() {
        const input = document.getElementById('edit_due_date_input');
        if (!input) return;

        let d = new Date();
        const day = d.getDay();
        const diff = (7 - day) % 7;
        d.setDate(d.getDate() + (diff === 0 ? 7 : diff));
        d.setHours(23, 59, 0, 0);

        const pad = (n) => String(n).padStart(2, '0');
        const year = d.getFullYear();
        const month = pad(d.getMonth() + 1);
        const date = pad(d.getDate());
        const hours = pad(d.getHours());
        const minutes = pad(d.getMinutes());
        input.value = `${year}-${month}-${date}T${hours}:${minutes}`;
    }
</script>

@include('components.toast')

<!-- Modal AI Sinh Câu Hỏi -->
<div id="aiModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-50" onclick="closeAiModal()"></div>
    
    <!-- Modal Content -->
    <div class="relative z-10 w-full max-w-md rounded-lg bg-white p-6 shadow-sm">
        <!-- Close button -->
        <button onclick="closeAiModal()" class="absolute right-4 top-4 text-slate-500 hover:text-slate-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="mb-6 flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-amber-100 text-2xl text-amber-500">
                
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">Trợ lý AI Sinh Câu Hỏi</h3>
                <p class="text-sm text-slate-500">Tạo câu hỏi trắc nghiệm tự động bằng Gemini AI</p>
            </div>
        </div>

        <form id="aiForm" onsubmit="generateQuestionsWithAi(event)" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Chủ đề câu hỏi</label>
                <input type="text" id="aiTopic" placeholder="Ví dụ: Cú pháp PHP, Mảng trong Javascript, Lịch sử..." required class="w-full rounded-md border border-blue-400 px-4 py-2.5 text-sm outline-none focus:border-[#1e4f8f] focus:ring-1 focus:ring-[#1e4f8f]">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Số lượng câu hỏi</label>
                <input type="number" id="aiCount" value="5" min="1" max="50" required class="w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-[#1e4f8f] focus:ring-1 focus:ring-[#1e4f8f]">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Dạng câu hỏi</label>
                <div class="grid grid-cols-2 gap-2 text-sm text-slate-700">
                    <label class="flex items-center gap-2"><input type="checkbox" class="ai-type" value="mcq" checked> Trắc nghiệm</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="ai-type" value="true_false"> Đúng/Sai</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="ai-type" value="fill_blank"> Điền khuyết</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="ai-type" value="code_reading"> Đọc code</label>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Độ khó (thang Bloom)</label>
                <select id="aiDifficulty" class="w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-[#1e4f8f]">
                    <option value="">Tự động (xen kẽ)</option>
                    <option value="Nhớ (Remember)">Nhớ (Remember)</option>
                    <option value="Hiểu (Understand)">Hiểu (Understand)</option>
                    <option value="Vận dụng (Apply)">Vận dụng (Apply)</option>
                    <option value="Phân tích (Analyze)">Phân tích (Analyze)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Tài liệu tham khảo (Tùy chọn)</label>
                <input type="file" id="aiFile" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 outline-none file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-[#1e4f8f] hover:file:bg-blue-100">
                <p class="mt-2 text-xs text-slate-500">Hỗ trợ file .txt, .pdf, .docx. AI sẽ đọc tài liệu để sinh câu hỏi sát nội dung.</p>
            </div>

            <div class="mt-8 flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeAiModal()" class="rounded-md bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Hủy</button>
                <button type="submit" class="flex items-center gap-2 rounded-md bg-[#1e4f8f] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#163c6d] transition-colors">Sinh câu hỏi</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
