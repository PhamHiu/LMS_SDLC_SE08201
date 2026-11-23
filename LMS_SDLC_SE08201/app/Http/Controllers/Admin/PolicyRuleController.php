<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PolicyRule;
use App\Services\AI\PolicyRuleEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quản trị Phiên bản Nội quy & Quy chế có kiểm tra kiểu (Section 6).
 * - Cho phép trường tự cập nhật quy định theo phạm vi, kỳ học và mốc hiệu lực.
 * - Hỗ trợ AI đọc văn bản đề xuất bản nháp (chỉ trích đoạn gốc, không tự xuất bản).
 * - Hỗ trợ chạy mô phỏng tác động (VD: 4/20 buổi với ">" vs ">=") trước khi duyệt & xuất bản.
 */
class PolicyRuleController extends Controller
{
    public function __construct(
        private readonly PolicyRuleEngine $engine,
    ) {
    }

    public function index(): View
    {
        $this->engine->ensureDefaultAttendancePolicy();

        $policies = PolicyRule::with(['creator', 'approver', 'audits.actor'])
            ->orderByDesc('policy_id')
            ->get();

        $simulations = [];
        foreach ($policies as $p) {
            $simulations[$p->policy_id] = $this->engine->simulateRule($p);
        }

        return view('admin.rag.policies', [
            'policies' => $policies,
            'simulations' => $simulations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rule_code' => 'required|string|max:80',
            'title' => 'required|string|max:255',
            'source_document' => 'required|string|max:255',
            'clause_ref' => 'nullable|string|max:120',
            'excerpt' => 'required|string|max:3000',
            'scope_type' => 'required|in:GLOBAL,MAJOR,SUBJECT,CLASS',
            'scope_id' => 'nullable|integer',
            'term_code' => 'nullable|string|max:40',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'priority' => 'nullable|integer|min:1|max:1000',
            'rule_type' => 'required|in:ATTENDANCE_THRESHOLD,GENERAL_POLICY,UNSUPPORTED_CALCULATION',
            'threshold_value' => 'nullable|numeric|min:0|max:100',
            'operator' => 'nullable|in:GT,GTE,LT,LTE',
            'unit' => 'nullable|in:PERCENT,SESSIONS',
            'basis' => 'nullable|in:TOTAL_CLASS_SESSIONS,MARKED_SESSIONS',
            'requires_engineering_support' => 'nullable|boolean',
        ]);

        $rule = PolicyRule::create([
            ...$data,
            'priority' => $data['priority'] ?? 100,
            'status' => PolicyRule::STATUS_DRAFT,
            'requires_engineering_support' => (bool) ($data['requires_engineering_support'] ?? ($data['rule_type'] === PolicyRule::TYPE_UNSUPPORTED_CALCULATION)),
            'created_by' => $request->user()->user_id,
        ]);

        $this->engine->logAudit($rule, $request->user(), 'CREATED', $data);

        return redirect()->route('admin.policies.index')
            ->with('success', "Đã tạo bản nháp quy định #{$rule->policy_id} ({$rule->title}). Hãy chạy mô phỏng và duyệt trước khi xuất bản.");
    }

    /**
     * Đọc văn bản quy định tải lên như DỮ LIỆU KHÔNG TIN CẬY để đề xuất bản nháp (không tự xuất bản).
     */
    public function proposeFromText(Request $request): JsonResponse
    {
        $request->validate([
            'source_document' => 'required|string|max:255',
            'document_text' => 'required|string|max:20000',
        ]);

        $proposal = $this->engine->proposeDraftFromText(
            $request->input('document_text'),
            $request->input('source_document')
        );

        return response()->json([
            'success' => true,
            'proposal' => $proposal,
            'notice' => 'Bản đề xuất chỉ ở trạng thái NHÁP. Quản trị viên cần kiểm tra trích đoạn gốc, chạy mô phỏng và duyệt thủ công trước khi xuất bản.',
        ]);
    }

    /**
     * Chạy mô phỏng tác động trên các ca mẫu (4/20, 5/20, 2/12, 3/12...) trước khi duyệt/xuất bản.
     */
    public function simulate(Request $request, PolicyRule $policy): JsonResponse
    {
        $cases = $request->input('cases', []);
        $results = $this->engine->simulateRule($policy, is_array($cases) ? $cases : []);
        $this->engine->logAudit($policy, $request->user(), 'SIMULATED', ['sample_count' => count($results)]);

        return response()->json([
            'success' => true,
            'policy_id' => $policy->policy_id,
            'title' => $policy->title,
            'operator' => $policy->operator,
            'threshold_value' => $policy->threshold_value,
            'results' => $results,
        ]);
    }

    public function approve(Request $request, PolicyRule $policy): RedirectResponse
    {
        $policy->update([
            'status' => PolicyRule::STATUS_APPROVED,
            'approved_by' => $request->user()->user_id,
            'approved_at' => now(),
        ]);

        $this->engine->logAudit($policy, $request->user(), 'APPROVED', ['status' => PolicyRule::STATUS_APPROVED]);

        return back()->with('success', "Đã duyệt quy định #{$policy->policy_id}. Bạn có thể bấm Xuất bản để áp dụng.");
    }

    public function publish(Request $request, PolicyRule $policy): RedirectResponse
    {
        if ($policy->status !== PolicyRule::STATUS_APPROVED) {
            return back()->with('error', 'Chỉ có thể xuất bản quy định sau khi đã được duyệt (APPROVED).');
        }

        $policy->update([
            'status' => PolicyRule::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->engine->logAudit($policy, $request->user(), 'PUBLISHED', ['status' => PolicyRule::STATUS_PUBLISHED]);

        return back()->with('success', "Đã xuất bản quy định #{$policy->policy_id} và làm mới cache AI Support.");
    }

    public function archive(Request $request, PolicyRule $policy): RedirectResponse
    {
        $policy->update([
            'status' => PolicyRule::STATUS_ARCHIVED,
        ]);

        $this->engine->logAudit($policy, $request->user(), 'ARCHIVED', ['status' => PolicyRule::STATUS_ARCHIVED]);

        return back()->with('success', "Đã lưu trữ (ngưng hiệu lực) quy định #{$policy->policy_id}.");
    }
}
