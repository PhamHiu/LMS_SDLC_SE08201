@extends('layouts.app')

@section('title', 'Sửa bài tập — '.$assignment->title)

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-bold text-slate-800">Sửa bài tập: {{ $assignment->title }}</h2>

    <form method="POST" action="{{ route('teacher.assignments.update', $assignment) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
            <input type="text" name="title" value="{{ old('title', $assignment->title) }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Đính kèm file (PDF, DOCX, ZIP, RAR - Tối đa 10MB)</label>
            @if($assignment->attachment_path)
                <p class="text-sm text-slate-500 mb-2">Đã đính kèm: <a href="{{ asset(Storage::url($assignment->attachment_path)) }}" class="text-indigo-600 underline" target="_blank">Xem/Tải file hiện tại</a></p>
            @endif
            <input type="file" name="attachment" accept=".pdf,.docx,.doc,.zip,.rar"
                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-lg">
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hình thức *</label>
                <select name="type" id="typeSelect" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-slate-100 outline-none" style="pointer-events: none;" readonly>
                    <option value="essay" @selected($assignment->type === 'essay')>Tự luận (nộp file)</option>
                    <option value="quiz" @selected($assignment->type === 'quiz')>Quiz (trắc nghiệm)</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">Không thể đổi hình thức bài tập sau khi tạo.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hạn nộp *</label>
                <input type="datetime-local" name="due_date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Thang điểm *</label>
                <input type="number" name="max_score" value="{{ old('max_score', $assignment->max_score) }}" min="1" max="100" step="0.5" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả / Đề bài</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('description', $assignment->description) }}</textarea>
        </div>

        {{-- Khu vực câu hỏi Quiz --}}
        <div id="quizSection" class="hidden rounded-xl border border-purple-200 bg-purple-50/40 p-4">
            <div class="mb-3 flex flex-col gap-3">
                <h3 class="text-sm font-bold text-purple-800">Bộ câu hỏi trắc nghiệm</h3>
                
                <div class="rounded-xl border border-amber-100 bg-amber-50/40 p-4 w-full">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="show_answer_after_submit" id="show_answer_after_submit" value="1" 
                               @checked(old('show_answer_after_submit', $assignment->show_answer_after_submit))
                               class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mt-0.5 cursor-pointer">
                        <div>
                            <label for="show_answer_after_submit" class="text-sm font-bold text-slate-800 cursor-pointer flex items-center gap-1.5 font-sans">
                                🔑 Cho phép học sinh xem đáp án đúng sau khi nộp bài
                            </label>
                            <p class="mt-1 text-xs text-amber-700 font-medium leading-relaxed font-sans">
                                ⚠️ <strong>Chú ý bảo mật:</strong> Nếu bật tính năng này, học sinh sẽ thấy ngay đáp án đúng của tất cả các câu hỏi ngay sau khi nhấn nộp bài. Điều này có thể dẫn đến việc học sinh làm bài xong trước chia sẻ đáp án cho các học sinh chưa làm bài. Hãy cân nhắc tắt tính năng này nếu đây là bài thi chính thức.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end">
                    <button type="button" onclick="openAiModal()" class="rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:from-amber-600 hover:to-orange-600 transition-all">✨ Tạo bằng AI</button>
                    <button type="button" onclick="addQuestion()" class="rounded-lg bg-purple-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-purple-700 transition-all">+ Thêm câu hỏi</button>
                </div>
            </div>
            <div id="questionList" class="space-y-4"></div>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Cập nhật</button>
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
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

