@extends('layouts.app')

@section('title', 'Giao bài tập — '.$class->class_name)

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-bold text-slate-800">Giao bài tập cho lớp {{ $class->class_name }}</h2>

    <form method="POST" action="{{ route('teacher.assignments.store', $class) }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tiêu đề *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
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
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Thang điểm *</label>
                <input type="number" name="max_score" value="{{ old('max_score', 10) }}" min="1" max="100" step="0.5" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
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
                <button type="button" onclick="addQuestion()" class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700">+ Thêm câu hỏi</button>
            </div>
            <div id="questionList" class="space-y-4"></div>
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
</script>
@endsection
