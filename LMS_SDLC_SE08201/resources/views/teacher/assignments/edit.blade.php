@extends('layouts.app')
<!-- ( Đăng thêm ) Tạo giao diện sửa bài tập -->

@section('title', 'Sửa bài tập - ' . $class->class_name)

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
                   class="w-full rounded-lg border @error('title') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
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
                <input type="datetime-local" name="due_date" value="{{ old('due_date', $assignment->due_date->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-lg border @error('due_date') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                @error('due_date')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Thang điểm *</label>
                <input type="number" name="max_score" value="{{ old('max_score', $assignment->max_score) }}" required min="1" max="100" step="0.5"
                       class="w-full rounded-lg border @error('max_score') border-red-500 @else border-slate-300 @enderror px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
                @error('max_score')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả / Đề bài</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">{{ old('description', $assignment->description) }}</textarea>
        </div>

        <!-- ( Đăng sửa ) Thêm trường input cho file đính kèm -->
        @if(!$assignment->isQuiz())
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tài liệu đính kèm (nếu muốn thay đổi)</label>
            @if($assignment->attachment_path)
                <p class="mb-2 text-sm text-indigo-600">Đã có file: <a href="{{ route('teacher.assignments.download-attachment', $assignment) }}" class="underline">Tải xuống</a></p>
            @endif
            <input type="file" name="attachment_file" class="w-full rounded-lg border @error('attachment_file') border-red-500 @else border-slate-300 @enderror px-3 py-2 text-sm bg-white outline-none">
            @error('attachment_file')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
        @endif

        @if($assignment->isQuiz())
        {{-- Khu vực câu hỏi Quiz --}}
        <div id="quizSection" class="mt-4 rounded-xl border border-purple-200 bg-purple-50/40 p-4">
            <h3 class="mb-4 font-bold text-slate-800">Bộ câu hỏi trắc nghiệm</h3>

            <!-- ( Đăng sửa ) Checkbox cấu hình hiển thị đáp án được đưa vào trong Quiz và có UI cảnh báo -->
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="show_answers" value="1" @checked(old('show_answers', $assignment->show_answers)) class="mt-1 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-bold text-amber-900">🔑 Cho phép học sinh xem đáp án đúng sau khi nộp bài</span>
                        <p class="mt-1 text-xs text-amber-700">⚠️ <span class="font-bold">Chú ý bảo mật:</span> Nếu bật tính năng này, học sinh sẽ thấy ngay đáp án đúng của tất cả các câu hỏi ngay sau khi nhấn nộp bài. Điều này có thể dẫn đến việc học sinh làm bài xong trước chia sẻ đáp án cho các học sinh chưa làm bài. Hãy cân nhắc tắt tính năng này nếu đây là bài thi chính thức.</p>
                    </div>
                </label>
            </div>

            <div class="mb-3 flex items-center justify-end gap-2">
                <button type="button" onclick="openAiModal()" class="flex items-center gap-1.5 rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-600 hover:bg-amber-200 transition-colors">✨ Tạo bằng AI</button>
                <button type="button" onclick="addQuestion()" class="flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition-colors">+ Thêm câu hỏi</button>
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
                    @endphp
                    <div class="question-item relative rounded-lg border border-slate-200 bg-white p-4 pt-8 shadow-sm">
                        <button type="button" onclick="this.closest('.question-item').remove(); updateQuestionIndexes();" 
                                class="absolute right-3 top-3 text-slate-400 hover:text-red-500" title="Xóa câu hỏi">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        
                        <div class="mb-3 flex items-center gap-3">
                            <span class="question-index font-bold text-slate-700">Câu {{ $index + 1 }}</span>
                            <div class="flex-1">
                                <input type="text" name="questions[{{ $index }}][content]" value="{{ $content }}" required placeholder="Nhập câu hỏi..." class="w-full rounded-lg border @error('questions.'.$index.'.content') border-red-500 @else border-slate-300 @enderror px-3 py-1.5 text-sm outline-none focus:border-purple-500">
                                @error('questions.'.$index.'.content')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="grid gap-2 sm:grid-cols-2 ml-10">
                            @foreach(['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD] as $char => $val)
                            <div class="flex items-start gap-2">
                                <input type="radio" name="questions[{{ $index }}][correct_answer]" value="{{ $char }}" required @checked($correct == $char) class="mt-1 h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-medium text-slate-600 mt-1">{{ $char }}.</span>
                                <div class="flex-1">
                                    <input type="text" name="questions[{{ $index }}][option_{{ strtolower($char) }}]" value="{{ $val }}" required placeholder="Đáp án {{ $char }}" class="w-full rounded-md border @error('questions.'.$index.'.option_'.strtolower($char)) border-red-500 @else border-slate-200 @enderror px-2 py-1 text-sm outline-none focus:border-purple-500">
                                    @error('questions.'.$index.'.option_'.strtolower($char)) 
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p> 
                                    @enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex items-center gap-3 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Cập nhật bài tập</button>
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>

@if($assignment->isQuiz())
<script>
    let qIndex = {{ count(old('questions', $assignment->questions)) }};

    function addQuestion() {
        const tpl = `
            <div class="question-item relative rounded-lg border border-slate-200 bg-white p-4 pt-8 shadow-sm">
                <button type="button" onclick="this.closest('.question-item').remove(); updateQuestionIndexes();" 
                        class="absolute right-3 top-3 text-slate-400 hover:text-red-500" title="Xóa câu hỏi">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                
                <div class="mb-3 flex items-center gap-3">
                    <span class="question-index font-bold text-slate-700">Câu ${qIndex + 1}</span>
                    <input type="text" name="questions[${qIndex}][content]" required placeholder="Nhập câu hỏi..." class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-purple-500">
                </div>
                
                <div class="grid gap-2 sm:grid-cols-2 ml-10">
                    ${['A','B','C','D'].map(char => `
                        <div class="flex items-center gap-2">
                            <input type="radio" name="questions[${qIndex}][correct_answer]" value="${char}" required class="h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-medium text-slate-600">${char}.</span>
                            <input type="text" name="questions[${qIndex}][option_${char.toLowerCase()}]" required placeholder="Đáp án ${char}" class="flex-1 rounded-md border border-slate-200 px-2 py-1 text-sm outline-none focus:border-purple-500">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        document.getElementById('questionList').insertAdjacentHTML('beforeend', tpl);
        qIndex++;
        updateQuestionIndexes();
    }

    function updateQuestionIndexes() {
        document.querySelectorAll('.question-item').forEach((item, index) => {
            item.querySelector('.question-index').textContent = 'Câu ' + (index + 1);
            
            // Cập nhật lại thuộc tính name cho chuẩn array index
            item.querySelector('input[name$="[content]"]').name = `questions[${index}][content]`;
            ['A','B','C','D'].forEach(char => {
                const optKey = `option_${char.toLowerCase()}`;
                item.querySelector(`input[name$="[${optKey}]"]`).name = `questions[${index}][${optKey}]`;
            });
            item.querySelectorAll('input[type="radio"]').forEach(r => {
                r.name = `questions[${index}][correct_answer]`;
            });
        });
        qIndex = document.querySelectorAll('.question-item').length;
    }

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
                // Thêm các câu hỏi vào giao diện
                result.data.forEach(q => {
                    addQuestion();
                    const items = document.querySelectorAll('.question-item');
                    const newItem = items[items.length - 1];
                    
                    newItem.querySelector('input[name$="[content]"]').value = q.content;
                    newItem.querySelector('input[name$="[option_a]"]').value = q.option_a;
                    newItem.querySelector('input[name$="[option_b]"]').value = q.option_b;
                    newItem.querySelector('input[name$="[option_c]"]').value = q.option_c;
                    newItem.querySelector('input[name$="[option_d]"]').value = q.option_d;
                    newItem.querySelector(`input[type="radio"][value="${q.correct_answer.toUpperCase()}"]`).checked = true;
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
@endif

@endsection