<!-- AI Quiz Modal Container -->
<div id="aiQuizModal" class="fixed inset-0 z-50 hidden overflow-y-auto select-none bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 transform transition-all duration-300">
        <!-- Close button -->
        <button type="button" onclick="closeAiModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <div class="flex items-center gap-2.5 mb-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 text-lg">
                ✨
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800 font-sans">Trợ lý AI Sinh Câu Hỏi</h3>
                <p class="text-xs text-slate-500 font-sans">Tạo câu hỏi trắc nghiệm tự động bằng Gemini AI</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1 font-sans">Chủ đề câu hỏi</label>
                <input type="text" id="ai-topic-input" placeholder="Ví dụ: Cú pháp PHP, Mảng trong Javascript, Lịch sử..." 
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-slate-700 font-sans">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1 font-sans">Số lượng câu hỏi</label>
                <input type="number" id="ai-quantity-select" min="1" max="50" value="5" 
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm bg-white outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-slate-700 font-sans"
                       placeholder="Nhập số lượng câu hỏi cần tạo (tối đa 50)...">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1 font-sans">Tài liệu tham khảo (Tùy chọn)</label>
                <input type="file" id="ai-document-input" accept=".txt,.pdf,.docx,.doc"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-1.5 text-xs outline-none focus:border-indigo-500 bg-white text-slate-700 font-sans file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-[10px] text-slate-400 mt-1 font-sans">Hỗ trợ file .txt, .pdf, .docx. AI sẽ đọc tài liệu để sinh câu hỏi sát nội dung.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2.5">
            <button type="button" onclick="closeAiModal()" 
                    class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition-colors font-sans">
                Hủy
            </button>
            <button type="button" id="btn-ai-generate-submit" onclick="submitAiGeneration()" 
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-md transition-colors flex items-center gap-1.5 font-sans">
                <span>✨</span> Sinh câu hỏi
            </button>
        </div>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('typeSelect');
    const quizSection = document.getElementById('quizSection');
    const questionList = document.getElementById('questionList');

    function toggleQuiz() {
        const isQuiz = typeSelect.value === 'quiz';
        quizSection.classList.toggle('hidden', !isQuiz);
    }

    function addQuestion(q = null) {
        const tpl = document.getElementById('questionTemplate');
        const node = tpl.content.cloneNode(true);
        if (q) {
            node.querySelector('[data-name="content"]').value = q.content;
            node.querySelector('[data-name="option_a"]').value = q.option_a;
            node.querySelector('[data-name="option_b"]').value = q.option_b;
            node.querySelector('[data-name="option_c"]').value = q.option_c;
            node.querySelector('[data-name="option_d"]').value = q.option_d;
            node.querySelector('[data-name="correct_answer"]').value = q.correct_answer;
        }
        questionList.appendChild(node);
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

    // Initialize existing questions if quiz
    @if($assignment->isQuiz())
        const oldQuestions = @json($assignment->questions);
        if(oldQuestions.length > 0) {
            oldQuestions.forEach(q => addQuestion(q));
        } else {
            addQuestion();
        }
    @endif

    typeSelect.addEventListener('change', toggleQuiz);
    toggleQuiz();

    // Toast notification helper
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-2.5';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        const isSuccess = type === 'success';
        const borderClass = isSuccess ? 'border-emerald-100 bg-white' : 'border-rose-100 bg-white';
        const icon = isSuccess ? '✅' : '❌';
        const title = isSuccess ? 'Thành công' : 'Thất bại';

        toast.className = `min-w-[280px] rounded-xl border p-4 shadow-2xl flex items-center gap-3 transform translate-x-12 opacity-0 transition-all duration-300 ${borderClass}`;
        toast.innerHTML = `
            <span class="text-lg font-sans">${icon}</span>
            <div class="flex-1">
                <p class="text-xs font-bold text-slate-800 font-sans">${title}</p>
                <p class="text-[11px] text-slate-500 font-medium mt-0.5 font-sans">${message}</p>
            </div>
        `;

        container.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-12', 'opacity-0');
        }, 10);

        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-12', 'opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // AI Generation Logic
    function openAiModal() {
        const modal = document.getElementById('aiQuizModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Clear fields on open
            document.getElementById('ai-topic-input').value = '';
            document.getElementById('ai-quantity-select').value = '5';
            const docInput = document.getElementById('ai-document-input');
            if (docInput) docInput.value = '';
            document.getElementById('ai-topic-input').focus();
        }
    }

    function closeAiModal() {
        const modal = document.getElementById('aiQuizModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function submitAiGeneration() {
        const topic = document.getElementById('ai-topic-input').value.trim();
        if (!topic) {
            showToast('Vui lòng nhập chủ đề câu hỏi.', 'error');
            return;
        }

        const quantityInput = document.getElementById('ai-quantity-select');
        const quantity = parseInt(quantityInput.value, 10);
        if (isNaN(quantity) || quantity < 1 || quantity > 50) {
            showToast('Vui lòng nhập số lượng câu hỏi từ 1 đến 50.', 'error');
            return;
        }

        const btn = document.getElementById('btn-ai-generate-submit');
        const oldText = btn.innerHTML;
        
        btn.innerHTML = '⏳ Đang tạo...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('topic', topic);
        formData.append('quantity', quantity);
        
        const fileInput = document.getElementById('ai-document-input');
        if (fileInput && fileInput.files.length > 0) {
            formData.append('reference_document', fileInput.files[0]);
        }

        fetch('{{ route('teacher.assignments.generate-quiz') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = oldText;
            btn.disabled = false;
            if (data.success && data.questions) {
                // Clear old
                questionList.innerHTML = '';
                data.questions.forEach(q => {
                    const tpl = document.getElementById('questionTemplate').content.cloneNode(true);
                    tpl.querySelector('[data-name="content"]').value = q.content;
                    tpl.querySelector('[data-name="option_a"]').value = q.option_a;
                    tpl.querySelector('[data-name="option_b"]').value = q.option_b;
                    tpl.querySelector('[data-name="option_c"]').value = q.option_c;
                    tpl.querySelector('[data-name="option_d"]').value = q.option_d;
                    tpl.querySelector('[data-name="correct_answer"]').value = q.correct_answer;
                    questionList.appendChild(tpl);
                });
                renumber();
                closeAiModal();
                showToast("Đã tự động tạo các câu hỏi trắc nghiệm bằng AI thành công!");
            } else {
                showToast("Lỗi: " + data.message, "error");
            }
        })
        .catch(err => {
            btn.innerHTML = oldText;
            btn.disabled = false;
            showToast("Có lỗi xảy ra khi kết nối đến AI.", "error");
        });
    }
</script>
@endsection
