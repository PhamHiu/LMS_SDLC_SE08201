<?php

namespace App\Services\AI;

use App\Models\Attendance;
use App\Models\BtecSupportConversation;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\Notification;
use App\Models\PolicyRule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Bộ điều phối nghiệp vụ học vụ nội bộ (Internal Academic Orchestrator) cho AI Support.
 *
 * - Chạy TRƯỚC mọi lệnh RAG, Embedding hay API AI bên ngoài.
 * - Thực thi phân quyền (SupportAuthorizationService) TRƯỚC khi đọc bất kỳ bản ghi cá nhân nào.
 * - Hỗ trợ câu hỏi nhiều ý, tiếng Việt không dấu, câu hỏi tiếp nối (context_state),
 *   phân biệt thiếu quyền vs thiếu thông tin vs lỗi hệ thống, và không bao giờ đẩy PII ra LLM ngoài.
 */
class SupportAcademicOrchestrator
{
    public function __construct(
        private readonly SupportAuthorizationService $auth,
        private readonly PolicyRuleEngine $policyEngine,
    ) {
    }

    /**
     * Phân tích và xử lý nội bộ nếu câu hỏi liên quan đến dữ liệu cá nhân / lớp học / phép tính chuyên cần / quyền truy cập.
     * Trả về `['handled' => false]` nếu là câu hỏi hướng dẫn/quy chế công khai chung có thể chuyển tiếp cho RAG/LLM.
     */
    public function handle(User $actor, string $rawMessage, ?BtecSupportConversation $conversation = null): array
    {
        $text = trim($rawMessage);
        $norm = $this->normalizeVi($text);
        $state = is_array($conversation?->context_state) ? $conversation->context_state : [];
        $me = $actor->isStudent() ? 'thầy' : 'hệ thống';
        $you = $actor->isStudent() ? 'em' : 'bạn';

        // 0. Phát hiện câu hỏi công khai về quy trình của role khác (VD: "Giảng viên chấm bài như thế nào?")
        if ($this->isPublicProcessQuestion($norm) && ! $this->hasOtherUserPrivateRequest($norm) && ! $this->hasPersonalMarker($norm)) {
            return ['handled' => false, 'context_state' => $state];
        }

        // 1. Phát hiện phép tính phần trăm thuần túy (VD: "20% của 15 buổi là bao nhiêu?") — KHÔNG đọc hồ sơ cá nhân
        if ($this->isPureMathAttendanceQuestion($norm) && ! $this->hasPersonalMarker($norm)) {
            return $this->handlePureMathQuestion($norm, $text, $state, $me, $you);
        }

        // 2. Phát hiện số liệu do sinh viên tự cung cấp (VD: "Nếu lớp 20 buổi, em vắng 4 buổi thì sao?")
        $selfProvided = $this->extractSelfProvidedAttendance($norm);
        if ($selfProvided !== null) {
            return $this->handleSelfProvidedAttendance($selfProvided, $norm, $state, $me, $you);
        }

        // 3. Phát hiện yêu cầu dữ liệu riêng tư của người khác / tài liệu chưa công bố / vượt quyền
        $deniedOtherPrivate = $this->hasOtherUserPrivateRequest($norm);
        $hasOwnPrivatePart = $this->hasOwnPartAlongsideOther($norm);

        // Nếu CHỈ đòi xem dữ liệu người khác (không có phần hợp lệ của chính mình) -> chặn ngay TRƯỚC khi đọc DB
        if ($deniedOtherPrivate && ! $hasOwnPrivatePart) {
            $this->auth->logDeniedAttempt($actor, 'other_user_private_data', 'Requested another user private/unpublished record');

            return [
                'handled' => true,
                'intent' => 'access_denied',
                'is_private' => true,
                'private_refs' => ['owner_user_id' => $actor->user_id, 'required_role' => $actor->role],
                'message' => $actor->isStudent()
                    ? 'Thầy chỉ có thể hỗ trợ xem dữ liệu học tập thuộc tài khoản của chính em. Hồ sơ cá nhân hoặc bài chưa công bố của sinh viên khác nằm ngoài phạm vi tài khoản này.'
                    : 'Hệ thống không thể truy xuất dữ liệu nằm ngoài phạm vi phân công của tài khoản hiện tại.',
                'citations' => [],
                'actions' => [],
                'needs_escalation' => false,
                'context_state' => $state,
            ];
        }

        // 4. Kiểm tra câu hỏi quy chế theo học kỳ cụ thể (kỳ cũ / kỳ sau) trước khi kiểm tra intent cá nhân
        $termCode = $this->extractTermCode($norm);
        if ($termCode !== null && $this->mentionsAttendanceRule($norm) && ! $this->hasPersonalMarker($norm)) {
            return $this->handlePolicyByTermQuestion($termCode, $state, $me, $you);
        }

        // 4b. Chuyển thẳng sang RAG nếu là câu hỏi thủ tục hành chính (phúc khảo/khiếu nại) hoặc hỏi vị trí menu trong app (tránh bắt nhầm GRADES/SCHEDULE)
        if (preg_match('/\b(phuc khao|phuc tra|khieu nai diem|o dau trong app|o dau tren app|trong app lms|tren app lms|nam o dau|vao dau de)\b/u', $norm)) {
            return ['handled' => false, 'context_state' => $state];
        }

        // Nhận diện các ý định học vụ nội bộ (hỗ trợ đa ý định trong cùng 1 câu)
        $intents = $this->detectAcademicIntents($norm, $state);
        $routerSubjectHint = null;
        $routerDateHint = null;

        // Nhận diện nhanh cảm xúc học tập, chào hỏi, hoặc câu hỏi quy chế/hướng dẫn công khai rõ ràng (không tốn 2.5-9s gọi LLM Router)
        if (empty($intents)) {
            if ($this->isStudyEncouragement($norm)) {
                return $this->buildEncouragementResponse($actor, $norm, $state);
            }
            if ($this->isGreetingOrThanks($norm)) {
                return $this->buildGreetingOrThanksResponse($actor, $norm, $state);
            }
            if ($this->isObviousPublicPolicyQuestion($norm)) {
                return ['handled' => false, 'context_state' => $state];
            }
        }

        // Nếu bộ nhận diện nhanh chưa khớp -> dùng LLM Structured Intent Router (Zero-PII: chỉ gửi câu hỏi thuần, không gửi dữ liệu cá nhân)
        if (empty($intents)) {
            $routed = app(SupportIntentRouterService::class)->classify($text);
            if ($routed !== null) {
                if (! empty($routed['asks_other_person'])) {
                    $this->auth->logDeniedAttempt($actor, 'other_user_private_data', 'LLM Router detected unauthorized other-user private request');

                    return [
                        'handled' => true,
                        'intent' => 'access_denied',
                        'is_private' => true,
                        'private_refs' => ['owner_user_id' => $actor->user_id, 'required_role' => $actor->role],
                        'message' => $actor->isStudent()
                            ? 'Thầy chỉ có thể hỗ trợ xem dữ liệu học tập thuộc tài khoản của chính em. Hồ sơ cá nhân của sinh viên khác nằm ngoài phạm vi tài khoản này.'
                            : 'Hệ thống không thể truy xuất dữ liệu nằm ngoài phạm vi phân công của tài khoản hiện tại.',
                        'citations' => [],
                        'actions' => [],
                        'needs_escalation' => false,
                        'context_state' => $state,
                    ];
                }

                $routedIntents = $routed['intents'] ?? [];
                $confidence = (float) ($routed['confidence'] ?? 0.0);
                $academicOnly = array_values(array_intersect($routedIntents, SupportIntentRouterService::VALID_ACADEMIC_INTENTS));

                if (! empty($academicOnly) && $confidence >= 0.55) {
                    $intents = $academicOnly;
                    $routerSubjectHint = $routed['subject_hint'] ?? null;
                    $routerDateHint = $routed['date_hint'] ?? null;
                } elseif (in_array('STUDY_ENCOURAGEMENT', $routedIntents, true)) {
                    return $this->buildEncouragementResponse($actor, $norm, $state);
                } elseif (in_array('GREETING_OR_THANKS', $routedIntents, true)) {
                    return $this->buildGreetingOrThanksResponse($actor, $norm, $state);
                } elseif (in_array('OUT_OF_SCOPE', $routedIntents, true) && ! in_array('POLICY_OR_GUIDE', $routedIntents, true)) {
                    return [
                        'handled' => true,
                        'intent' => 'out_of_scope',
                        'is_private' => false,
                        'private_refs' => null,
                        'message' => "Mình chưa xác định được thông tin phù hợp với câu hỏi này trong hệ thống LMS.\n\nBạn có thể hỏi cụ thể hơn về **lịch học**, **giảng viên phụ trách**, **điểm số**, **chuyên cần**, hoặc **quy chế / thủ tục đào tạo** nhé. Nếu cần cán bộ hỗ trợ trực tiếp, bạn hãy bấm nút **Tạo Ticket** bên dưới.",
                        'citations' => [],
                        'actions' => [],
                        'needs_escalation' => true,
                        'context_state' => $state,
                    ];
                }
            }
        }

        // Nếu không khớp bất kỳ nghiệp vụ nội bộ nào -> chuyển cho RAG/LLM (nhưng không rò rỉ dữ liệu riêng tư)
        if (empty($intents)) {
            return ['handled' => false, 'context_state' => $state];
        }

        // 5. Lấy danh sách lớp TRONG PHẠM VI QUYỀN của $actor (RBAC-before-read)
        try {
            $accessibleClasses = $this->auth->accessibleClasses($actor);
        } catch (\Throwable $e) {
            Log::error('AI Support failed to load accessible classes: ' . $e->getMessage());
            return $this->systemErrorResponse('Chưa tải được dữ liệu lớp học do lỗi hệ thống. Vui lòng thử lại sau.', $state);
        }

        // Nếu người dùng nêu rõ mã lớp lạ không nằm trong phạm vi quyền -> chặn trước khi truy vấn lớp đó
        $explicitClassCode = $this->extractExplicitClassCode($text);
        if ($explicitClassCode !== null) {
            $matchedExplicit = $accessibleClasses->first(
                fn (ClassRoom $c) => strcasecmp($c->class_name, $explicitClassCode) === 0
            );
            if (! $matchedExplicit) {
                $this->auth->logDeniedAttempt($actor, 'unauthorized_class_access', "Class {$explicitClassCode} outside actor scope");

                return [
                    'handled' => true,
                    'intent' => 'access_denied',
                    'is_private' => true,
                    'private_refs' => ['owner_user_id' => $actor->user_id, 'required_role' => $actor->role],
                    'message' => $actor->isStudent()
                        ? 'Lớp học em hỏi không nằm trong phạm vi tài khoản hiện tại của em. Thầy chỉ hỗ trợ tra cứu các lớp em đang ghi danh.'
                        : 'Lớp học bạn yêu cầu không thuộc phạm vi phân công của tài khoản hiện tại.',
                    'citations' => [],
                    'actions' => [],
                    'needs_escalation' => false,
                    'context_state' => $state,
                ];
            }
        }

        // Giải phân giải môn học / lớp học từ câu hỏi + ngữ cảnh hội thoại (chỉ trong $accessibleClasses)
        $classResolution = $this->resolveTargetClass($text, $norm, $accessibleClasses, $state, $intents, $routerSubjectHint);

        if ($classResolution['status'] === 'DISAMBIGUATE') {
            $options = $classResolution['candidates']->map(
                fn (ClassRoom $c) => ($c->subject?->subject_name ?? 'Môn học') . " (lớp {$c->class_name})"
            )->values()->all();

            $state['listed_class_ids'] = $classResolution['candidates']->pluck('class_id')->map(fn ($id) => (int) $id)->all();
            $state['active_class_id'] = null;
            $state['pending_intents'] = $intents;

            return [
                'handled' => true,
                'intent' => 'disambiguation',
                'is_private' => true,
                'private_refs' => [
                    'owner_user_id' => $actor->user_id,
                    'required_role' => $actor->role,
                    'class_ids' => $state['listed_class_ids'],
                ],
                'message' => ucfirst($you) . ' muốn xem cụ thể lớp/môn nào trong danh sách sau: ' . implode(' hay ', $options) . '?',
                'citations' => [],
                'actions' => [],
                'needs_escalation' => false,
                'context_state' => $state,
            ];
        }

        /** @var ?ClassRoom $targetClass */
        $targetClass = $classResolution['class'];
        if ($targetClass !== null) {
            $state['active_class_id'] = (int) $targetClass->class_id;
            $state['active_subject_id'] = (int) $targetClass->subject_id;
            $state['listed_class_ids'] = [(int) $targetClass->class_id];
        }
        if ($termCode !== null) {
            $state['active_term'] = $termCode;
        }
        $state['last_intents'] = $intents;
        unset($state['pending_intents']);

        // 6. Thực thi từng ý định hợp lệ độc lập
        $sections = [];
        $citations = [];
        $actions = [];
        $touchedClassIds = [];
        $needsEscalation = false;

        foreach ($intents as $intent) {
            $res = match ($intent) {
                'INSTRUCTOR' => $this->handleInstructor($targetClass, $accessibleClasses, $norm),
                'SUBJECTS' => $this->handleSubjects($actor, $accessibleClasses, $state, $me, $you),
                'SCHEDULE' => $this->handleSchedule($actor, $accessibleClasses, $targetClass, $norm, $me, $you, $routerDateHint),
                'SCHEDULE_CHANGE' => $this->handleScheduleChange($actor, $accessibleClasses, $targetClass, $norm, $me, $you),
                'GRADES' => $this->handleGrades($actor, $accessibleClasses, $targetClass, $me, $you),
                'ATTENDANCE', 'ATTENDANCE_HYPOTHETICAL' => $this->handleAttendance(
                    $actor,
                    $accessibleClasses,
                    $targetClass,
                    $norm,
                    $intent === 'ATTENDANCE_HYPOTHETICAL',
                    $termCode ?? ($state['active_term'] ?? null),
                    $me,
                    $you
                ),
                default => null,
            };

            if ($res === null) {
                continue;
            }

            if (! empty($res['error'])) {
                return $this->systemErrorResponse($res['message'], $state);
            }

            $sections[] = $res['message'];
            $citations = array_merge($citations, $res['citations'] ?? []);
            $actions = array_merge($actions, $res['actions'] ?? []);
            $touchedClassIds = array_merge($touchedClassIds, $res['class_ids'] ?? []);
            if (! empty($res['needs_escalation'])) {
                $needsEscalation = true;
            }
            if (! empty($res['listed_class_ids'])) {
                $state['listed_class_ids'] = $res['listed_class_ids'];
                if (count($res['listed_class_ids']) > 1) {
                    $state['active_class_id'] = null;
                }
            }
        }

        // Nếu câu hỏi có kèm phần trái quyền của người khác -> ghép thông báo chặn phần trái quyền
        if ($deniedOtherPrivate) {
            $this->auth->logDeniedAttempt($actor, 'partial_other_user_private_data', 'Blocked unauthorized part of multi-intent query');
            $sections[] = $actor->isStudent()
                ? 'Ngoài ra, thầy chỉ có thể hiển thị dữ liệu của chính em; thông tin cá nhân của sinh viên khác nằm ngoài phạm vi tài khoản này.'
                : 'Phần yêu cầu ngoài phạm vi phân công của tài khoản hiện tại đã bị chặn.';
        }

        $finalMessage = implode("\n\n", array_filter($sections));
        $primaryIntent = count($intents) > 1 ? 'multi_intent:' . implode('+', $intents) : ($intents[0] ?? 'academic_query');

        return [
            'handled' => true,
            'intent' => $primaryIntent,
            'is_private' => true,
            'private_refs' => [
                'owner_user_id' => $actor->user_id,
                'required_role' => $actor->role,
                'class_ids' => array_values(array_unique(array_map('intval', $touchedClassIds))),
            ],
            'message' => $finalMessage,
            'citations' => $this->uniqueByKey($citations, 'source_id'),
            'actions' => $this->uniqueByKey($actions, 'url'),
            'needs_escalation' => $needsEscalation,
            'context_state' => $state,
        ];
    }

