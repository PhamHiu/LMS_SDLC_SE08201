{{--
    Partial thảo luận dùng chung (Teacher & Student).
    Yêu cầu biến: $assignment (đã load rootDiscussions), $storeRoute (route đăng bình luận)
--}}
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h3 class="font-bold text-slate-800">💬 Thảo luận ({{ $assignment->discussions->count() }})</h3>
    </div>

    @php
        $me = auth()->user();
        $destroyRoute = $me?->isTeacher() ? 'teacher.discussions.destroy' : 'student.discussions.destroy';
    @endphp
    <div class="p-5">
        {{-- Form bình luận mới --}}
        <form method="POST" action="{{ $storeRoute }}" class="mb-5 flex gap-3">
            @csrf
            <input type="text" name="comment" required maxlength="2000" placeholder="Đặt câu hỏi hoặc bình luận..."
                   class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 outline-none">
            <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Gửi</button>
        </form>

        <div class="space-y-4">
            @forelse ($assignment->rootDiscussions as $d)
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $d->author?->isTeacher() ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-600' }} text-sm font-bold">{{ mb_substr($d->author?->full_name ?? '?', 0, 1) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm">
                                <b class="text-slate-700">{{ $d->author?->full_name }}</b>
                                @if ($d->author?->isTeacher())
                                    <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[11px] font-medium text-amber-700">Giảng viên</span>
                                @endif
                                <span class="ml-1 text-xs text-slate-400">{{ $d->created_at?->diffForHumans() }}</span>
                            </p>
                            <p class="mt-1 text-sm text-slate-600">{{ $d->comment }}</p>
                            @if ($me && ($d->user_id === $me->user_id || $me->isTeacher()))
                                <form method="POST" action="{{ route($destroyRoute, $d) }}" onsubmit="return confirm('Xóa bình luận này? Các trả lời bên dưới cũng sẽ bị xóa.')" class="mt-1">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-medium text-rose-500 hover:underline">Xóa</button>
                                </form>
                            @endif

                            {{-- Replies --}}
                            @foreach ($d->replies as $reply)
                                <div class="mt-3 flex items-start gap-2 border-l-2 border-indigo-200 pl-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $reply->author?->isTeacher() ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-600' }} text-xs font-bold">{{ mb_substr($reply->author?->full_name ?? '?', 0, 1) }}</span>
                                    <div>
                                        <p class="text-xs">
                                            <b class="text-slate-700">{{ $reply->author?->full_name }}</b>
                                            @if ($reply->author?->isTeacher())
                                                <span class="ml-1 rounded bg-amber-100 px-1 py-0.5 text-[10px] font-medium text-amber-700">GV</span>
                                            @endif
                                            <span class="ml-1 text-slate-400">{{ $reply->created_at?->diffForHumans() }}</span>
                                        </p>
                                        <p class="mt-0.5 text-sm text-slate-600">{{ $reply->comment }}</p>
                                        @if ($me && ($reply->user_id === $me->user_id || $me->isTeacher()))
                                            <form method="POST" action="{{ route($destroyRoute, $reply) }}" onsubmit="return confirm('Xóa câu trả lời này?')" class="mt-0.5">
                                                @csrf @method('DELETE')
                                                <button class="text-[11px] font-medium text-rose-500 hover:underline">Xóa</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- Form trả lời --}}
                            <details class="mt-2">
                                <summary class="cursor-pointer text-xs font-medium text-indigo-600 hover:underline">↩ Trả lời</summary>
                                <form method="POST" action="{{ $storeRoute }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $d->discussion_id }}">
                                    <input type="text" name="comment" required maxlength="2000" placeholder="Viết câu trả lời..."
                                           class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                                    <button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">Gửi</button>
                                </form>
                            </details>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-slate-400">Chưa có thảo luận nào. Hãy là người đầu tiên đặt câu hỏi!</p>
            @endforelse
        </div>
    </div>
</div>
