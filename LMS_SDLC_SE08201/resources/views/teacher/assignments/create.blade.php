@extends('layouts.app')

@section('title', 'Giao bài tập — '.$class->class_name)

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-bold text-slate-800">Giao bài tập cho lớp {{ $class->class_name }}</h2>

    <form id="mainForm" method="POST" action="{{ route('teacher.assignments.store', $class) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full rounded-lg border @error('title') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            @error('title')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div id="attachmentSection">
            <label class="block text-sm font-medium text-slate-700 mb-1">Tài liệu đính kèm (nếu có)</label>
            <input type="file" name="attachment_file" class="w-full rounded-lg border @error('attachment_file') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm bg-white outline-none">
            @error('attachment_file')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hình thức *</label>
                <select name="type" id="typeSelect" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                    <option value="essay" @selected(old('type', 'essay') === 'essay')>Tự luận (nộp file)</option>
                    <option value="quiz" @selected(old('type') === 'quiz')>Quiz (trắc nghiệm)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hạn nộp *</label>
                <input type="datetime-local" name="due_date" value="{{ old('due_date') }}" required
                       class="w-full rounded-lg border @error('due_date') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                @error('due_date')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Thang điểm *</label>
                <input type="number" name="max_score" value="{{ old('max_score', 10) }}" min="1" max="100" step="0.5" required
                       class="w-full rounded-lg border @error('max_score') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                @error('max_score')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả / Đề bài</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('description') }}</textarea>
        </div>

        {{-- Khu vực câu hỏi Quiz --}}
        <div id="quizSection" class="hidden rounded-xl border border-purple-200 bg-purple-50/40 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-purple-800">Bộ câu hỏi trắc nghiệm</h3>
                <div class="flex gap-2">
                    <button type="button" onclick="openAiModal()" class="flex items-center gap-1 rounded-lg bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-200">✨ AI Sinh Câu Hỏi</button>
                    <button type="button" onclick="addQuestion()" class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700">+ Thêm câu hỏi</button>
                </div>
            </div>
            <div id="questionList" class="space-y-4">
                @if(old('questions'))
                    @foreach(old('questions') as $index => $q)
                        <div class="question-item rounded-xl border border-purple-200 bg-white p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-bold text-slate-700">Câu <span class="q-number">{{ $index + 1 }}</span></p>
                                <button type="button" onclick="this.closest('.question-item').remove(); renumber()" class="text-xs font-medium text-rose-600 hover:underline">Xóa câu này</button>
                            </div>
                            <textarea name="questions[{{ $index }}][content]" rows="2" required placeholder="Nội dung câu hỏi..."
                                      class="w-full rounded-lg border @error('questions.'.$index.'.content') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm focus:border-purple-500 outline-none">{{ $q['content'] ?? '' }}</textarea>
                            @error('questions.'.$index.'.content')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <div>
                                    <input name="questions[{{ $index }}][option_a]" type="text" value="{{ $q['option_a'] ?? '' }}" required placeholder="Đáp án A" class="w-full rounded-lg border @error('questions.'.$index.'.option_a') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm outline-none focus:border-purple-500">
                                    @error('questions.'.$index.'.option_a') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <input name="questions[{{ $index }}][option_b]" type="text" value="{{ $q['option_b'] ?? '' }}" required placeholder="Đáp án B" class="w-full rounded-lg border @error('questions.'.$index.'.option_b') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm outline-none focus:border-purple-500">
                                    @error('questions.'.$index.'.option_b') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <input name="questions[{{ $index }}][option_c]" type="text" value="{{ $q['option_c'] ?? '' }}" required placeholder="Đáp án C" class="w-full rounded-lg border @error('questions.'.$index.'.option_c') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm outline-none focus:border-purple-500">
                                    @error('questions.'.$index.'.option_c') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <input name="questions[{{ $index }}][option_d]" type="text" value="{{ $q['option_d'] ?? '' }}" required placeholder="Đáp án D" class="w-full rounded-lg border @error('questions.'.$index.'.option_d') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm outline-none focus:border-purple-500">
                                    @error('questions.'.$index.'.option_d') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2 text-sm">
                                <label class="font-medium text-slate-600">Đáp án đúng:</label>
                                <select name="questions[{{ $index }}][correct_answer]" required class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm bg-white outline-none">
                                    <option value="A" @selected(($q['correct_answer'] ?? '') === 'A')>A</option>
                                    <option value="B" @selected(($q['correct_answer'] ?? '') === 'B')>B</option>
                                    <option value="C" @selected(($q['correct_answer'] ?? '') === 'C')>C</option>
                                    <option value="D" @selected(($q['correct_answer'] ?? '') === 'D')>D</option>
                                </select>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Giao bài tập</button>
            <a href="{{ route('teacher.classes.show', $class) }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>

<template id="questionTemplate">
    <div class="question-item rounded-xl border border-purple-200 bg-white p-4">
        <div class="mb-2 flex items-center justify-between">
            <p class="text-sm font-bold text-slate-700">Câu <span class="q-number"></span></p>
            <button type="button" onclick="this.closest('.question-item').remove(); renumber()" class="text-xs font-medium text-rose-600 hover:underline">Xóa câu này</button>
        </div>
        <textarea data-name="content" rows="2" required placeholder="Nội dung câu hỏi..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 outline-none"></textarea>
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            <input data-name="option_a" type="text" required placeholder="Đáp án A" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-purple-500">
            <input data-name="option_b" type="text" required placeholder="Đáp án B" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-purple-500">
            <input data-name="option_c" type="text" required placeholder="Đáp án C" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-purple-500">
            <input data-name="option_d" type="text" required placeholder="Đáp án D" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-purple-500">
        </div>
        <div class="mt-2 flex items-center gap-2 text-sm">
            <label class="font-medium text-slate-600">Đáp án đúng:</label>
            <select data-name="correct_answer" required class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm bg-white outline-none">
                <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
            </select>
        </div>
    </div>
