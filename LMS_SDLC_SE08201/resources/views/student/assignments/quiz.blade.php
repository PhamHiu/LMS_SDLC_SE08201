@extends('layouts.app')

@section('title', 'Làm bài: '.$assignment->title)

@section('content')
<div class="w-full">
    <div class="rounded-2xl border border-white/10 bg-[#030407]/90 p-6 shadow-2xl backdrop-blur-xl">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[#1558C9]/20 border border-[#1558C9]/40 shadow-[0_0_10px_rgba(21,88,201,0.4)] shrink-0">
                    <svg class="w-5 h-5 text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold !text-white tracking-wide">{{ $assignment->title }}</h2>
                    <p class="text-xs !text-slate-300 mt-0.5">{{ $assignment->questions->count() }} câu hỏi · Thang điểm <span class="font-bold text-[#D4FF24]">{{ $assignment->max_score }}</span> · Hạn nộp: <span class="text-white font-semibold">{{ $assignment->due_date->format('H:i d/m/Y') }}</span></p>
                </div>
            </div>
            <span id="progress" class="rounded-full bg-[#1558C9]/20 border border-[#1558C9]/50 px-4 py-1.5 text-sm font-black text-[#D4FF24] shadow-[0_0_12px_rgba(21,88,201,0.3)]">0/{{ $assignment->questions->count() }}</span>
        </div>

        {{-- Cho sinh viên biết trước: sau khi nộp có được xem đáp án không --}}
        @if ($assignment->allowsAnswerReview())
            <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-500/40 bg-emerald-950/30 px-4 py-3 text-sm !text-emerald-300 shadow-[0_0_15px_rgba(16,185,129,0.15)]">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-500/20 border border-emerald-400/40 shrink-0">
                    <svg class="w-4 h-4 text-emerald-300 filter drop-shadow-[0_0_6px_rgba(52,211,153,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </span>
                <span>Sau khi nộp, bạn sẽ được xem lại đáp án đúng của từng câu.</span>
            </div>
        @else
            <div class="mb-5 flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm !text-slate-300">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white/10 border border-white/20 shrink-0">
                    <svg class="w-4 h-4 text-slate-300 filter drop-shadow-[0_0_4px_rgba(255,255,255,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-5">
                        <p class="font-bold !text-white text-base">Câu {{ $i + 1 }}: <span class="!text-slate-100 font-normal">{{ $q->content }}</span></p>
                        <div class="mt-4 grid gap-2.5">
                            @foreach ($q->options() as $key => $option)
                                <label class="flex cursor-pointer items-center gap-3.5 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm !text-slate-200 transition-all duration-200 hover:border-[#1558C9] hover:bg-[#1558C9]/10 has-[:checked]:border-[#D4FF24] has-[:checked]:bg-[#1558C9]/20 has-[:checked]:!text-[#D4FF24] has-[:checked]:shadow-[0_0_12px_rgba(212,255,36,0.25)]">
                                    <input type="radio" name="answers[{{ $q->question_id }}]" value="{{ $key }}"
                                           class="quiz-radio text-[#1558C9] focus:ring-[#D4FF24] border-white/20 bg-slate-900 w-4 h-4" onchange="updateProgress()" required>
                                    <span><b class="text-white">{{ $key }}.</b> {{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <button class="mt-6 w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#1558C9] to-blue-600 border border-[#D4FF24]/60 py-3.5 text-sm font-black text-white hover:border-[#D4FF24] hover:shadow-[0_0_20px_rgba(212,255,36,0.5)] transition duration-200 cursor-pointer">
                <svg class="w-4 h-4 text-[#D4FF24] filter drop-shadow-[0_0_6px_rgba(212,255,36,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        const answered = new Set([...document.querySelectorAll('.quiz-radio:checked')].map(r => r.name)).size;
        document.getElementById('progress').textContent = `${answered}/${total}`;
    }
</script>
@endsection
