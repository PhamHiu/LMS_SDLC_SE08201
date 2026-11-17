@extends('layouts.app')

@section('title', 'Làm bài: '.$assignment->title)

@section('content')
<div class="w-full">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 shrink-0">
                    <svg class="w-5 h-5 text-[#1e4f8f] filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-xl font-semibold !text-slate-900 tracking-wide">{{ $assignment->title }}</h2>
                    <p class="text-xs !text-slate-600 mt-0.5">{{ $assignment->questions->count() }} câu hỏi · Thang điểm <span class="font-bold text-[#1e4f8f]">{{ $assignment->max_score }}</span> · Hạn nộp: <span class="text-slate-900 font-semibold">{{ $assignment->due_date->format('H:i d/m/Y') }}</span></p>
                </div>
            </div>
            <span id="progress" class="rounded-full bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 px-4 py-1.5 text-sm font-bold text-[#1e4f8f]">0/{{ $assignment->questions->count() }}</span>
        </div>

        {{-- Cho sinh viên biết trước: sau khi nộp có được xem đáp án không --}}
        @if ($assignment->allowsAnswerReview())
            <div class="mb-5 flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm !text-emerald-700">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200 shrink-0">
                    <svg class="w-4 h-4 text-emerald-700 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </span>
                <span>Sau khi nộp, bạn sẽ được xem lại đáp án đúng của từng câu.</span>
            </div>
        @else
            <div class="mb-5 flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm !text-slate-600">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-50 border border-slate-200 shrink-0">
                    <svg class="w-4 h-4 text-slate-600 filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <span>Giáo viên không cho phép xem đáp án — sau khi nộp bạn chỉ thấy điểm và trạng thái đã nộp.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('student.assignments.quiz.submit', $assignment) }}"
              onsubmit="return confirm('Nộp bài? Bạn chỉ được làm 1 lần duy nhất và không thể sửa lại.')">
            @csrf
            <div class="space-y-6">
                @foreach ($assignment->questions as $i => $q)
                    <div class="rounded-md border border-slate-200 bg-white/[0.02] p-5">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-[#1e4f8f]/10 border border-[#1e4f8f]/30 px-2.5 py-0.5 text-xs font-semibold text-[#1e4f8f]">{{ $q->typeLabel() }}</span>
                        </div>
                        @if ($q->question_type === 'code_reading')
                            <p class="font-bold !text-slate-900 text-base">Câu {{ $i + 1 }}:</p>
                            <pre class="mt-2 overflow-x-auto rounded-md border border-slate-200 bg-slate-900 px-4 py-3 text-sm text-slate-100 whitespace-pre-wrap font-mono">{{ $q->content }}</pre>
                        @else
                            <p class="font-bold !text-slate-900 text-base">Câu {{ $i + 1 }}: <span class="!text-slate-800 font-normal">{{ $q->content }}</span></p>
                        @endif

                        @if ($q->isFillBlank())
                            <div class="mt-4">
                                <input type="text" name="answer_text[{{ $q->question_id }}]"
                                       class="quiz-text w-full rounded-md border border-slate-300 px-4 py-3 text-sm !text-slate-800 outline-none focus:border-[#1e4f8f]"
                                       placeholder="Nhập câu trả lời của bạn..." autocomplete="off" oninput="updateProgress()" required>
                            </div>
                        @else
                            <div class="mt-4 grid gap-2.5">
                                @foreach ($q->options() as $key => $option)
                                    <label class="flex cursor-pointer items-center gap-3.5 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm !text-slate-700 transition-all duration-200 hover:border-[#1e4f8f] hover:bg-slate-100 has-[:checked]:border-[#1e4f8f] has-[:checked]:bg-[#1e4f8f]/10 has-[:checked]:!text-[#1e4f8f]">
                                        <input type="radio" name="answers[{{ $q->question_id }}]" value="{{ $key }}"
                                               class="quiz-radio text-[#1e4f8f] focus:ring-[#1e4f8f] border-slate-200 bg-slate-50 w-4 h-4" onchange="updateProgress()" required>
                                        <span><b class="text-slate-900">{{ $key }}.</b> {{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <button class="mt-6 w-full flex items-center justify-center gap-2 rounded-md bg-[#1e4f8f] border border-[#1e4f8f]/30 py-3.5 text-sm font-bold text-white hover:border-[#1e4f8f] transition duration-200 cursor-pointer">
                <svg class="w-4 h-4 text-white filter" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span>NỘP BÀI & XEM ĐIỂM NGAY</span>
            </button>
        </form>
    </div>
</div>

<script>
    function updateProgress() {
        const total = {{ $assignment->questions->count() }};
        const radios = new Set([...document.querySelectorAll('.quiz-radio:checked')].map(r => r.name));
        const texts = new Set([...document.querySelectorAll('.quiz-text')].filter(t => t.value.trim() !== '').map(t => t.name));
        document.getElementById('progress').textContent = `${radios.size + texts.size}/${total}`;
    }
</script>
@endsection