    // =========================================================================
    // CÁC BỘ XỬ LÝ NGHIỆP VỤ (MÔN HỌC, LỊCH HỌC, ĐỔI LỊCH, ĐIỂM, CHUYÊN CẦN)
    // =========================================================================

    private function handleSubjects(User $actor, Collection $classes, array $state, string $me, string $you): array
    {
        if ($classes->isEmpty()) {
            return [
                'message' => $actor->isStudent()
                    ? 'Hiện tại tài khoản của em chưa ghi danh lớp học nào.'
                    : 'Hiện tại tài khoản chưa được phân công lớp học nào.',
                'actions' => [],
                'class_ids' => [],
            ];
        }

        $lines = [
            $actor->isStudent()
                ? "Chào em, đây là danh sách **{$classes->count()} môn học** em đang ghi danh nè:"
                : "Danh sách **{$classes->count()} lớp học** bạn đang phụ trách:",
            '',
        ];

        foreach ($classes as $c) {
            $subName = $c->subject?->subject_name ?? 'Chưa gắn môn';
            $subCode = $c->subject?->subject_code ?? '—';
            $gvName = trim((string) $c->teacher?->full_name) ?: 'Chưa phân công';
            $lines[] = "• **{$subName}** ({$subCode})\n  Lớp: **{$c->class_name}** · Thời lượng: {$c->total_sessions} buổi · GV: {$gvName}";
        }

        if ($actor->isStudent()) {
            $lines[] = '';
            $lines[] = '💡 Em muốn xem lịch học, điểm số hay chuyên cần của môn nào cứ nhắn tên môn cho mình nhé!';
        }

        $url = $actor->isStudent() ? route('student.schedule.index') : ($actor->isTeacher() ? route('teacher.classes.index') : route('admin.classes.index'));

        return [
            'message' => implode("\n", $lines),
            'actions' => [['label' => $actor->isStudent() ? 'Mở Thời khóa biểu & Lớp học' : 'Mở Danh sách Lớp', 'url' => $url]],
            'class_ids' => $classes->pluck('class_id')->all(),
            'listed_class_ids' => $classes->pluck('class_id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function handleSchedule(User $actor, Collection $classes, ?ClassRoom $targetClass, string $norm, string $me, string $you, ?string $dateHint = null): array
    {
        if ($classes->isEmpty()) {
            return [
                'message' => 'Tài khoản hiện chưa có lớp học nào nên chưa có thời khóa biểu.',
                'class_ids' => [],
            ];
        }

        [$startDate, $endDate, $dateLabel] = $this->resolveDateRange($norm, false, $dateHint);
        $classIds = $targetClass ? [$targetClass->class_id] : $classes->pluck('class_id')->all();

        try {
            $sessions = ClassSession::with(['classRoom.subject', 'classRoom.teacher', 'slot', 'room'])
                ->whereIn('class_id', $classIds)
                ->whereDate('session_date', '>=', $startDate->toDateString())
                ->whereDate('session_date', '<=', $endDate->toDateString())
                ->orderBy('session_date')
                ->orderBy('slot_id')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AI Support schedule load failed: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Chưa tải được dữ liệu thời khóa biểu do lỗi hệ thống. Vui lòng thử lại sau.',
            ];
        }

        if ($sessions->isEmpty()) {
            $scopeText = $targetClass ? " môn **{$targetClass->subject?->subject_name}** (lớp {$targetClass->class_name})" : '';
            $friendlyEmpty = $actor->isStudent()
                ? "📅 **Thời khóa biểu — {$dateLabel}**{$scopeText}\n\nEm không có buổi học nào trong thời gian này đâu. Tranh thủ nghỉ ngơi hoặc xem lại bài cũ nhé!"
                : "📅 **Thời khóa biểu — {$dateLabel}**{$scopeText}\n\nHiện không có buổi học nào được xếp trong khoảng thời gian này.";

            return [
                'message' => $friendlyEmpty,
                'actions' => $actor->isStudent() ? [['label' => 'Xem Thời khóa biểu đầy đủ', 'url' => route('student.schedule.index')]] : [],
                'class_ids' => $classIds,
            ];
        }

        $groupedByDate = $sessions->groupBy(fn ($s) => $s->session_date->format('Y-m-d'));
        $lines = [
            $actor->isStudent()
                ? "Chào em! Đây là **Thời khóa biểu — {$dateLabel}** ({$sessions->count()} buổi học):"
                : "📅 **Thời khóa biểu — {$dateLabel}** ({$sessions->count()} buổi học)",
        ];

        foreach ($groupedByDate as $dateKey => $daySessions) {
            /** @var ClassSession $first */
            $first = $daySessions->first();
            $dayOfWeek = $this->vietnameseDayOfWeek($first->session_date);
            $dateFormatted = $first->session_date->format('d/m/Y');

            $lines[] = '';
            $lines[] = "**{$dayOfWeek}, {$dateFormatted}**";

            foreach ($daySessions as $s) {
                $startShort = $s->slot?->start_time ? substr((string) $s->slot->start_time, 0, 5) : '';
                $endShort = $s->slot?->end_time ? substr((string) $s->slot->end_time, 0, 5) : '';
                $slotInfo = $s->slot
                    ? "Ca {$s->slot->slot_number} ({$startShort} – {$endShort})"
                    : 'Chưa xếp ca';
                $roomInfo = $s->room
                    ? "Phòng **{$s->room->room_name}**"
                    : '**Chưa xếp phòng**';
                $subName = $s->classRoom?->subject?->subject_name ?? 'Môn học';
                $clsName = $s->classRoom?->class_name ?? '';
                $gvName = trim((string) $s->classRoom?->teacher?->full_name);
                $gvPart = $gvName !== '' ? " · GV: {$gvName}" : '';

                $lines[] = "• **{$slotInfo}** — **{$subName}**\n  Lớp: {$clsName} · {$roomInfo}{$gvPart}";
            }
        }

        if ($actor->isStudent()) {
            $lines[] = '';
            $lines[] = '💡 **Nhắc nhỏ:** Em nhớ đến lớp trước giờ khoảng 10–15 phút để điểm danh đầu ca cho thoải mái, tránh bị đánh vắng nhé!';
        }

        return [
            'message' => implode("\n", $lines),
            'actions' => $actor->isStudent() ? [['label' => 'Mở Thời khóa biểu', 'url' => route('student.schedule.index')]] : [],
            'class_ids' => $sessions->pluck('class_id')->unique()->all(),
        ];
    }

    private function handleScheduleChange(User $actor, Collection $classes, ?ClassRoom $targetClass, string $norm, string $me, string $you): array
    {
        if ($classes->isEmpty()) {
            return ['message' => 'Tài khoản hiện chưa có lớp học nào để kiểm tra thay đổi lịch.', 'class_ids' => []];
        }

        [$startDate, $endDate, $dateLabel] = $this->resolveDateRange($norm, defaultToUpcomingWeek: true);
        $classIds = $targetClass ? [$targetClass->class_id] : $classes->pluck('class_id')->all();

        try {
            $sessions = ClassSession::with(['classRoom.subject', 'slot', 'room'])
                ->whereIn('class_id', $classIds)
                ->whereDate('session_date', '>=', $startDate->toDateString())
                ->whereDate('session_date', '<=', $endDate->toDateString())
                ->orderBy('session_date')
                ->orderBy('slot_id')
                ->get();

            $notifications = Notification::whereIn('class_id', $classIds)
                ->orderByDesc('created_at')
                ->take(15)
                ->get()
                ->filter(function (Notification $n) {
                    $combined = $this->normalizeVi($n->title . ' ' . $n->body);
                    return (bool) preg_match('/(doi phong|chuyen phong|doi lich|thay doi lich|doi ca|hoan lich|nghi bu|chuyen sang phong)/', $combined);
                })
                ->values();
        } catch (\Throwable $e) {
            Log::error('AI Support schedule change check failed: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Chưa tải được dữ liệu thông báo thay đổi lịch do lỗi hệ thống. Vui lòng thử lại sau.',
            ];
        }

        $firstSession = $sessions->first();
        $roomText = $firstSession
            ? ($firstSession->room ? "phòng **{$firstSession->room->room_name}**" : '**chưa xếp phòng**')
            : null;

        if ($notifications->isNotEmpty()) {
            $n = $notifications->first();
            $createdStr = $n->created_at ? $n->created_at->format('H:i d/m/Y') : '';
            $msg = "Có thông báo thay đổi lịch liên quan đến lớp:\n"
                . "- **{$n->title}** ({$createdStr}): {$n->body}";
            if ($firstSession) {
                $msg .= "\n- Lịch hiện ghi trên hệ thống ngày {$firstSession->session_date->format('d/m/Y')}: {$roomText}.";
            }

            return [
                'message' => $msg,
                'citations' => [['source_id' => 'NOTIF-' . $n->notification_id, 'title' => $n->title]],
                'actions' => $actor->isStudent() ? [['label' => 'Xem Thông báo', 'url' => route('student.notifications.index')]] : [],
                'class_ids' => $classIds,
            ];
        }

        if ($firstSession) {
            $msg = "Lịch hiện tại ngày **{$firstSession->session_date->format('d/m/Y')}** ghi {$roomText}. "
                . ucfirst($me) . ' chưa tìm thấy thông báo đủ để xác nhận buổi này đã đổi phòng hay đổi lịch.';
        } else {
            $msg = ucfirst($me) . " chưa tìm thấy thông báo thay đổi lịch nào cho {$dateLabel} và hiện cũng không có buổi học nào trong khoảng thời gian này.";
        }

        return [
            'message' => $msg,
            'actions' => $actor->isStudent() ? [['label' => 'Kiểm tra Thông báo lớp', 'url' => route('student.notifications.index')]] : [],
            'class_ids' => $classIds,
        ];
    }

    private function handleGrades(User $actor, Collection $classes, ?ClassRoom $targetClass, string $me, string $you): array
    {
        if (! $actor->isStudent()) {
            // Với giảng viên: chỉ tóm tắt phạm vi lớp được phân công
            if ($classes->isEmpty()) {
                return ['message' => 'Bạn chưa được phân công lớp nào để tra cứu điểm.', 'class_ids' => []];
            }
            $scopeIds = $targetClass ? [$targetClass->class_id] : $classes->pluck('class_id')->all();
            $countGraded = Submission::whereHas('assignment', fn ($q) => $q->whereIn('class_id', $scopeIds))
                ->whereNotNull('score')
                ->count();
            return [
                'message' => "Trong phạm vi lớp được phân công, hiện có **{$countGraded}** bài nộp đã có điểm. Bạn có thể mở trang Chấm bài để xem chi tiết từng sinh viên.",
                'actions' => [['label' => 'Mở Bài tập & Chấm bài', 'url' => route('teacher.assignments.index')]],
                'class_ids' => $scopeIds,
            ];
        }

        $classIds = $targetClass ? [$targetClass->class_id] : $classes->pluck('class_id')->all();
        if (empty($classIds)) {
            return ['message' => 'Em chưa ghi danh lớp học nào nên chưa có dữ liệu điểm.', 'class_ids' => []];
        }

        try {
            // Truy vấn STRICT theo $actor->user_id và $classIds đã xác thực quyền
            $submissions = Submission::with(['assignment.classRoom.subject'])
                ->where('user_id', $actor->user_id)
                ->whereHas('assignment', fn ($q) => $q->whereIn('class_id', $classIds))
                ->orderByDesc('submitted_at')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AI Support grade load failed: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Chưa tải được dữ liệu điểm số do lỗi hệ thống. Vui lòng thử lại sau.',
            ];
        }

        $scopeLabel = $targetClass
            ? ($targetClass->subject?->subject_name ?? 'Môn học') . " (Lớp {$targetClass->class_name})"
            : 'các môn em đang học';

        if ($submissions->isEmpty()) {
            return [
                'message' => "📊 **Bảng điểm — {$scopeLabel}**\n\nHiện chưa có kết quả điểm nào được ghi nhận đâu em. Nhớ nộp bài đúng hạn để thầy/cô chấm sớm nhé!",
                'actions' => [['label' => 'Mở Bảng điểm', 'url' => route('student.grades.index')]],
                'class_ids' => $classIds,
            ];
        }

        $lines = ["Chào em, đây là **Bảng điểm — {$scopeLabel}** hiện có của em nè:"];
        $publishedScores = [];
        $hasPending = false;

        foreach ($submissions as $sub) {
            $isPublished = $sub->assignment?->isGradesPublished() ?? true;
            if ($sub->score !== null && $isPublished) {
                $publishedScores[] = (float) $sub->score;
            } else {
                $hasPending = true;
            }
        }

        if ($targetClass !== null) {
            $lines[] = '';
            foreach ($submissions as $sub) {
                $lines[] = $this->formatGradeRow($sub);
            }
        } else {
            $grouped = $submissions->groupBy(fn ($s) => $s->assignment?->class_id ?? 0);
            foreach ($grouped as $groupSubs) {
                $firstSub = $groupSubs->first();
                $cls = $firstSub->assignment?->classRoom;
                $subTitle = ($cls?->subject?->subject_name ?? 'Môn học') . ($cls?->class_name ? " — Lớp {$cls->class_name}" : '');
                $lines[] = '';
                $lines[] = "**{$subTitle}**";
                foreach ($groupSubs as $sub) {
                    $lines[] = $this->formatGradeRow($sub);
                }
            }
        }

        // Lời nhắn động viên / nhắc nhở gần gũi dựa trên kết quả thực tế
        $lines[] = '';
        if (! empty($publishedScores)) {
            $avg = array_sum($publishedScores) / count($publishedScores);
            $minScore = min($publishedScores);
            if ($minScore < 5.0) {
                $lines[] = '💡 **Nhắc nhỏ:** Có bài điểm hơi thấp một chút, em nhớ xem lại nhận xét của thầy/cô và ôn kỹ hơn cho các bài tới để cải thiện điểm nhé!';
            } elseif ($avg >= 8.0) {
                $lines[] = '💡 Điểm của em đang rất tốt luôn, cứ giữ vững phong độ này cho các bài tiếp theo nhé!';
            } else {
                $lines[] = '💡 Em đang giữ nhịp khá ổn rồi đó, cố gắng thêm chút ở các bài sau để kéo điểm lên cao hơn nữa nha!';
            }
        }
        if ($hasPending) {
            $lines[] = '🔔 Các bài đang chờ chấm hoặc chưa công bố em cứ yên tâm đợi giảng viên chốt điểm, hệ thống sẽ báo chuông ngay khi có kết quả nhé.';
        }

        return [
            'message' => implode("\n", $lines),
            'actions' => [['label' => 'Xem Bảng điểm chi tiết', 'url' => route('student.grades.index')]],
            'class_ids' => $submissions->pluck('assignment.class_id')->filter()->unique()->all(),
        ];
    }

    private function formatGradeRow(Submission $sub): string
    {
        $title = $sub->assignment?->title ?? 'Bài tập';
        $typeLabel = ($sub->assignment?->type === 'quiz') ? 'Trắc nghiệm / Quiz' : 'Tự luận / Assignment';
        $isPublished = $sub->assignment?->isGradesPublished() ?? true;

        if ($sub->score !== null && ! $isPublished) {
            $resultText = '**Chưa công bố điểm**';
        } elseif ($sub->score !== null) {
            $scoreFormatted = rtrim(rtrim(number_format((float) $sub->score, 2, '.', ''), '0'), '.');
            $resultText = "**{$scoreFormatted}/10**";
        } elseif ($sub->submitted_at !== null && $sub->graded_at === null) {
            $resultText = "**Chưa chấm điểm** (đã nộp lúc {$sub->submitted_at->format('H:i d/m/Y')})";
        } else {
            $resultText = '**Chưa có kết quả**';
        }

        return "• **{$title}** ({$typeLabel})\n  Kết quả: {$resultText}";
    }

    private function handleAttendance(
        User $actor,
        Collection $classes,
        ?ClassRoom $targetClass,
        string $norm,
        bool $isHypothetical,
        ?string $termCode,
        string $me,
        string $you
    ): array {
        if ($classes->isEmpty()) {
            return ['message' => 'Tài khoản hiện chưa có lớp học nào để tra cứu chuyên cần.', 'class_ids' => []];
        }

        // Nếu hỏi chuyên cần / giả định nghỉ thêm mà chưa chọn lớp khi có nhiều lớp -> ưu tiên giải quyết hoặc báo rõ
        if ($targetClass === null) {
            if ($classes->count() === 1) {
                $targetClass = $classes->first();
            } else {
                $options = $classes->map(fn (ClassRoom $c) => ($c->subject?->subject_name ?? 'Môn') . " (lớp {$c->class_name})")->all();
                return [
                    'message' => ucfirst($you) . ' muốn kiểm tra chuyên cần của lớp/môn nào: ' . implode(', ', $options) . '?',
                    'class_ids' => $classes->pluck('class_id')->all(),
                    'listed_class_ids' => $classes->pluck('class_id')->map(fn ($id) => (int) $id)->all(),
                ];
            }
        }

        $totalSessions = (int) $targetClass->total_sessions;
        if ($totalSessions <= 0) {
            // Fallback đọc số buổi thực tế đã xếp lịch trong bảng sessions nếu cột total_sessions chưa được cập nhật
            $totalSessions = (int) $targetClass->sessions()->count();
        }
        $subName = $targetClass->subject?->subject_name ?? 'Môn học';

        // Kiểm tra tổng số buổi hợp lệ
        if ($totalSessions <= 0) {
            return [
                'message' => "Lớp **{$subName}** (`{$targetClass->class_name}`) chưa có tổng số buổi học hợp lệ (hiện ghi `{$totalSessions}`), nên {$me} chưa đủ căn cứ để tính ngưỡng nghỉ.",
                'class_ids' => [$targetClass->class_id],
            ];
        }

        // Giải phân giải phiên bản quy chế chuyên cần đang hiệu lực (theo lớp, thời điểm hoặc kỳ học)
        $resolved = $this->policyEngine->resolveAttendanceRule($targetClass, null, $termCode);
        if ($resolved['status'] === 'CONFLICT') {
            $conflictLines = ['Hiện có **2 quy định chuyên cần có hiệu lực nhưng khác ngưỡng/điều kiện** nên chưa thể kết luận duy nhất:'];
            foreach ($resolved['rules'] as $r) {
                $conflictLines[] = "- **{$r->title}** ({$r->citationString()}): vắng {$r->operatorLabel()} {$r->threshold_value}%";
            }
            $conflictLines[] = 'Vui lòng liên hệ Phòng Đào tạo để xác nhận quy định áp dụng chính thức.';

            return [
                'message' => implode("\n", $conflictLines),
                'citations' => array_map(fn (PolicyRule $r) => ['source_id' => 'POL-' . $r->policy_id, 'title' => $r->citationString()], $resolved['rules']),
                'needs_escalation' => true,
                'class_ids' => [$targetClass->class_id],
            ];
        }

        if ($resolved['status'] === 'UNSUPPORTED') {
            /** @var PolicyRule $rule */
            $rule = $resolved['rule'];
            return [
                'message' => "Quy định hiện hành **{$rule->title}** ({$rule->citationString()}) áp dụng công thức tính hệ thống chưa hỗ trợ tự động. "
                    . "Trích đoạn: \"{$rule->excerpt}\". Vui lòng kiểm tra trực tiếp với Phòng Đào tạo.",
                'citations' => [['source_id' => 'POL-' . $rule->policy_id, 'title' => $rule->citationString()]],
                'needs_escalation' => true,
                'class_ids' => [$targetClass->class_id],
            ];
        }

        if ($resolved['status'] === 'NOT_FOUND') {
            $termNote = $termCode ? " cho học kỳ `{$termCode}`" : '';
            return [
                'message' => "Hệ thống chưa có phiên bản quy định chuyên cần nào đã xuất bản và có hiệu lực{$termNote} để làm căn cứ tính toán.",
                'needs_escalation' => true,
                'class_ids' => [$targetClass->class_id],
            ];
        }

        /** @var PolicyRule $rule */
        $rule = $resolved['rule'];
        $citations = [['source_id' => 'POL-' . $rule->policy_id, 'title' => $rule->citationString()]];

        // Câu hỏi chỉ hỏi "20% môn Java là mấy buổi?" (không hỏi còn nghỉ được mấy buổi / không giả định)
        if ($this->isClassThresholdCountOnly($norm) && ! $isHypothetical) {
            $eval0 = $this->policyEngine->evaluateAbsence(0, $totalSessions, $rule);
            $exactSessions = str_replace('.', ',', (string) $eval0['threshold_sessions_exact']);
            return [
                'message' => "Chào {$you}, thầy kiểm tra nhanh giúp {$you} môn **{$subName}** (Lớp {$targetClass->class_name}) nè:\n\n"
                    . "• Tổng thời lượng: **{$totalSessions} buổi**\n"
                    . "• Quy định áp dụng ({$rule->operatorLabel()} {$rule->threshold_value}%): {$rule->threshold_value}% tương ứng **{$exactSessions} buổi**\n"
                    . "• Số buổi được nghỉ tối đa: **{$eval0['max_allowed_absences']} buổi** (nghỉ từ **" . ($eval0['max_allowed_absences'] + 1) . " buổi** trở lên sẽ vượt ngưỡng và bị trượt môn)\n\n"
                    . "💡 **Nhắc nhỏ:** {$you} cố gắng đi học đều để giữ chuyên cần an toàn và đừng để sát nút giới hạn nhé!",
                'citations' => $citations,
                'class_ids' => [$targetClass->class_id],
            ];
        }

        // Lấy dữ liệu điểm danh thực tế của $actor trong lớp $targetClass
        try {
            $classSessionIds = $targetClass->sessions()->pluck('session_id');
            $passedSessionsCount = $targetClass->sessions()
                ->whereDate('session_date', '<=', Carbon::now('Asia/Ho_Chi_Minh')->toDateString())
                ->count();

            $records = Attendance::where('user_id', $actor->user_id)
                ->whereIn('session_id', $classSessionIds)
                ->get();
        } catch (\Throwable $e) {
            Log::error('AI Support attendance load failed: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Chưa tải được dữ liệu điểm danh do lỗi hệ thống. Vui lòng thử lại sau.',
            ];
        }

        $markedCount = $records->count();
        $absentCount = $records->where('status', Attendance::ABSENT)->count();
        $incompleteAttendance = ($passedSessionsCount > 0 && $markedCount < $passedSessionsCount);

        $currentEval = $this->policyEngine->evaluateAbsence($absentCount, $totalSessions, $rule);

        // Nếu điểm danh chưa ghi nhận đầy đủ khi hỏi "còn nghỉ được mấy buổi?"
        if ($incompleteAttendance && ! $isHypothetical && str_contains($norm, 'con nghi')) {
            $exactSessions = str_replace('.', ',', (string) $currentEval['threshold_sessions_exact']);
            return [
                'message' => "Lớp **{$subName}** (`{$targetClass->class_name}`) có **{$totalSessions} buổi** ({$rule->threshold_value}% = **{$exactSessions} buổi**, tối đa nghỉ **{$currentEval['max_allowed_absences']} buổi**). "
                    . "Hiện hệ thống mới ghi nhận điểm danh **{$markedCount}/{$passedSessionsCount}** buổi đã học (đã vắng **{$absentCount}** buổi), nên {$me} chưa thể khẳng định chắc chắn {$you} còn nghỉ được bao nhiêu buổi.",
                'citations' => $citations,
                'actions' => $actor->isStudent() ? [['label' => 'Xem Lịch sử điểm danh', 'url' => route('student.attendance.index')]] : [],
                'class_ids' => [$targetClass->class_id],
            ];
        }

        // Tính giả định (KHÔNG ghi điểm danh!)
        if ($isHypothetical) {
            $deltaSessions = $this->resolveHypotheticalDeltaSessions($norm, $targetClass);

            if ($deltaSessions['mode'] === 'TOMORROW_EMPTY') {
                return [
                    'message' => "Ngày mai (**{$deltaSessions['date']}**) lớp **{$subName}** (`{$targetClass->class_name}`) không có buổi học nào. "
                        . "Hiện tại {$you} đã vắng **{$absentCount}/{$totalSessions} buổi** ({$currentEval['display_percent']}%).",
                    'citations' => $citations,
                    'class_ids' => [$targetClass->class_id],
                ];
            }

            $added = $deltaSessions['count'];
            $hypAbsent = $absentCount + $added;
            $hypEval = $this->policyEngine->evaluateAbsence($hypAbsent, $totalSessions, $rule);
            $verdict = $hypEval['violated']
                ? "**vượt/chạm ngưỡng vi phạm** ({$rule->operatorLabel()} {$rule->threshold_value}% → trượt môn)"
                : "**chưa vi phạm** quy định áp dụng ({$rule->operatorLabel()} {$rule->threshold_value}%)";

            $dateNote = $deltaSessions['note'] ? " ({$deltaSessions['note']})" : '';
            $incompleteNote = $incompleteAttendance
                ? " Lưu ý: điểm danh hiện mới ghi nhận {$markedCount}/{$passedSessionsCount} buổi đã học."
                : '';

            return [
                'message' => ucfirst($you) . " đã vắng **{$absentCount}/{$totalSessions} buổi** môn **{$subName}** (`{$targetClass->class_name}`). "
                    . "Nếu nghỉ thêm **{$added} buổi**{$dateNote}, tỷ lệ vắng sẽ là **{$hypAbsent}/{$totalSessions} = {$hypEval['display_percent']}%**, {$verdict}.{$incompleteNote}",
                'citations' => $citations,
                'actions' => $actor->isStudent() ? [['label' => 'Xem Lịch sử điểm danh', 'url' => route('student.attendance.index')]] : [],
                'class_ids' => [$targetClass->class_id],
            ];
        }

        // Trả kết quả chuyên cần hiện tại
        $statusText = $currentEval['violated']
            ? "**đã vi phạm ngưỡng** ({$rule->operatorLabel()} {$rule->threshold_value}% → trượt môn)"
            : "còn được nghỉ tối đa **{$currentEval['remaining_absences']} buổi** nữa (ngưỡng áp dụng: {$rule->operatorLabel()} {$rule->threshold_value}%, tối đa {$currentEval['max_allowed_absences']} buổi)";

        $reminder = $currentEval['violated']
            ? '💡 **Nhắc nhỏ:** Em nhớ liên hệ sớm với giảng viên hoặc Phòng Đào tạo nếu có lý do chính đáng cần giải trình nhé.'
            : ($currentEval['remaining_absences'] === 0
                ? '💡 **Nhắc nhỏ:** Em đã chạm sát giới hạn rồi đó, các buổi tới cố gắng đi học đầy đủ đừng nghỉ thêm buổi nào nha!'
                : '💡 **Nhắc nhỏ:** Em vẫn đang giữ chuyên cần khá ổn, nhưng cố gắng đi học đều để theo kịp bài trên lớp nhé!');

        return [
            'message' => "Chào {$you}, tình trạng chuyên cần môn **{$subName}** (Lớp {$targetClass->class_name}) hiện tại:\n\n"
                . "• Đã vắng: **{$absentCount}/{$totalSessions} buổi** ({$currentEval['display_percent']}%)\n"
                . "• Đánh giá: {$statusText}\n\n"
                . $reminder,
            'citations' => $citations,
            'actions' => $actor->isStudent() ? [['label' => 'Xem Lịch sử điểm danh', 'url' => route('student.attendance.index')]] : [],
            'class_ids' => [$targetClass->class_id],
        ];
    }

    // =========================================================================
    // CÁC TRƯỜNG HỢP ĐẶC BIỆT (TOÁN THUẦN TÚY, SỐ LIỆU TỰ CUNG CẤP, QUY CHẾ KỲ CŨ)
    // =========================================================================

    private function handlePureMathQuestion(string $norm, string $raw, array $state, string $me, string $you): array
    {
        preg_match('/(\d+(?:[.,]\d+)?)\s*%.*?(\d+)\s*buoi/', $norm, $m);
        $pct = (float) str_replace(',', '.', $m[1]);
        $total = (int) $m[2];

        if ($total <= 0) {
            return [
                'handled' => true,
                'intent' => 'pure_math_attendance',
                'is_private' => false,
                'private_refs' => null,
                'message' => 'Tổng số buổi phải lớn hơn 0 thì mới tính được tỷ lệ phần trăm.',
                'citations' => [],
                'actions' => [],
                'needs_escalation' => false,
                'context_state' => $state,
            ];
        }

        $exact = ($total * $pct) / 100.0;
        $exactFormatted = str_replace('.', ',', rtrim(rtrim(number_format($exact, 2, '.', ''), '0'), '.'));
        $floorSessions = (int) floor($exact + 1e-9);

        return [
            'handled' => true,
            'intent' => 'pure_math_attendance',
            'is_private' => false,
            'private_refs' => null,
            'message' => "**{$pct}% của {$total} buổi** bằng **{$exactFormatted} buổi** (nếu quy định tính theo mốc vượt quá {$pct}% thì tương ứng tối đa **{$floorSessions} buổi** nguyên).",
            'citations' => [],
            'actions' => [],
            'needs_escalation' => false,
            'context_state' => $state,
        ];
    }

    private function handleSelfProvidedAttendance(array $nums, string $norm, array $state, string $me, string $you): array
    {
        $absent = $nums['absent'];
        $total = $nums['total'];
        $termCode = $this->extractTermCode($norm);

        $resolved = $this->policyEngine->resolveAttendanceRule(null, null, $termCode);
        if ($resolved['status'] === 'CONFLICT') {
            $lines = ['**Theo số liệu ' . $you . " cung cấp ({$absent}/{$total} buổi)**, hiện có 2 quy định có hiệu lực mâu thuẫn nhau:"];
            foreach ($resolved['rules'] as $r) {
                $eval = $this->policyEngine->evaluateAbsence($absent, $total, $r);
                $lines[] = "- **{$r->title}** ({$r->citationString()}): " . ($eval['violated'] ? 'Vi phạm' : 'Chưa vi phạm');
            }
            return [
                'handled' => true,
                'intent' => 'attendance_self_provided_conflict',
                'is_private' => false,
                'private_refs' => null,
                'message' => implode("\n", $lines),
                'citations' => array_map(fn (PolicyRule $r) => ['source_id' => 'POL-' . $r->policy_id, 'title' => $r->citationString()], $resolved['rules']),
                'actions' => [],
                'needs_escalation' => true,
                'context_state' => $state,
            ];
        }

        $rule = $resolved['rule'] ?? null;
        $eval = $this->policyEngine->evaluateAbsence($absent, $total, $rule);
        $ruleDesc = $rule ? "{$rule->operatorLabel()} {$rule->threshold_value}% ({$rule->citationString()})" : 'quá 20%';
        $verdict = $eval['violated'] ? '**đã vi phạm quy định (trượt môn)**' : '**chưa vi phạm quy định**';

        return [
            'handled' => true,
            'intent' => 'attendance_self_provided',
            'is_private' => false,
            'private_refs' => null,
            'message' => "**Theo số liệu {$you} cung cấp**: vắng **{$absent}/{$total} buổi** = **{$eval['display_percent']}%**. So với quy định áp dụng ({$ruleDesc}), mức này {$verdict}.",
            'citations' => $rule ? [['source_id' => 'POL-' . $rule->policy_id, 'title' => $rule->citationString()]] : [],
            'actions' => [],
            'needs_escalation' => false,
            'context_state' => $state,
        ];
    }

    private function handlePolicyByTermQuestion(string $termCode, array $state, string $me, string $you): array
    {
        $resolved = $this->policyEngine->resolveAttendanceRule(null, null, $termCode);
        if ($resolved['status'] === 'RESOLVED' && $resolved['rule']) {
            /** @var PolicyRule $r */
            $r = $resolved['rule'];
            return [
                'handled' => true,
                'intent' => 'policy_by_term',
                'is_private' => false,
                'private_refs' => null,
                'message' => "Quy định chuyên cần áp dụng cho kỳ **{$termCode}**: **{$r->title}** — ngưỡng vắng **{$r->operatorLabel()} {$r->threshold_value}%**.\nTrích đoạn: \"{$r->excerpt}\"",
                'citations' => [['source_id' => 'POL-' . $r->policy_id, 'title' => $r->citationString()]],
                'actions' => [],
                'needs_escalation' => false,
                'context_state' => array_merge($state, ['active_term' => $termCode]),
            ];
        }

        return [
            'handled' => true,
            'intent' => 'policy_by_term_not_found',
            'is_private' => false,
            'private_refs' => null,
            'message' => "Hệ thống chưa có phiên bản quy định chuyên cần đã xuất bản cho kỳ **{$termCode}**.",
            'citations' => [],
            'actions' => [],
            'needs_escalation' => true,
            'context_state' => $state,
        ];
    }

    // =========================================================================
    // NLU & HELPER FUNCTIONS (TIẾNG VIỆT CÓ DẤU / KHÔNG DẤU / NGỮ CẢNH)
    // =========================================================================

    private function handleInstructor(?ClassRoom $class, Collection $classes, string $question): array
    {
        if ($class === null) {
            if ($classes->isEmpty()) {
                return ['message' => 'LMS chưa có lớp học nào trong phạm vi tài khoản này để tra giảng viên.'];
            }

            if (preg_match('/\b(?:mon|lop)\b/', $question) && !preg_match('/\b(?:cac|nhung|tat ca) (?:mon|lop)\b/', $question)) {
                return ['message' => 'Thầy chưa xác định được lớp em hỏi. Em gửi tên môn đầy đủ hoặc mã lớp trong thời khóa biểu nhé.'];
            }

            $lines = ['👨‍🏫 **Danh sách Giảng viên phụ trách:**', ''];
            foreach ($classes as $c) {
                $subName = $c->subject?->subject_name ?? 'Môn học';
                $gvName = trim((string) $c->teacher?->full_name) ?: 'Chưa phân công';
                $gvEmail = $c->teacher?->email ? " · Email: {$c->teacher->email}" : '';
                $lines[] = "• **{$subName}** (Lớp {$c->class_name})\n  Giảng viên: **{$gvName}**{$gvEmail}";
            }

            return [
                'message' => implode("\n", $lines),
                'class_ids' => $classes->pluck('class_id')->map(fn ($id) => (int) $id)->all(),
                'listed_class_ids' => $classes->pluck('class_id')->map(fn ($id) => (int) $id)->all(),
            ];
        }

        $subName = $class->subject?->subject_name ?? 'Môn học';
        $name = trim((string) $class->teacher?->full_name);
        $email = trim((string) $class->teacher?->email);

        if ($name === '') {
            $message = "👨‍🏫 **Thông tin Giảng viên**\n\n• Môn học: **{$subName}** (Lớp {$class->class_name})\n• Giảng viên: Hiện chưa cập nhật tên giảng viên trên LMS. Em chờ phòng Đào tạo phân công hoặc kiểm tra lại sau nhé!";
        } else {
            $lines = [
                'Chào em, đây là thông tin **Giảng viên phụ trách** môn em hỏi nè:',
                '',
                "• Môn học: **{$subName}**",
                "• Lớp: **{$class->class_name}**",
                "• Giảng viên: **{$name}**",
            ];
            if ($email !== '') {
                $lines[] = "• Email liên hệ: {$email}";
            }
            $lines[] = '';
            $lines[] = '💡 Nếu có thắc mắc về bài học hay điểm danh, em cứ mạnh dạn trao đổi trực tiếp với thầy/cô qua email trên hoặc gửi phiếu hỗ trợ nhé!';
            $message = implode("\n", $lines);
        }

        return [
            'message' => $message,
            'class_ids' => [(int) $class->class_id],
        ];
    }

    private function detectAcademicIntents(string $norm, array $state): array
    {
        $intents = [];

        if (preg_match('/\b(ai (?:giang )?day|(?:thay|co|giang vien|giao vien) nao (?:dang |se )?(?:day|giang day|phu trach)|(?:giang vien|giao vien) (?:cua |phu trach )?(?:mon|lop)|(?:thay|co|giang vien|giao vien) (?:day|giang day) .+ la ai|(?:mon|lop) .+ do ai)\b/', $norm)) {
            $intents[] = 'INSTRUCTOR';
        }

        if (preg_match('/(doi phong|chuyen phong|doi lich|thay doi lich|co doi phong|co doi lich|lich co thay doi)/', $norm)) {
            $intents[] = 'SCHEDULE_CHANGE';
        }
        if (preg_match('/(lich hoc|thoi khoa bieu|tkb|hoc phong nao|phong nao|may gio hoc|hom nay hoc|mai hoc|ngay mai hoc|tuan nay hoc|co tiet|co ca)/', $norm)) {
            $intents[] = 'SCHEDULE';
        }
        if (preg_match('/(diem so|bang diem|diem cua|xem diem|diem bao nhieu|diem mon|duoc may diem|cham chua|co diem chua|diem thi|diem bai)/', $norm)) {
            $intents[] = 'GRADES';
        }
        if (preg_match('/(neu nghi them|neu mai nghi|nghi them \d+|nghi them mot|nghi them 1|nghi them 2|neu vang them|gia su nghi|nghi buoi mai)/', $norm)) {
            $intents[] = 'ATTENDANCE_HYPOTHETICAL';
        } elseif (preg_match('/(con nghi duoc|nghi duoc may buoi|nghi duoc bao nhieu|da vang|da nghi|vang may buoi|nghi may buoi|chuyen can|ty le vang|ty le nghi|20% mon|nghi qua 20%|vang qua 20%|20%.*?(?:may|bao nhieu) buoi|tong so buoi|la may buoi)/', $norm)) {
            $intents[] = 'ATTENDANCE';
        }
        if (preg_match('/(cac mon|nhung mon|mon dang hoc|lop dang hoc|dang ky mon nao|lop cua em|lop cua toi|xem lop\s+[a-z0-9_-]+|danh sach mon|hoc nhung mon)/', $norm)) {
            $intents[] = 'SUBJECTS';
        }

        // Follow-up đổi môn ("thế còn Java?", "còn môn SQL thì sao?") — tuyệt đối không bắt nhầm chữ "con ..." (như "con chó", "con lợn")
        if (empty($intents) && preg_match('/^(the con|con mon|con lop|con diem|con lich|con chuyen can|con java|con sql|con web|con dsa|con oop|con sdlc)\b/', $norm)) {
            return $state['pending_intents'] ?? ($state['last_intents'] ?? ['GRADES']);
        }
        if (empty($intents) && ! empty($state['pending_intents'])) {
            return $state['pending_intents'];
        }

        return array_values(array_unique($intents));
    }

    private function resolveTargetClass(string $raw, string $norm, Collection $accessibleClasses, array $state, array $intents, ?string $subjectHint = null): array
    {
        if ($accessibleClasses->isEmpty()) {
            return ['status' => 'NONE', 'class' => null, 'candidates' => collect()];
        }

        $searchNorm = trim($norm . ' ' . ($subjectHint ? $this->normalizeVi($subjectHint) : ''));
        $hintNorm = $subjectHint ? $this->normalizeVi($subjectHint) : '';

        // 1. Khớp trực tiếp mã lớp, mã môn hoặc tên môn đầy đủ
        $exactMatched = $accessibleClasses->filter(function (ClassRoom $c) use ($searchNorm, $hintNorm) {
            $className = $this->normalizeVi($c->class_name);
            $subCode = $this->normalizeVi($c->subject?->subject_code ?? '');
            $subName = $this->normalizeVi($c->subject?->subject_name ?? '');

            if ($className !== '' && str_contains($searchNorm, $className)) {
                return true;
            }
            if ($subCode !== '' && str_contains($searchNorm, $subCode)) {
                return true;
            }
            if ($subName !== '' && (str_contains($searchNorm, $subName) || ($hintNorm !== '' && str_contains($subName, $hintNorm)))) {
                return true;
            }

            return false;
        })->values();

        if ($exactMatched->count() === 1) {
            return ['status' => 'RESOLVED', 'class' => $exactMatched->first(), 'candidates' => $exactMatched];
        }
        if ($exactMatched->count() > 1) {
            return ['status' => 'DISAMBIGUATE', 'class' => null, 'candidates' => $exactMatched];
        }

        // 2. Fuzzy Token Overlap Scoring tổng quát (không hardcode từng môn học)
        $genericStopWords = [
            'mon', 'hoc', 'lop', 'cua', 'cho', 'voi', 'va', 'la', 'gi', 'nao', 'ai', 'em', 'toi', 'minh', 'ban',
            'thay', 'co', 'giang', 'vien', 'giao', 'day', 'phu', 'trach', 'xem', 'diem', 'lich', 'buoi', 'ngay',
            'mai', 'hom', 'nay', 'tuan', 'vang', 'nghi', 'chuyen', 'can', 'bao', 'nhieu', 'may', 'khong', 'co', 'a',
            'the', 'con', 'thi', 'sao', 'duoc', 'chua', 'co', 'phong', 'ca', 'tiet',
        ];

        $queryTokens = array_values(array_unique(array_filter(
            explode(' ', preg_replace('/[^a-z0-9 ]/', ' ', $searchNorm)),
            fn ($w) => strlen($w) >= 2 && ! in_array($w, $genericStopWords, true)
        )));

        $scored = [];
        foreach ($accessibleClasses as $c) {
            $subName = $this->normalizeVi($c->subject?->subject_name ?? '');
            $className = $this->normalizeVi($c->class_name);
            $subTokens = array_values(array_unique(array_filter(
                explode(' ', preg_replace('/[^a-z0-9 ]/', ' ', $subName . ' ' . $className)),
                fn ($w) => strlen($w) >= 2 && ! in_array($w, ['mon', 'hoc', 'va', 'cho', 'cua', 'voi', 'co', 'ban', 'nang', 'cao'], true)
            )));

            if (empty($subTokens) || empty($queryTokens)) {
                continue;
            }

            $hitCount = 0;
            foreach ($queryTokens as $qt) {
                foreach ($subTokens as $st) {
                    if ($qt === $st || (strlen($qt) >= 3 && strlen($st) >= 3 && (str_contains($st, $qt) || str_contains($qt, $st)))) {
                        $hitCount++;
                        break;
                    }
                }
            }

            if ($hitCount > 0) {
                $ratio = $hitCount / max(1, count($subTokens));
                // Ưu tiên lớp có nhiều từ khớp nhất và tỷ lệ phủ tên môn cao nhất
                $score = ($hitCount * 10.0) + ($ratio * 10.0);
                // Chấp nhận nếu khớp >= 2 từ hoặc khớp 1 từ đặc trưng dài >= 3 ký tự (VD: java, sql, mysql, laravel, sdlc, python...)
                $singleStrongMatch = ($hitCount === 1 && count(array_filter($queryTokens, fn ($qt) => strlen($qt) >= 3 && str_contains($subName . ' ' . $className, $qt))) > 0);
                if ($hitCount >= 2 || $singleStrongMatch) {
                    $scored[] = ['class' => $c, 'score' => $score, 'hits' => $hitCount];
                }
            }
        }

        if (! empty($scored)) {
            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
            $bestScore = $scored[0]['score'];
            $topCandidates = collect(array_values(array_filter(
                $scored,
                fn ($item) => abs($item['score'] - $bestScore) < 0.01
            )))->pluck('class');

            if ($topCandidates->count() === 1) {
                return ['status' => 'RESOLVED', 'class' => $topCandidates->first(), 'candidates' => $topCandidates];
            }
            if ($topCandidates->count() > 1) {
                return ['status' => 'DISAMBIGUATE', 'class' => null, 'candidates' => $topCandidates];
            }
        }

        // 3. Không nêu môn mới -> kiểm tra đại từ hồi chỉ ("môn này", "lớp này", "buổi đó")
        $usesAnaphora = (bool) preg_match('/(mon nay|lop nay|buoi do|mon do|lop do)/', $norm);
        if ($usesAnaphora) {
            $activeId = $state['active_class_id'] ?? null;
            if ($activeId !== null) {
                $activeClass = $accessibleClasses->firstWhere('class_id', (int) $activeId);
                if ($activeClass) {
                    return ['status' => 'RESOLVED', 'class' => $activeClass, 'candidates' => collect([$activeClass])];
                }
            }

            $listedIds = $state['listed_class_ids'] ?? [];
            if (count($listedIds) > 1) {
                $listedClasses = $accessibleClasses->whereIn('class_id', $listedIds)->values();
                if ($listedClasses->count() > 1) {
                    return ['status' => 'DISAMBIGUATE', 'class' => null, 'candidates' => $listedClasses];
                }
            }
        }

        // 3. Nếu hỏi chuyên cần giả định ("nếu nghỉ thêm 1 buổi?") mà đã có active_class_id
        if (in_array('ATTENDANCE_HYPOTHETICAL', $intents, true) || in_array('ATTENDANCE', $intents, true)) {
            $activeId = $state['active_class_id'] ?? null;
            if ($activeId !== null) {
                $activeClass = $accessibleClasses->firstWhere('class_id', (int) $activeId);
                if ($activeClass) {
                    return ['status' => 'RESOLVED', 'class' => $activeClass, 'candidates' => collect([$activeClass])];
                }
            }
        }

        return ['status' => 'NONE', 'class' => null, 'candidates' => collect()];
    }

    private function resolveDateRange(string $norm, bool $defaultToUpcomingWeek = false, ?string $dateHint = null): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Ưu tiên kiểm tra Thứ cụ thể trong câu hỏi (VD: "t4 tuần sau", "thứ 6 tuần này") trước khi lấy cả tuần
        $explicitDow = $this->extractDayOfWeek($norm);
        if ($explicitDow !== null) {
            $isNextWeek = str_contains($norm, 'tuan sau') || strtolower((string) $dateHint) === 'next_week';
            $weekStart = $isNextWeek
                ? $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY)
                : $now->copy()->startOfWeek(Carbon::MONDAY);
            $d = $weekStart->copy()->addDays($explicitDow - 1);
            $weekSuffix = $isNextWeek ? ' tuần sau' : (str_contains($norm, 'tuan nay') ? ' tuần này' : '');

            return [
                $d->copy()->startOfDay(),
                $d->copy()->endOfDay(),
                $this->vietnameseDayOfWeek($d) . $weekSuffix . ' (' . $d->format('d/m/Y') . ')',
            ];
        }

