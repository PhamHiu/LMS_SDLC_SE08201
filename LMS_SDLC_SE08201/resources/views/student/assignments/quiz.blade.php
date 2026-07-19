@extends('layouts.app')

@section('title', 'Làm bài: '.$assignment->title)

@section('content')
<div class="w-full">
    <div class="rounded-2xl border border-purple-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800">🧩 {{ $assignment->title }}</h2>
                <p class="text-sm text-slate-500">{{ $assignment->questions->count() }} câu hỏi · Thang điểm {{ $assignment->max_score }} · Hạn: {{ $assignment->due_date->format('H:i d/m/Y') }}</p>
            </div>
            <span id="progress" class="rounded-full bg-purple-100 px-3 py-1.5 text-sm font-bold text-purple-700">0/{{ $assignment->questions->count() }}</span>
        </div>

        {{-- Cho sinh viên biết trước: sau khi nộp có được xem đáp án không --}}
        <div class="mb-5 rounded-xl border px-4 py-3 text-sm {{ $assignment->allowsAnswerReview() ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
            {{ $assignment->allowsAnswerReview()
                ? '👁️ Sau khi nộp, bạn sẽ được xem lại đáp án đúng của từng câu.'
                : '🔒 Giáo viên không cho phép xem đáp án — sau khi nộp bạn chỉ thấy điểm và trạng thái đã nộp.' }}
        </div>

        <form method="POST" action="{{ route('student.assignments.quiz.submit', $assignment) }}"
              onsubmit="return confirm('Nộp bài? Bạn chỉ được làm 1 lần duy nhất và không thể sửa lại.')">
            @csrf
            <div class="space-y-6">
                @foreach ($assignment->questions as $i => $q)
                    <div class="rounded-xl border border-slate-200 p-5">
                        <p class="font-semibold text-slate-800">Câu {{ $i + 1 }}: {{ $q->content }}</p>
                        <div class="mt-3 grid gap-2">
                            @foreach ($q->options() as $key => $option)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm transition hover:bg-purple-50 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50 has-[:checked]:font-semibold">
                                    <input type="radio" name="answers[{{ $q->question_id }}]" value="{{ $key }}"
                                           class="quiz-radio text-purple-600 focus:ring-purple-500" onchange="updateProgress()" required>
                                    <span><b>{{ $key }}.</b> {{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <button class="mt-6 w-full rounded-xl bg-purple-600 py-3.5 text-sm font-bold text-white hover:bg-purple-700">
                📤 Nộp bài & Xem điểm ngay
            </button>
        </form>
    </div>
</div>

<script>
    function updateProgress() {
        const total = {{ $assignment->questions->count() }};
        const answered = new Set([...document.querySelectorAll('.quiz-radio:checked')].map(r => r.name)).size;
        document.getElementById('progress').textContent = `${answered}/${total}`;
    }
</script>
@endsection
