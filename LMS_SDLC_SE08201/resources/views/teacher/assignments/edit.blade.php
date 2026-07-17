@extends('layouts.app')

@section('title', 'Sửa bài tập — '.$assignment->title)

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-1 text-base font-bold text-slate-800">Sửa bài tập</h2>
    <p class="mb-5 text-xs text-slate-400">Lớp {{ $assignment->classRoom->class_name }} · {{ $assignment->classRoom->subject->subject_name ?? '' }}</p>

    @if ($locked)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            ⚠️ Bài tập này đã có sinh viên nộp bài nên <b>không thể đổi hình thức hay chỉnh sửa câu hỏi</b> (để bảo toàn dữ liệu đã chấm). Bạn vẫn có thể sửa tiêu đề, mô tả, hạn nộp và thang điểm.
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.assignments.update', $assignment) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
            <input type="text" name="title" value="{{ old('title', $assignment->title) }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hình thức *</label>
                @if ($locked)
                    <select disabled class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-500">
                        <option>{{ $assignment->typeLabel() }}</option>
                    </select>
                    <input type="hidden" name="type" value="{{ $assignment->type }}">
                @else
                    <select name="type" id="typeSelect" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white outline-none">
                        <option value="essay" @selected(old('type', $assignment->type) === 'essay')>Tự luận (nộp file)</option>
                        <option value="quiz" @selected(old('type', $assignment->type) === 'quiz')>Quiz (trắc nghiệm)</option>
                    </select>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hạn nộp *</label>
                <input type="datetime-local" name="due_date" required
                       value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}"
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

        @if ($locked)
            {{-- Chỉ hiển thị câu hỏi (không sửa được) --}}
            @if ($assignment->isQuiz() && $assignment->questions->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="mb-3 text-sm font-bold text-slate-700">Bộ câu hỏi ({{ $assignment->questions->count() }}) — chỉ xem</h3>
                    <ol class="space-y-2 text-sm text-slate-600">
                        @foreach ($assignment->questions as $q)
                            <li><b>{{ $loop->iteration }}.</b> {{ $q->content }} <span class="text-xs text-emerald-600">(Đáp án đúng: {{ $q->correct_answer }})</span></li>
                        @endforeach
                    </ol>
                </div>
            @endif
        @else
            {{-- Khu vực câu hỏi Quiz (chỉnh sửa được) --}}
            <div id="quizSection" class="hidden rounded-xl border border-purple-200 bg-purple-50/40 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-purple-800">Bộ câu hỏi trắc nghiệm</h3>
                    <button type="button" onclick="addQuestion()" class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700">+ Thêm câu hỏi</button>
                </div>
                <div id="questionList" class="space-y-4"></div>
            </div>
        @endif

        <div class="flex gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lưu thay đổi</button>
            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Hủy</a>
        </div>
    </form>
</div>

@unless ($locked)
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

    // Câu hỏi hiện có (hoặc dữ liệu vừa nhập khi có lỗi validation) để seed lại builder
    const existingQuestions = @json(old('questions', $assignment->questions->map(fn ($q) => [
        'content' => $q->content,
        'option_a' => $q->option_a,
        'option_b' => $q->option_b,
        'option_c' => $q->option_c,
        'option_d' => $q->option_d,
        'correct_answer' => $q->correct_answer,
    ])->values()));

    function toggleQuiz(seed = false) {
        const isQuiz = typeSelect.value === 'quiz';
        quizSection.classList.toggle('hidden', !isQuiz);
        if (isQuiz && questionList.children.length === 0) {
            if (seed && existingQuestions.length) {
                existingQuestions.forEach(addQuestion);
            } else {
                addQuestion();
            }
        }
        if (!isQuiz) questionList.innerHTML = '';
    }

    function addQuestion(data = null) {
        const tpl = document.getElementById('questionTemplate');
        const node = tpl.content.cloneNode(true);
        if (data) {
            node.querySelector('[data-name="content"]').value = data.content ?? '';
            node.querySelector('[data-name="option_a"]').value = data.option_a ?? '';
            node.querySelector('[data-name="option_b"]').value = data.option_b ?? '';
            node.querySelector('[data-name="option_c"]').value = data.option_c ?? '';
            node.querySelector('[data-name="option_d"]').value = data.option_d ?? '';
            node.querySelector('[data-name="correct_answer"]').value = data.correct_answer ?? 'A';
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

    typeSelect.addEventListener('change', () => toggleQuiz(false));
    toggleQuiz(true);
</script>
@endunless
@endsection