        if ($dateHint !== null) {
            $hint = strtolower(trim($dateHint));
            if ($hint === 'today') {
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hôm nay (' . $now->format('d/m/Y') . ')'];
            }
            if ($hint === 'tomorrow') {
                $d = $now->copy()->addDay();
                return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'ngày mai (' . $d->format('d/m/Y') . ')'];
            }
            if ($hint === 'day_after_tomorrow') {
                $d = $now->copy()->addDays(2);
                return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'ngày kia (' . $d->format('d/m/Y') . ')'];
            }
            if ($hint === 'this_week') {
                $s = $now->copy()->startOfWeek(Carbon::MONDAY);
                $e = $s->copy()->endOfWeek(Carbon::SUNDAY);
                return [$s, $e, 'tuần này (' . $s->format('d/m') . ' – ' . $e->format('d/m/Y') . ')'];
            }
            if ($hint === 'next_week') {
                $s = $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY);
                $e = $s->copy()->endOfWeek(Carbon::SUNDAY);
                return [$s, $e, 'tuần sau (' . $s->format('d/m') . ' – ' . $e->format('d/m/Y') . ')'];
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hint)) {
                try {
                    $d = Carbon::parse($hint, 'Asia/Ho_Chi_Minh');
                    return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'ngày ' . $d->format('d/m/Y')];
                } catch (\Throwable $e) {
                }
            }
        }

        if (preg_match('/(\d{1,2})\/(\d{1,2})(?:\/(\d{4}))?/', $norm, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = ! empty($m[3]) ? (int) $m[3] : $now->year;
            try {
                $d = Carbon::createFromDate($year, $month, $day, 'Asia/Ho_Chi_Minh');
                return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'ngày ' . $d->format('d/m/Y')];
            } catch (\Throwable $e) {
            }
        }

        if (preg_match('/(ngay mai|mai\b)/', $norm)) {
            $d = $now->copy()->addDay();
            return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'ngày mai (' . $this->vietnameseDayOfWeek($d) . ', ' . $d->format('d/m/Y') . ')'];
        }

        if (str_contains($norm, 'ngay kia') || str_contains($norm, 'ngay mot')) {
            $d = $now->copy()->addDays(2);
            return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'ngày kia (' . $this->vietnameseDayOfWeek($d) . ', ' . $d->format('d/m/Y') . ')'];
        }

        if (str_contains($norm, 'hom qua')) {
            $d = $now->copy()->subDay();
            return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'hôm qua (' . $d->format('d/m/Y') . ')'];
        }

        if (str_contains($norm, 'hom nay')) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hôm nay (' . $now->format('d/m/Y') . ')'];
        }

        if (str_contains($norm, 'tuan sau')) {
            $s = $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY);
            $e = $s->copy()->endOfWeek(Carbon::SUNDAY);
            return [$s, $e, 'tuần sau (' . $s->format('d/m') . ' – ' . $e->format('d/m/Y') . ')'];
        }

        if (str_contains($norm, 'tuan nay') || $defaultToUpcomingWeek) {
            $s = $now->copy()->startOfWeek(Carbon::MONDAY);
            $e = $now->copy()->endOfWeek(Carbon::SUNDAY);
            return [$s, $e, 'tuần này (' . $s->format('d/m') . ' – ' . $e->format('d/m/Y') . ')'];
        }

        // Mặc định: 7 ngày tới tính từ hôm nay
        return [$now->copy()->startOfDay(), $now->copy()->addDays(6)->endOfDay(), '7 ngày tới'];
    }

    /** Trả về 1 (Thứ 2) .. 7 (Chủ nhật) nếu câu hỏi nêu rõ thứ trong tuần */
    private function extractDayOfWeek(string $norm): ?int
    {
        if (preg_match('/\b(chu nhat|cn)\b/', $norm)) {
            return 7;
        }
        if (preg_match('/\b(?:thu\s*|t)([2-7])\b/', $norm, $m)) {
            return (int) $m[1] - 1;
        }
        $wordMap = [
            'thu hai' => 1,
            'thu ba' => 2,
            'thu tu' => 3,
            'thu nam' => 4,
            'thu sau' => 5,
            'thu bay' => 6,
        ];
        foreach ($wordMap as $phrase => $idx) {
            if (str_contains($norm, $phrase)) {
                return $idx;
            }
        }

        return null;
    }

    private function vietnameseDayOfWeek(Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            7 => 'Chủ nhật',
            default => '',
        };
    }

    private function resolveHypotheticalDeltaSessions(string $norm, ClassRoom $class): array
    {
        // "Nếu mai nghỉ Java thì sao?" -> đối chiếu lịch ngày mai xem có mấy ca
        if (preg_match('/(ngay mai|mai\b)/', $norm)) {
            $tomorrow = Carbon::now('Asia/Ho_Chi_Minh')->addDay();
            $tomorrowSessions = $class->sessions()
                ->whereDate('session_date', $tomorrow->toDateString())
                ->count();

            if ($tomorrowSessions === 0) {
                return ['mode' => 'TOMORROW_EMPTY', 'count' => 0, 'date' => $tomorrow->format('d/m/Y'), 'note' => ''];
            }

            return [
                'mode' => 'TOMORROW_SESSIONS',
                'count' => $tomorrowSessions,
                'date' => $tomorrow->format('d/m/Y'),
                'note' => "ngày mai {$tomorrow->format('d/m/Y')} có {$tomorrowSessions} ca học",
            ];
        }

        if (preg_match('/(?:nghi|vang)\s*them\s*(\d+)/', $norm, $m)) {
            return ['mode' => 'EXPLICIT_COUNT', 'count' => max(1, (int) $m[1]), 'note' => ''];
        }

        if (preg_match('/(?:nghi|vang)\s*them\s*(hai|2)/', $norm)) {
            return ['mode' => 'EXPLICIT_COUNT', 'count' => 2, 'note' => ''];
        }

        if (preg_match('/(?:nghi|vang)\s*them\s*(ba|3)/', $norm)) {
            return ['mode' => 'EXPLICIT_COUNT', 'count' => 3, 'note' => ''];
        }

        return ['mode' => 'EXPLICIT_COUNT', 'count' => 1, 'note' => ''];
    }

    private function isPublicProcessQuestion(string $norm): bool
    {
        return (bool) preg_match('/(giang vien cham bai nhu the nao|quy trinh cham bai|lam sao de cham bai|cach giang vien|quy trinh dao tao|cach dung lms)/', $norm);
    }

    private function isPureMathAttendanceQuestion(string $norm): bool
    {
        return (bool) preg_match('/\d+(?:[.,]\d+)?\s*%\s*(?:cua|trong)\s*\d+\s*buoi/', $norm);
    }

    private function extractSelfProvidedAttendance(string $norm): ?array
    {
        if (preg_match('/(?:lop\s*(?:co)?\s*)?(\d+)\s*buoi.*?(?:vang|nghi)\s*(\d+)\s*buoi/', $norm, $m)) {
            return ['total' => (int) $m[1], 'absent' => (int) $m[2]];
        }
        if (preg_match('/(?:vang|nghi)\s*(\d+)\s*\/\s*(\d+)\s*buoi/', $norm, $m)) {
            return ['absent' => (int) $m[1], 'total' => (int) $m[2]];
        }

        return null;
    }

    private function hasOtherUserPrivateRequest(string $norm): bool
    {
        return (bool) preg_match('/(cua ban\s+[a-z0-9]+|va ban\s+[a-z0-9]+|cung ban\s+[a-z0-9]+|ban khac|sinh vien khac|nguoi khac|chua cong bo cua|ho so cua\s+[a-z0-9]+|diem cua\s+(?!em\b|toi\b|minh\b|lop\b|mon\b)[a-z]+|mo bai cua ban)/', $norm);
    }

    private function hasOwnPartAlongsideOther(string $norm): bool
    {
        return (bool) preg_match('/(cua em va|cua toi va|cua minh va|diem cua em\b|diem cua toi\b|lich cua em\b)/', $norm);
    }

    private function hasPersonalMarker(string $norm): bool
    {
        return (bool) preg_match('/(\bem\b|\btoi\b|\bminh\b|cua em|cua toi|cua minh)/', $norm);
    }

    private function isClassThresholdCountOnly(string $norm): bool
    {
        return (bool) preg_match('/(\d+\s*%\s*mon|la may buoi|duoc nghi toi da bao nhieu buoi)/', $norm)
            && ! preg_match('/(con nghi|da vang|da nghi)/', $norm);
    }

    private function mentionsAttendanceRule(string $norm): bool
    {
        return (bool) preg_match('/(chuyen can|vang|nghi|truot mon|20%|quy dinh)/', $norm);
    }

    private function extractTermCode(string $norm): ?string
    {
        if (preg_match('/\b(fall|spring|summer)[\s_-]*(20\d{2})\b/', $norm, $m)) {
            return strtoupper($m[1]) . '_' . $m[2];
        }
        if (preg_match('/\b(hk[123]|ky\s*[123])[\s_-]*(20\d{2})\b/', $norm, $m)) {
            return strtoupper(str_replace(' ', '', $m[1])) . '_' . $m[2];
        }

        return null;
    }

    private function extractExplicitClassCode(string $raw): ?string
    {
        // Chỉ phát hiện mã lớp thuần túy bằng cú pháp (KHÔNG truy vấn bảng classes toàn cục!)
        if (preg_match('/(?:lớp|lop)\s+([A-Za-z0-9_-]{3,20})\b/iu', $raw, $m)) {
            $cand = strtoupper($m[1]);
            if (! in_array($cand, ['NAY', 'DO', 'NÀO', 'NAO', 'CUA', 'KHAC', 'KHÁC', 'HOC', 'HỌC', 'JAVA', 'SQL', 'MYSQL', 'LARAVEL', 'SDLC'], true)) {
                return $m[1];
            }
        }
        if (preg_match('/\b((?:SE|CLASS|LOP)[A-Z0-9_-]{2,12})\b/', $raw, $m)) {
            return $m[1];
        }

        return null;
    }

    private function systemErrorResponse(string $message, array $state): array
    {
        return [
            'handled' => true,
            'intent' => 'system_error',
            'is_private' => false,
            'private_refs' => null,
            'message' => $message,
            'citations' => [],
            'actions' => [],
            'needs_escalation' => false,
            'context_state' => $state,
        ];
    }

    private function isObviousPublicPolicyQuestion(string $norm): bool
    {
        // Chỉ bỏ qua LLM Router khi chắc chắn là câu hỏi quy chế/thủ tục/hướng dẫn app công khai (không hỏi dữ liệu cá nhân hay môn cụ thể)
        if (preg_match('/\b(cua em|cua toi|cua minh|em co|em dang|em nghi|em vang|lop em|mon em|hom nay|ngay mai|tuan nay|tuan sau|thu [2-7]|chu nhat)\b/u', $norm)) {
            return false;
        }

        return (bool) preg_match(
            '/\b(quy dinh|quy che|so tay|nop tre|nop muon|hoc phi|bao luu|thoi hoc|phuc khao|hoc lai|thi lai|cam thi|o dau trong app|cach dung|huong dan su dung|lien he phong|dao tao|ctsv|pass|merit|distinction)\b/u',
            $norm
        );
    }

    private function isStudyEncouragement(string $norm): bool
    {
        // Không bắt nhầm câu hỏi thủ tục/quy chế thực sự
        if (preg_match('/\b(bao luu|thoi hoc|hoc phi|phuc khao|quy che|quy dinh|hoan tien|khoi phuc diem danh|ticket|phieu ho tro)\b/u', $norm)) {
            return false;
        }

        return (bool) preg_match(
            '/\b(hoc nhieu|nhieu mon|nhieu bai|nhieu lich|lich day|lich kin|met qua|met moi|chan hoc|chan qua|nan qua|oai qua|ap luc|cang thang|stress|deadline di|nhieu deadline|so truot|so rot|buon ngu|kho qua|hoc khong vo|khong hieu gi|khong muon hoc|luoi hoc|muon nghi hoc|hoc muon xiu|qua tai|kiet suc)\b/u',
            $norm
        );
    }

    private function isGreetingOrThanks(string $norm): bool
    {
        $clean = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $norm) ?? '');
        if ($clean === '' || mb_strlen($clean) > 45) {
            return false;
        }

        return (bool) preg_match(
            '/^(xin chao|chao|hello|hi|hey|alo|cam on|thank|thanks|thank you|tks|ok|oke|okay|da|vang|tam biet|bye)(\s+(ban|minh|ad|admin|thay|co|bot|ai|tro ly|nhe|nha|nhieu|a|nha|nhi))*\s*$/u',
            $clean
        );
    }

    private function buildEncouragementResponse(User $actor, string $norm, array $state): array
    {
        $isStudent = $actor->isStudent();
        $you = $isStudent ? 'em' : 'bạn';
        $actions = [];

        if (preg_match('/\b(kho qua|khong hieu|hoc khong vo|so truot|so rot)\b/u', $norm)) {
            $msg = "Nghe là biết {$you} đang hơi căng thẳng với bài vở rồi, nhưng đừng lo quá nhé! Môn nào mới tiếp cận cũng sẽ thấy khó lúc đầu, cứ chia nhỏ từng phần ra ôn thì mọi thứ sẽ nhẹ nhàng hơn nhiều.\n\n"
                . "Nếu đang vướng kiến thức hay bài tập môn nào, {$you} có thể mở **Mr. Biết Tuốt** để thầy giảng lại từng bước cho dễ hiểu, hoặc hỏi mình xem lại **lịch học / điểm số** để sắp xếp kế hoạch ôn tập nhé!";
        } elseif (preg_match('/\b(hoc nhieu|nhieu mon|nhieu bai|nhieu lich|lich day|lich kin|deadline|qua tai)\b/u', $norm)) {
            $msg = "Công nhận lịch học và bài vở giai đoạn này hơi dày thật, cố lên {$you} nhé! Lúc thấy nhiều quá dễ bị ngợp lắm — {$you} thử nghỉ tay 5–10 phút, uống ngụm nước rồi ưu tiên xử lý từng việc gấp nhất trước là sẽ đỡ áp lực ngay.\n\n"
                . "Cần mình tra nhanh **lịch học tuần này** hoặc **bài tập sắp đến hạn** để {$you} dễ chia thời gian hơn không? Nếu kẹt bài nào thì ghé **Mr. Biết Tuốt** nhờ thầy gỡ rối cùng nhé!";
        } else {
            $msg = "Học liên tục dễ mệt và tụt năng lượng lắm, {$you} cứ cho mình nghỉ ngơi một chút cho lại sức nhé! Chậm lại một nhịp không sao cả, quan trọng là giữ sức khỏe rồi mình tiếp tục từng bước nhỏ.\n\n"
                . "Khi nào sẵn sàng, {$you} có thể nhắn mình kiểm tra nhanh **lịch học, chuyên cần, điểm số**, hoặc mở **Mr. Biết Tuốt** nếu cần người đồng hành ôn bài nhé!";
        }

        if ($isStudent) {
            $actions[] = [
                'label' => 'Học cùng Mr. Biết Tuốt',
                'url' => route('student.ai-tutor.index'),
            ];
            $actions[] = [
                'label' => 'Xem Thời khóa biểu',
                'url' => route('student.schedule.index'),
            ];
        }

        return [
            'handled' => true,
            'intent' => 'study_encouragement',
            'is_private' => false,
            'private_refs' => null,
            'message' => $msg,
            'citations' => [],
            'actions' => $actions,
            'needs_escalation' => false,
            'context_state' => $state,
        ];
    }

    private function buildGreetingOrThanksResponse(User $actor, string $norm, array $state): array
    {
        $you = $actor->isStudent() ? 'em' : 'bạn';
        $isThanks = (bool) preg_match('/\b(cam on|thank|thanks|tks)\b/u', $norm);

        $msg = $isThanks
            ? "Không có gì đâu {$you}! Chúc {$you} học tập thật tốt và đạt kết quả cao nhé. Cần tra cứu lịch học, điểm số, chuyên cần hay quy chế thì cứ nhắn mình bất cứ lúc nào."
            : "Chào {$you}! Mình là **Trợ lý Học vụ LMS**. {$you} cần mình tra cứu **lịch học**, **giảng viên**, **điểm số**, **chuyên cần** hay **quy chế đào tạo** nào không?";

        return [
            'handled' => true,
            'intent' => 'greeting_or_thanks',
            'is_private' => false,
            'private_refs' => null,
            'message' => $msg,
            'citations' => [],
            'actions' => [],
            'needs_escalation' => false,
            'context_state' => $state,
        ];
    }

    private function uniqueByKey(array $items, string $key): array
    {
        $seen = [];
        $out = [];
        foreach ($items as $item) {
            $k = $item[$key] ?? md5(json_encode($item));
            if (! isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $item;
            }
        }

        return $out;
    }

    public function normalizeVi(string $str): string
    {
        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        ];

        $lower = mb_strtolower($str, 'UTF-8');
        $clean = strtr($lower, $map);
        return trim(preg_replace('/\s+/', ' ', $clean) ?? '');
    }
}
