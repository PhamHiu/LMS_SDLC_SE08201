<?php

namespace App\Services\AI;

use App\Models\ClassRoom;
use App\Models\PolicyRule;
use App\Models\PolicyRuleAudit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Động cơ Quy chế có phiên bản (Versioned Policy Rule Engine).
 * - Dùng chung cho cả AI Support lẫn trang Thống kê tỷ lệ nghỉ học (AbsenceStatisticsService).
 * - Không hardcode ngưỡng 20% trong prompt; luôn đọc phiên bản PUBLISHED đúng phạm vi & thời điểm hiệu lực.
 * - So sánh theo tỷ lệ chính xác trước khi làm tròn hiển thị.
 */
class PolicyRuleEngine
{
    private const SCOPE_RANK = [
        PolicyRule::SCOPE_CLASS => 4,
        PolicyRule::SCOPE_SUBJECT => 3,
        PolicyRule::SCOPE_MAJOR => 2,
        PolicyRule::SCOPE_GLOBAL => 1,
    ];

    /**
     * Truy xuất quy định chuyên cần đang có hiệu lực cho 1 lớp / thời điểm / học kỳ.
     * Trả về:
     *  - status: 'RESOLVED' | 'CONFLICT' | 'UNSUPPORTED' | 'NOT_FOUND'
     *  - rule: ?PolicyRule (khi RESOLVED hoặc UNSUPPORTED)
     *  - rules: array<PolicyRule> (khi CONFLICT)
     */
    public function resolveAttendanceRule(?ClassRoom $class = null, ?Carbon $asOfDate = null, ?string $termCode = null): array
    {
        if (! Schema::hasTable('policy_rules')) {
            return ['status' => 'NOT_FOUND', 'rule' => null, 'rules' => []];
        }

        $date = ($asOfDate ?? Carbon::now('Asia/Ho_Chi_Minh'))->toDateString();

        $query = PolicyRule::query()
            ->where('rule_code', PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE)
            ->where('status', PolicyRule::STATUS_PUBLISHED);

        if ($termCode !== null && $termCode !== '') {
            $query->where(function ($q) use ($termCode) {
                $q->where('term_code', $termCode)
                    ->orWhereNull('term_code');
            });
        } else {
            $query->whereDate('effective_from', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $date);
                });
        }

        $candidates = $query->get()->filter(function (PolicyRule $rule) use ($class) {
            return match ($rule->scope_type) {
                PolicyRule::SCOPE_GLOBAL => true,
                PolicyRule::SCOPE_MAJOR => $class !== null && (int) $rule->scope_id === (int) $class->major_id,
                PolicyRule::SCOPE_SUBJECT => $class !== null && (int) $rule->scope_id === (int) $class->subject_id,
                PolicyRule::SCOPE_CLASS => $class !== null && (int) $rule->scope_id === (int) $class->class_id,
                default => false,
            };
        })->values();

        // Nếu có termCode cụ thể, ưu tiên các bản ghi khớp đúng termCode trước bản ghi null
        if ($termCode !== null && $termCode !== '') {
            $exactTerm = $candidates->where('term_code', $termCode)->values();
            if ($exactTerm->isNotEmpty()) {
                $candidates = $exactTerm;
            } else {
                // Không có quy định nào khớp kỳ cũ được hỏi
                return ['status' => 'NOT_FOUND', 'rule' => null, 'rules' => []];
            }
        }

        if ($candidates->isEmpty()) {
            return ['status' => 'NOT_FOUND', 'rule' => null, 'rules' => []];
        }

        // Sắp xếp theo: phạm vi hẹp nhất (CLASS > SUBJECT > MAJOR > GLOBAL) -> priority cao nhất
        $maxScopeRank = $candidates->max(fn (PolicyRule $r) => self::SCOPE_RANK[$r->scope_type] ?? 0);
        $topScope = $candidates->filter(fn (PolicyRule $r) => (self::SCOPE_RANK[$r->scope_type] ?? 0) === $maxScopeRank)->values();

        $maxPriority = $topScope->max('priority');
        $topRules = $topScope->filter(fn (PolicyRule $r) => (int) $r->priority === (int) $maxPriority)->values();

        // Phát hiện xung đột nếu có >= 2 nguồn cùng độ ưu tiên nhưng khác công thức/ngưỡng
        if ($topRules->count() > 1) {
            $signatures = $topRules->map(fn (PolicyRule $r) => implode('|', [
                $r->rule_type,
                $r->operator,
                $r->threshold_value,
                $r->unit,
                $r->basis,
            ]))->unique();

            if ($signatures->count() > 1) {
                return [
                    'status' => 'CONFLICT',
                    'rule' => null,
                    'rules' => $topRules->all(),
                ];
            }
        }

        /** @var PolicyRule $winner */
        $winner = $topRules->sortByDesc('effective_from')->first();

        if ($winner->requires_engineering_support || $winner->rule_type === PolicyRule::TYPE_UNSUPPORTED_CALCULATION) {
            return [
                'status' => 'UNSUPPORTED',
                'rule' => $winner,
                'rules' => [$winner],
            ];
        }

        return [
            'status' => 'RESOLVED',
            'rule' => $winner,
            'rules' => [$winner],
        ];
    }

    /**
     * Đánh giá số buổi nghỉ theo quy định bằng phép so sánh chính xác (không làm tròn trước khi so sánh).
     */
    public function evaluateAbsence(int $absentSessions, int $totalSessions, ?PolicyRule $rule = null): array
    {
        if ($totalSessions <= 0) {
            return [
                'status' => 'INSUFFICIENT_DATA',
                'violated' => null,
                'exact_percent' => null,
                'display_percent' => null,
                'max_allowed_absences' => null,
                'remaining_absences' => null,
                'rule' => $rule,
            ];
        }

        $threshold = $rule?->threshold_value ?? 20.0;
        $operator = $rule?->operator ?? PolicyRule::OP_GT;
        $unit = $rule?->unit ?? 'PERCENT';

        // Tỷ lệ chính xác trước khi làm tròn
        $exactPercent = ($absentSessions * 100.0) / $totalSessions;
        $displayPercent = round($exactPercent, 1);

        if ($unit === 'SESSIONS') {
            $violated = $this->compareExact((float) $absentSessions, (float) $threshold, $operator);
            $maxAllowed = $operator === PolicyRule::OP_GTE
                ? max(0, (int) ceil($threshold) - 1)
                : max(0, (int) floor($threshold));
        } else {
            // So sánh chính xác bằng số nguyên chéo để tránh sai số dấu phẩy động:
            // absent / total > threshold / 100  <=>  absent * 10000 > total * round(threshold * 100)
            $lhs = $absentSessions * 10000;
            $rhs = (int) round($totalSessions * $threshold * 100);
            $violated = match ($operator) {
                PolicyRule::OP_GT => $lhs > $rhs,
                PolicyRule::OP_GTE => $lhs >= $rhs,
                PolicyRule::OP_LT => $lhs < $rhs,
                PolicyRule::OP_LTE => $lhs <= $rhs,
                default => $lhs > $rhs,
            };

            $rawThresholdSessions = ($totalSessions * $threshold) / 100.0;
            if ($operator === PolicyRule::OP_GTE) {
                // Nếu >= 20% vi phạm: 20% của 20 = 4.0 -> 4 đã vi phạm -> tối đa 3; 20% của 12 = 2.4 -> 3 vi phạm -> tối đa 2
                $maxAllowed = (abs($rawThresholdSessions - round($rawThresholdSessions)) < 1e-9)
                    ? max(0, (int) round($rawThresholdSessions) - 1)
                    : max(0, (int) floor($rawThresholdSessions));
            } else {
                // Nếu > 20% vi phạm: 20% của 20 = 4.0 -> 4 chưa vi phạm -> tối đa 4; 20% của 12 = 2.4 -> tối đa 2
                $maxAllowed = max(0, (int) floor($rawThresholdSessions + 1e-9));
            }
        }

        $remaining = max(0, $maxAllowed - $absentSessions);
        $warningThreshold = max(0.0, $threshold - 5.0);
        $status = $violated
            ? 'FAILED'
            : ($exactPercent >= $warningThreshold ? 'WARNING' : 'SAFE');

        return [
            'status' => $status,
            'violated' => $violated,
            'exact_percent' => $exactPercent,
            'display_percent' => $displayPercent,
            'threshold_value' => $threshold,
            'operator' => $operator,
            'threshold_sessions_exact' => round(($totalSessions * $threshold) / 100.0, 2),
            'max_allowed_absences' => $maxAllowed,
            'remaining_absences' => $remaining,
            'rule' => $rule,
        ];
    }

    private function compareExact(float $lhs, float $rhs, string $operator): bool
    {
        return match ($operator) {
            PolicyRule::OP_GT => $lhs > $rhs,
            PolicyRule::OP_GTE => $lhs >= $rhs,
            PolicyRule::OP_LT => $lhs < $rhs,
            PolicyRule::OP_LTE => $lhs <= $rhs,
            default => $lhs > $rhs,
        };
    }

    /**
     * Tính thử tác động của một phiên bản quy định trên các ca mẫu trước khi duyệt/xuất bản.
     */
    public function simulateRule(PolicyRule $rule, array $sampleCases = []): array
    {
        if (empty($sampleCases)) {
            $sampleCases = [
                ['label' => '4/20 buổi', 'absent' => 4, 'total' => 20],
                ['label' => '5/20 buổi', 'absent' => 5, 'total' => 20],
                ['label' => '2/12 buổi', 'absent' => 2, 'total' => 12],
                ['label' => '3/12 buổi', 'absent' => 3, 'total' => 12],
                ['label' => '3/15 buổi', 'absent' => 3, 'total' => 15],
            ];
        }

        $results = [];
        foreach ($sampleCases as $case) {
            $eval = $this->evaluateAbsence((int) $case['absent'], (int) $case['total'], $rule);
            $results[] = [
                'label' => $case['label'] ?? "{$case['absent']}/{$case['total']}",
                'absent' => (int) $case['absent'],
                'total' => (int) $case['total'],
                'display_percent' => $eval['display_percent'],
                'violated' => $eval['violated'],
                'max_allowed_absences' => $eval['max_allowed_absences'],
                'conclusion' => $rule->requires_engineering_support
                    ? 'Cần bổ sung nghiệp vụ tính toán'
                    : ($eval['violated'] ? 'Vi phạm quy chế (Trượt môn)' : 'Chưa vi phạm (Đạt)'),
            ];
        }

        return $results;
    }

    /**
     * Phân tích văn bản quy định tải lên như DỮ LIỆU KHÔNG TIN CẬY để đề xuất bản nháp.
     * - Không bao giờ tự xuất bản, không đổi quyền, không thực thi lệnh trong văn bản.
     * - Chỉ trích đoạn gốc và gắn cờ `requires_engineering_support` nếu phát hiện công thức chưa hỗ trợ.
     */
    public function proposeDraftFromText(string $rawText, string $sourceDocument): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $rawText));
        $lower = mb_strtolower($clean);

        // Phát hiện phép tính chưa được hệ thống hỗ trợ (ví dụ: quy đổi đi muộn thành vắng, điểm chuyên cần có trọng số)
        $unsupportedPattern = '/(đi muộn.*tính.*vắng|3 lần muộn|2 lần muộn|trọng số chuyên cần|điểm danh theo tín chỉ|nghỉ có phép tính 0\.5)/iu';
        $isUnsupported = (bool) preg_match($unsupportedPattern, $clean);

        $threshold = null;
        $operator = null;
        $excerpt = mb_substr($clean, 0, 450);

        if (preg_match('/([^.!?]{0,120}(?:vắng|nghỉ)[^.!?]{0,80}(\d+(?:[.,]\d+)?)\s*%[^.!?]{0,120}[.!?]?)/iu', $clean, $m)) {
            $excerpt = trim($m[1]);
            $threshold = (float) str_replace(',', '.', $m[2]);

            $segLower = mb_strtolower($excerpt);
            if (preg_match('/(từ\s*\d+(?:[.,]\d+)?\s*%\s*trở lên|từ\s*trở lên|>=|đạt\s*từ|bằng\s*hoặc\s*vượt)/u', $segLower)) {
                $operator = PolicyRule::OP_GTE;
            } elseif (preg_match('/(quá|vượt quá|lớn hơn|trên|>)/u', $segLower)) {
                $operator = PolicyRule::OP_GT;
            } else {
                $operator = PolicyRule::OP_GT;
            }
        }

        $clauseRef = null;
        if (preg_match('/((?:Điều|Mục|Khoản)\s+\d+(?:\.\d+)?(?:\s*,?\s*trang\s+\d+)?)/iu', $clean, $cm)) {
            $clauseRef = trim($cm[1]);
        }

        return [
            'rule_code' => PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE,
            'title' => $isUnsupported
                ? 'Đề xuất nháp quy định mới (Cần bổ sung nghiệp vụ tính toán)'
                : 'Đề xuất nháp quy định ngưỡng vắng chuyên cần',
            'source_document' => $sourceDocument,
            'clause_ref' => $clauseRef,
            'excerpt' => $excerpt,
            'rule_type' => $isUnsupported
                ? PolicyRule::TYPE_UNSUPPORTED_CALCULATION
                : ($threshold !== null ? PolicyRule::TYPE_ATTENDANCE_THRESHOLD : PolicyRule::TYPE_GENERAL_POLICY),
            'threshold_value' => $isUnsupported ? null : $threshold,
            'operator' => $isUnsupported ? null : $operator,
            'unit' => $threshold !== null ? 'PERCENT' : null,
            'basis' => $threshold !== null ? 'TOTAL_CLASS_SESSIONS' : null,
            'requires_engineering_support' => $isUnsupported,
            'status' => PolicyRule::STATUS_DRAFT,
        ];
    }

    /**
     * Ghi nhật ký kiểm toán thay đổi quy định và xóa cache FAQ liên quan khi xuất bản.
     */
    public function logAudit(PolicyRule $rule, ?User $actor, string $action, array $changes = []): PolicyRuleAudit
    {
        $audit = PolicyRuleAudit::create([
            'policy_id' => $rule->policy_id,
            'actor_user_id' => $actor?->user_id,
            'action' => $action,
            'changes_json' => $changes,
            'created_at' => now(),
        ]);

        if (in_array($action, ['PUBLISHED', 'ARCHIVED'], true)) {
            Cache::flush();
        }

        return $audit;
    }

    /**
     * Đảm bảo có ít nhất 1 quy định chuyên cần chuẩn đã xuất bản nếu bảng rỗng (dùng cho môi trường mới).
     */
    public function ensureDefaultAttendancePolicy(): ?PolicyRule
    {
        if (! Schema::hasTable('policy_rules')) {
            return null;
        }

        if (PolicyRule::where('rule_code', PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE)->exists()) {
            return PolicyRule::where('rule_code', PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE)
                ->where('status', PolicyRule::STATUS_PUBLISHED)
                ->latest('effective_from')
                ->first();
        }

        return PolicyRule::create([
            'rule_code' => PolicyRule::CODE_ATTENDANCE_MAX_ABSENCE,
            'title' => 'Quy chế chuyên cần chuẩn: Vắng quá 20% tổng số buổi bị trượt môn',
            'source_document' => 'Sổ tay sinh viên BTEC FPT ver.BTEC.9.2024',
            'clause_ref' => 'Điều 4.2 — Quy định chuyên cần',
            'excerpt' => 'Sinh viên vắng mặt quá 20% tổng số buổi học của môn học sẽ bị đánh trượt môn (FAIL chuyên cần) và phải đăng ký học lại.',
            'scope_type' => PolicyRule::SCOPE_GLOBAL,
            'scope_id' => null,
            'term_code' => null,
            'effective_from' => '2024-09-01',
            'effective_to' => null,
            'status' => PolicyRule::STATUS_PUBLISHED,
            'priority' => 100,
            'rule_type' => PolicyRule::TYPE_ATTENDANCE_THRESHOLD,
            'threshold_value' => 20.00,
            'operator' => PolicyRule::OP_GT,
            'unit' => 'PERCENT',
            'basis' => 'TOTAL_CLASS_SESSIONS',
            'requires_engineering_support' => false,
            'approved_at' => now(),
            'published_at' => now(),
        ]);
    }
}