</template>

<script>
    const typeSelect = document.getElementById('typeSelect');
    const quizSection = document.getElementById('quizSection');
    const questionList = document.getElementById('questionList');

    function toggleQuiz() {
        const isQuiz = typeSelect.value === 'quiz';
        quizSection.classList.toggle('hidden', !isQuiz);
        if (isQuiz && questionList.children.length === 0) addQuestion();
        if (!isQuiz) questionList.innerHTML = '';
    }

    function addQuestion() {
        const tpl = document.getElementById('questionTemplate');
        questionList.appendChild(tpl.content.cloneNode(true));
        renumber();
    }

    function renumber() {
        [...questionList.querySelectorAll('.question-item')].forEach((item, i) => {
            item.querySelector('.q-number').textContent = i + 1;
            item.querySelectorAll('[data-name]').forEach(el => {
                el.name = `questions[${i}][${el.dataset.name}]`;
            });
        });
    }

    typeSelect.addEventListener('change', toggleQuiz);
    toggleQuiz();

    // Bắt sự kiện submit form chính để cảnh báo nếu thiếu Tiêu đề hoặc Hạn nộp
    document.getElementById('mainForm').addEventListener('submit', function(e) {
        const title = document.querySelector('input[name="title"]').value.trim();
        const dueDate = document.querySelector('input[name="due_date"]').value.trim();
        
        let hasError = false;
        let errorMessage = '';

        if (!title) {
            hasError = true;
            errorMessage = 'Vui lòng điền Tiêu đề của bài tập!';
            document.querySelector('input[name="title"]').focus();
        } else if (!dueDate) {
            hasError = true;
            errorMessage = 'Vui lòng chọn Hạn nộp của bài tập!';
            document.querySelector('input[name="due_date"]').focus();
        }

        if (hasError) {
            e.preventDefault();
            showToast(errorMessage, 'error');
            return false;
        }
        
        // Cảnh báo nếu chưa có câu hỏi khi là Quiz
        if (typeSelect.value === 'quiz') {
            const questions = document.querySelectorAll('.question-item');
            if (questions.length === 0) {
                e.preventDefault();
                showToast('Vui lòng thêm ít nhất 1 câu hỏi trắc nghiệm!', 'error');
                return false;
            }
        }
    });
</script>
@include('components.toast')

<!-- Modal AI Sinh Câu Hỏi -->
<div id="aiModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeAiModal()"></div>
    
    <!-- Modal Content -->
    <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <!-- Close button -->
        <button onclick="closeAiModal()" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="mb-6 flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-2xl text-amber-500">
                ✨
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">Trợ lý AI Sinh Câu Hỏi</h3>
                <p class="text-sm text-slate-500">Tạo câu hỏi trắc nghiệm tự động bằng Gemini AI</p>
            </div>
        </div>

        <form id="aiForm" onsubmit="generateQuestionsWithAi(event)" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Chủ đề câu hỏi</label>
                <input type="text" id="aiTopic" placeholder="Ví dụ: Cú pháp PHP, Mảng trong Javascript, Lịch sử..." required class="w-full rounded-xl border border-indigo-400 px-4 py-2.5 text-sm outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Số lượng câu hỏi</label>
                <input type="number" id="aiCount" value="5" min="1" max="50" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Tài liệu tham khảo (Tùy chọn)</label>
                <input type="file" id="aiFile" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-600 outline-none file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                <p class="mt-2 text-xs text-slate-400">Hỗ trợ file .txt, .pdf, .docx. AI sẽ đọc tài liệu để sinh câu hỏi sát nội dung.</p>
            </div>

            <div class="mt-8 flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeAiModal()" class="rounded-xl bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Hủy</button>
                <button type="submit" class="flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-violet-700 transition-colors">✨ Sinh câu hỏi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAiModal() {
        document.getElementById('aiModal').classList.remove('hidden');
    }

    function closeAiModal() {
        document.getElementById('aiModal').classList.add('hidden');
        document.getElementById('aiForm').reset();
    }

    async function generateQuestionsWithAi(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const originalBtnText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Đang sinh...`;
        
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('topic', document.getElementById('aiTopic').value);
        formData.append('count', document.getElementById('aiCount').value);
        
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
                // Xóa câu hỏi mặc định nếu nó đang trống
                const currentItems = document.querySelectorAll('.question-item');
                if (currentItems.length === 1) {
                    const firstItem = currentItems[0];
                    const content = firstItem.querySelector('textarea[name$="[content]"]').value.trim();
                    const optA = firstItem.querySelector('input[name$="[option_a]"]').value.trim();
                    const optB = firstItem.querySelector('input[name$="[option_b]"]').value.trim();
                    if (!content && !optA && !optB) {
                        firstItem.remove();
                    }
                }

                // Thêm các câu hỏi vào giao diện
                result.data.forEach(q => {
                    addQuestion();
                    const items = document.querySelectorAll('.question-item');
                    const newItem = items[items.length - 1];
                    
                    newItem.querySelector('textarea[name$="[content]"]').value = q.content;
                    newItem.querySelector('input[name$="[option_a]"]').value = q.option_a;
                    newItem.querySelector('input[name$="[option_b]"]').value = q.option_b;
                    newItem.querySelector('input[name$="[option_c]"]').value = q.option_c;
                    newItem.querySelector('input[name$="[option_d]"]').value = q.option_d;
                    newItem.querySelector(`select[name$="[correct_answer]"]`).value = q.correct_answer.toUpperCase();
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
</script>

@endsection
