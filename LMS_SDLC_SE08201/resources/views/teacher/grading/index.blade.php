@extends('layouts.app')

@section('title', 'Chấm bài — ' . $assignment->title)

@section('content')
<div class="space-y-6">
    <!-- Header Thông tin Bài tập & Môn / Lớp -->
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-start gap-3.5">
                <a href="{{ route('teacher.assignments.index') }}" class="rounded-md border border-slate-200 bg-slate-50 p-2.5 text-slate-600 hover:bg-slate-100 transition shadow-sm" title="Quay lại danh sách bài tập">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-slate-800">Chấm bài: {{ $assignment->title }}</h1>
                        <span class="rounded-md px-2.5 py-0.5 text-xs font-bold uppercase {{ $assignment->isQuiz() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-[#1e4f8f]/10 text-[#1e4f8f] border border-[#1e4f8f]/30' }}">
                            {{ $assignment->typeLabel() }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                        <span>Môn học: <strong class="text-slate-700">{{ $assignment->classRoom?->subject?->subject_name }} ({{ $assignment->classRoom?->subject?->subject_code }})</strong></span> ·
                        <span>Lớp: <strong class="text-[#1e4f8f]">{{ $assignment->classRoom?->class_name }}</strong></span> ·
                        <span>Thang điểm: <strong class="text-[#1e4f8f]">{{ $assignment->max_score }}</strong></span> ·
                        <span>Hạn nộp: <strong class="{{ $assignment->isOverdue() ? 'text-rose-600' : 'text-slate-700' }}">{{ $assignment->due_date ? $assignment->due_date->format('H:i d/m/Y') : 'Không giới hạn' }}</strong></span>
                        @if ($assignment->isOverdue())
                            <span class="rounded bg-rose-50 border border-rose-200 px-1.5 py-0.2 text-[10px] font-bold text-rose-700">Đã hết hạn</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Nút thao tác nhanh -->
            <div class="flex flex-wrap items-center gap-2">
                @if ($assignment->isEssay())
                    @if ($assignment->isGradesPublished())
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-xs">
                            <span>✓</span> Đã công bố điểm ({{ $assignment->grades_published_at->format('H:i d/m/Y') }})
                        </span>
                    @else
                        <form method="POST" action="{{ route('teacher.grading.publish-grades', $assignment) }}" onsubmit="return confirm('Xác nhận công bố điểm bài tự luận cho toàn bộ lớp và gửi thông báo tới học sinh?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white transition shadow-sm cursor-pointer">
                                <span>✓</span> Công bố điểm của lớp
                            </button>
                        </form>
                    @endif
                @endif
                @if ($notSubmittedCount > 0)
                    <form method="POST" action="{{ route('teacher.grading.remind', $assignment) }}" onsubmit="return confirm('Bạn có chắc muốn gửi thông báo nhắc nhở nộp bài đến toàn bộ {{ $notSubmittedCount }} sinh viên chưa nộp?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-amber-500 hover:bg-amber-600 px-4 py-2 text-xs font-bold text-white transition shadow-sm">
                            <span>📢</span> Nhắc nhở nộp bài ({{ $notSubmittedCount }} bạn)
                        </button>
                    </form>
                @endif
                <button type="button" onclick="openExtendDeadlineModal()" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition">
                    <span>⏰</span> Sửa hạn nộp bài
                </button>
                <a href="{{ route('teacher.assignments.show', $assignment) }}" class="rounded-md border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                    Chi tiết đề bài
                </a>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- THỐNG KÊ TỶ LỆ NỘP BÀI (PROGRESS & KPIS)                                  -->
        <!-- ========================================================================= -->
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Sĩ số lớp -->
            <div class="rounded-md border border-slate-200 bg-slate-50/70 p-3.5 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sĩ số lớp</span>
                <p class="text-xl font-extrabold text-slate-800 mt-0.5">{{ $totalStudents }} <span class="text-xs font-normal text-slate-500">SV</span></p>
            </div>

            <!-- Đã nộp bài -->
            <div class="rounded-md border border-emerald-200 bg-emerald-50/80 p-3.5 text-center">
                <div class="flex items-center justify-center gap-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Đã nộp bài</span>
                    <span class="rounded bg-emerald-200/80 text-emerald-800 px-1.5 py-0.2 text-[10px] font-extrabold">{{ $submissionRate }}%</span>
                </div>
                <p class="text-xl font-extrabold text-emerald-800 mt-0.5">{{ $submittedCount }} <span class="text-xs font-normal text-emerald-600">SV</span></p>
            </div>

            <!-- Chưa nộp bài -->
            <div class="rounded-md border {{ $notSubmittedCount > 0 ? 'border-rose-200 bg-rose-50/80' : 'border-slate-200 bg-slate-50' }} p-3.5 text-center">
                <div class="flex items-center justify-center gap-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider {{ $notSubmittedCount > 0 ? 'text-rose-700' : 'text-slate-500' }}">Chưa nộp bài</span>
                    <span class="rounded {{ $notSubmittedCount > 0 ? 'bg-rose-200/80 text-rose-800' : 'bg-slate-200 text-slate-600' }} px-1.5 py-0.2 text-[10px] font-extrabold">
                        {{ $totalStudents > 0 ? round(($notSubmittedCount / $totalStudents) * 100, 1) : 0 }}%
                    </span>
                </div>
                <p class="text-xl font-extrabold {{ $notSubmittedCount > 0 ? 'text-rose-700' : 'text-slate-700' }} mt-0.5">{{ $notSubmittedCount }} <span class="text-xs font-normal">SV</span></p>
            </div>

            <!-- Tiến độ chấm bài -->
            <div class="rounded-md border border-blue-200 bg-blue-50/80 p-3.5 text-center">
                <div class="flex items-center justify-center gap-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#1e4f8f]">Đã chấm</span>
                    <span class="rounded bg-blue-200/80 text-[#1e4f8f] px-1.5 py-0.2 text-[10px] font-extrabold">{{ $gradingRate }}%</span>
                </div>
                <p class="text-xl font-extrabold text-[#1e4f8f] mt-0.5">{{ $gradedCount }}/{{ $submittedCount }} <span class="text-xs font-normal text-blue-600">bài</span></p>
            </div>
        </div>

        <!-- Thanh Progress Bar Tỷ Lệ Nộp Bài Trực Quan -->
        <div class="mt-4 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <span>📊</span> Tỷ lệ hoàn thành nộp bài của lớp:
                </span>
                <span class="font-extrabold text-sm {{ $submissionRate >= 75 ? 'text-emerald-700' : ($submissionRate >= 40 ? 'text-amber-700' : 'text-rose-700') }}">
                    {{ $submittedCount }}/{{ $totalStudents }} sinh viên ({{ $submissionRate }}%)
                </span>
            </div>
            <div class="w-full bg-slate-200/80 rounded-full h-3 overflow-hidden p-0.5">
                <div class="h-full rounded-full transition-all duration-700 {{ $submissionRate >= 75 ? 'bg-emerald-500' : ($submissionRate >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                     style="width: {{ $submissionRate }}%"></div>
            </div>
            <div class="mt-1.5 flex flex-wrap items-center justify-between text-[11px] text-slate-500">
                <span>
                    @if ($notSubmittedCount == 0)
                        🎉 Tuyệt vời! Toàn bộ 100% sinh viên trong lớp đã nộp bài.
                    @else
                        ⚠️ Còn <strong>{{ $notSubmittedCount }} sinh viên</strong> chưa nộp bài. Hãy gửi thông báo nhắc nhở để các em nộp kịp hạn.
                    @endif
                </span>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="copyEmails()" class="text-[#1e4f8f] hover:underline font-semibold cursor-pointer">
                        📋 Copy Email các bạn chưa nộp
                    </button>
                    <span>·</span>
                    <button type="button" onclick="copyNameList()" class="text-[#1e4f8f] hover:underline font-semibold cursor-pointer">
                        📋 Copy Danh sách nhắc nhở (Zalo/Group)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- BỘ LỌC CHECK NHANH AI ĐÃ NỘP & CHƯA NỘP (INSTANT STATUS FILTER TABS)       -->
    <!-- ========================================================================= -->
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Tabs lọc theo trạng thái nộp bài -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        onclick="setTabFilter('ALL')"
                        id="tabBtn-ALL"
                        class="tab-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold transition shadow-sm bg-slate-900 text-white border border-slate-900"
                        data-tab="ALL">
                    <span>Tất cả sinh viên</span>
                    <span class="rounded-full bg-slate-700 text-white px-2 py-0.2 text-[10px]">{{ $totalStudents }}</span>
                </button>

                <button type="button"
                        onclick="setTabFilter('SUBMITTED')"
                        id="tabBtn-SUBMITTED"
                        class="tab-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold transition shadow-sm bg-white text-emerald-700 border border-emerald-300 hover:bg-emerald-50"
                        data-tab="SUBMITTED">
                    <span>✓ Đã nộp bài</span>
                    <span class="rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.2 text-[10px]">{{ $submittedCount }}</span>
                </button>

                <button type="button"
                        onclick="setTabFilter('NOT_SUBMITTED')"
                        id="tabBtn-NOT_SUBMITTED"
                        class="tab-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold transition shadow-sm bg-white text-rose-700 border border-rose-300 hover:bg-rose-50"
                        data-tab="NOT_SUBMITTED">
                    <span>✗ Chưa nộp bài</span>
                    <span class="rounded-full bg-rose-100 text-rose-800 px-2 py-0.2 text-[10px]">{{ $notSubmittedCount }}</span>
                </button>

                <button type="button"
                        onclick="setTabFilter('PENDING')"
                        id="tabBtn-PENDING"
                        class="tab-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold transition shadow-sm bg-white text-amber-700 border border-amber-300 hover:bg-amber-50"
                        data-tab="PENDING">
                    <span>⏳ Chờ chấm điểm</span>
                    <span class="rounded-full bg-amber-100 text-amber-800 px-2 py-0.2 text-[10px]">{{ $pendingCount }}</span>
                </button>

                <button type="button"
                        onclick="setTabFilter('GRADED')"
                        id="tabBtn-GRADED"
                        class="tab-filter-btn inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold transition shadow-sm bg-white text-blue-700 border border-blue-300 hover:bg-blue-50"
                        data-tab="GRADED">
                    <span>📝 Đã chấm</span>
                    <span class="rounded-full bg-blue-100 text-[#1e4f8f] px-2 py-0.2 text-[10px]">{{ $gradedCount }}</span>
                </button>
            </div>

            <!-- Ô tìm kiếm sinh viên tức thì -->
            <div class="relative w-full lg:w-72">
                <input type="text"
                       id="studentSearchInput"
                       oninput="applyLiveSearch()"
                       placeholder="Tìm kiếm theo Tên, MSSV, Email..."
                       class="w-full rounded-md border border-slate-200 pl-9 pr-3.5 py-2 text-xs text-slate-700 placeholder-slate-400 focus:border-[#1e4f8f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- BẢNG DANH SÁCH CHI TIẾT TOÀN BỘ SINH VIÊN (MASTER SUBMISSION TABLE)       -->
    <!-- ========================================================================= -->
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Sinh viên</th>
                        <th class="px-4 py-3.5 text-center">Trạng thái nộp bài</th>
                        <th class="px-4 py-3.5">Thời gian nộp</th>
                        <th class="px-4 py-3.5 text-center">Điểm số</th>
                        <th class="px-5 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="submissionTableBody">
                    @forelse ($allStudentRows as $row)
                        @php
                            $st = $row['student'];
                            $sub = $row['submission'];
                            $hasSubmitted = $row['has_submitted'];
                            $isGraded = $row['is_graded'];
                            $isLate = $row['is_late'];
                        @endphp
                        <tr class="student-sub-row hover:bg-slate-50/80 transition"
                            data-submitted="{{ $hasSubmitted ? '1' : '0' }}"
                            data-graded="{{ $isGraded ? '1' : '0' }}"
                            data-search="{{ mb_strtolower($st->full_name . ' ' . ($st->student_code ?? '') . ' ' . $st->email) }}">
                            <!-- Thông tin sinh viên -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg font-bold text-sm shadow-sm
                                        {{ $hasSubmitted ? 'bg-[#1e4f8f] text-white' : 'bg-rose-100 text-rose-700 border border-rose-300' }}">
                                        {{ mb_substr($st->full_name, 0, 1) }}
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-sm">{{ $st->full_name }}</span>
                                            <span class="rounded bg-slate-100 px-2 py-0.2 text-[11px] font-mono font-bold text-slate-600">
                                                {{ $st->student_code ?? ('SV' . $st->user_id) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $st->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Trạng thái nộp bài -->
                            <td class="px-4 py-3.5 text-center">
                                @if ($hasSubmitted)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 shadow-sm">
                                        ✓ Đã nộp bài
                                    </span>
                                    @if ($isLate)
                                        <div class="text-[10px] font-bold text-rose-600 mt-1">⚠️ Nộp trễ hạn</div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800 shadow-sm">
                                        ✗ Chưa nộp bài
                                    </span>
                                @endif
                            </td>

                            <!-- Thời gian nộp bài -->
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                @if ($hasSubmitted && $sub?->submitted_at)
                                    <div>{{ $sub->submitted_at->format('H:i d/m/Y') }}</div>
                                    @if ($sub->submit_file_path)
                                        <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                                            <span>📎 Có đính kèm file</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">Chưa có bài nộp</span>
                                @endif
                            </td>

                            <!-- Điểm số -->
                            <td class="px-4 py-3.5 text-center">
                                @if ($hasSubmitted)
                                    @if ($isGraded)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700">
                                            {{ $sub->score }} / {{ $assignment->max_score }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                                            ⏳ Chờ chấm
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-300 font-mono">—</span>
                                @endif
                            </td>

                            <!-- Thao tác -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($hasSubmitted && $sub)
                                        <!-- Nút Tải file bài làm -->
                                        @if ($sub->submit_file_path || $sub->student_submit)
                                            <a href="{{ route('teacher.grading.download', $sub) }}" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 p-2 text-xs font-semibold text-slate-700 transition" title="Tải file bài làm">
                                                <svg class="w-4 h-4 text-[#1e4f8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            </a>
                                        @endif

                                        <!-- Nút Chấm điểm -->
                                        @if ($assignment->isQuiz())
                                            <span class="rounded bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 border border-blue-200">
                                                Tự động chấm
                                            </span>
                                        @else
                                            <a href="{{ route('teacher.grading.show', $sub) }}"
                                               class="inline-flex items-center gap-1.5 rounded-md bg-[#1e4f8f] hover:bg-[#163c6d] px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition">
                                                <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>{{ $isGraded ? 'Sửa điểm' : 'Chấm bài' }}</span>
                                            </a>
                                        @endif
                                    @else
                                        <!-- Nút Nhắc nhở riêng cho sinh viên chưa nộp -->
                                        <form method="POST" action="{{ route('teacher.grading.remind.student', [$assignment, $st]) }}" onsubmit="return confirm('Gửi thông báo nhắc nhở nộp bài cho sinh viên {{ $st->full_name }}?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-amber-300 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 transition shadow-sm" title="Nhắc nhở riêng">
                                                <span>📢</span> Nhắc nộp bài
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                Lớp học này chưa có sinh viên nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Thông báo khi tìm kiếm không có kết quả -->
        <div id="noMatchSubRow" class="hidden py-12 text-center text-sm text-slate-500">
            Không tìm thấy sinh viên nào phù hợp với bộ lọc / từ khóa tìm kiếm.
        </div>
    </div>
</div>

<!-- Dữ liệu ẩn phục vụ sao chép danh sách Email & Họ tên sinh viên chưa nộp -->
<textarea id="hiddenEmailsData" class="sr-only" readonly>@foreach ($notSubmitted as $sv){{ $sv->email }}{{ !$loop->last ? ', ' : '' }}@endforeach</textarea>
<textarea id="hiddenNameListData" class="sr-only" readonly>Danh sách sinh viên chưa nộp bài: {{ $assignment->title }} (Lớp: {{ $assignment->classRoom?->class_name }})
Hạn nộp: {{ $assignment->due_date ? $assignment->due_date->format('H:i d/m/Y') : 'Không giới hạn' }}
---------------------------------
@foreach ($notSubmitted as $idx => $sv)
{{ $idx + 1 }}. {{ $sv->full_name }} - {{ $sv->student_code ?? ('ID:' . $sv->user_id) }} ({{ $sv->email }})
@endforeach
---------------------------------
Các bạn khẩn trương hoàn thành và nộp bài trên hệ thống nhé!</textarea>

<script>
let currentTab = 'ALL';

// Chuyển đổi tab bộ lọc
function setTabFilter(tab) {
    currentTab = tab;

    // Cập nhật giao diện nút tab
    document.querySelectorAll('.tab-filter-btn').forEach(btn => {
        const t = btn.getAttribute('data-tab');
        if (t === tab) {
            btn.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
            btn.classList.remove('bg-white');
        } else {
            btn.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
            btn.classList.add('bg-white');
        }
    });

    applyLiveSearch();
}

// Áp dụng bộ lọc tab và tìm kiếm
function applyLiveSearch() {
    const searchVal = document.getElementById('studentSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.student-sub-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const isSub = row.getAttribute('data-submitted') === '1';
        const isGraded = row.getAttribute('data-graded') === '1';
        const searchData = row.getAttribute('data-search');

        let matchTab = false;
        if (currentTab === 'ALL') matchTab = true;
        else if (currentTab === 'SUBMITTED') matchTab = isSub;
        else if (currentTab === 'NOT_SUBMITTED') matchTab = !isSub;
        else if (currentTab === 'PENDING') matchTab = (isSub && !isGraded);
        else if (currentTab === 'GRADED') matchTab = (isSub && isGraded);

        let matchSearch = !searchVal || searchData.includes(searchVal);

        if (matchTab && matchSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noMatch = document.getElementById('noMatchSubRow');
    if (noMatch) {
        noMatch.classList.toggle('hidden', visibleCount > 0);
    }
}

// Sao chép danh sách Email các bạn chưa nộp bài
function copyEmails() {
    const text = document.getElementById('hiddenEmailsData').value.trim();
    if (!text) {
        alert('Không có sinh viên nào chưa nộp bài!');
        return;
    }
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã sao chép danh sách Email của các bạn chưa nộp bài vào bộ nhớ tạm!\nBạn có thể dán (Ctrl+V) ngay vào ô gửi thư.');
    }).catch(() => {
        prompt('Sao chép danh sách Email:', text);
    });
}

// Sao chép danh sách Họ tên và MSSV chưa nộp để gửi vào Zalo/nhóm lớp
function copyNameList() {
    const text = document.getElementById('hiddenNameListData').value.trim();
    if (!text) {
        alert('Không có sinh viên nào chưa nộp bài!');
        return;
    }
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã sao chép danh sách nhắc nhở nộp bài vào bộ nhớ tạm!\nBạn có thể dán (Ctrl+V) vào nhóm Zalo hoặc nhóm lớp.');
    }).catch(() => {
        prompt('Sao chép danh sách nhắc nhở:', text);
    });
}

// Modal sửa hạn nộp bài
function openExtendDeadlineModal() {
    const modal = document.getElementById('extendDeadlineModal');
    if (modal) modal.classList.remove('hidden');
}

function closeExtendDeadlineModal() {
    const modal = document.getElementById('extendDeadlineModal');
    if (modal) modal.classList.add('hidden');
}

// Phím tắt thêm ngày hạn nộp (+1, +3, +7 ngày)
function setQuickDeadline(days) {
    const input = document.getElementById('modal_due_date_input');
    if (!input) return;

    // Lấy mốc thời gian hiện tại hoặc hạn cũ nếu hạn cũ ở tương lai
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

    input.value = formatDateTimeLocal(baseTime);
}

// Đặt hạn vào Chủ Nhật tuần này lúc 23:59
function setWeekendDeadline() {
    const input = document.getElementById('modal_due_date_input');
    if (!input) return;

    let d = new Date();
    const day = d.getDay();
    const diff = (7 - day) % 7; // Số ngày đến Chủ Nhật
    d.setDate(d.getDate() + (diff === 0 ? 7 : diff));
    d.setHours(23, 59, 0, 0);

    input.value = formatDateTimeLocal(d);
}

function formatDateTimeLocal(d) {
    const pad = (n) => String(n).padStart(2, '0');
    const year = d.getFullYear();
    const month = pad(d.getMonth() + 1);
    const date = pad(d.getDate());
    const hours = pad(d.getHours());
    const minutes = pad(d.getMinutes());
    return `${year}-${month}-${date}T${hours}:${minutes}`;
}
</script>

<!-- ========================================================================= -->
<!-- MODAL SỬA HẠN NỘP BÀI / GIA HẠN NỘP BÀI TẬP                             -->
<!-- ========================================================================= -->
<div id="extendDeadlineModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- Header modal -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-4">
            <div class="flex items-center gap-2.5">
                <div class="rounded-lg bg-[#1e4f8f]/10 p-2 text-[#1e4f8f]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Sửa / Gia hạn hạn nộp bài tập</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $assignment->title }} ({{ $assignment->classRoom?->class_name }})</p>
                </div>
            </div>
            <button type="button" onclick="closeExtendDeadlineModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body Form -->
        <form method="POST" action="{{ route('teacher.assignments.extend-deadline', $assignment) }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Hạn nộp hiện tại -->
            <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500">Hạn nộp hiện tại:</span>
                    <div class="text-sm font-bold {{ $assignment->isOverdue() ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $assignment->due_date ? $assignment->due_date->format('H:i, d/m/Y') : 'Không giới hạn' }}
                        @if ($assignment->isOverdue())
                            <span class="ml-1 text-xs font-semibold text-rose-500">(Đã quá hạn)</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-500">Chưa nộp:</span>
                    <div class="text-sm font-extrabold text-amber-600">{{ $notSubmittedCount }} sinh viên</div>
                </div>
            </div>

            <!-- Input Hạn nộp mới -->
            <div>
                <label for="modal_due_date_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Hạn nộp mới <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local"
                       id="modal_due_date_input"
                       name="due_date"
                       value="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : now()->addDays(3)->format('Y-m-d\TH:i') }}"
                       required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#1e4f8f] focus:ring-2 focus:ring-[#1e4f8f]/20 outline-none transition shadow-sm">
            </div>

            <!-- Phím tắt gia hạn nhanh -->
            <div>
                <label class="block text-xs text-slate-500 mb-1.5 font-medium">Chọn nhanh mốc gia hạn:</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="setQuickDeadline(1)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +1 Ngày
                    </button>
                    <button type="button" onclick="setQuickDeadline(3)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +3 Ngày
                    </button>
                    <button type="button" onclick="setQuickDeadline(7)" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        +1 Tuần
                    </button>
                    <button type="button" onclick="setWeekendDeadline()" class="rounded-md border border-slate-200 bg-slate-50 hover:bg-[#1e4f8f]/10 hover:border-[#1e4f8f]/30 hover:text-[#1e4f8f] px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition">
                        Chủ Nhật
                    </button>
                </div>
            </div>

            <!-- Checkbox gửi thông báo gia hạn cho lớp -->
            <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-3.5">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="notify_students" value="1" checked class="mt-0.5 rounded border-slate-300 text-[#1e4f8f] focus:ring-[#1e4f8f]">
                    <div class="text-xs">
                        <span class="font-bold text-slate-800">Gửi thông báo gia hạn nộp bài đến toàn bộ sinh viên trong lớp</span>
                        <p class="text-slate-500 mt-0.5 leading-relaxed">Hệ thống sẽ tạo thông báo chuông cho sinh viên kèm thông tin hạn nộp mới để các em kịp thời nộp bài.</p>
                    </div>
                </label>
            </div>

            <!-- Nút hành động -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeExtendDeadlineModal()" class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-600 transition shadow-sm">
                    Hủy bỏ
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#1e4f8f] hover:bg-[#163c6d] px-5 py-2.5 text-xs font-bold text-white transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Lưu hạn nộp mới
                </button>
            </div>
        </form>
    </div>
</div>

@include('components.toast')
@endsection
