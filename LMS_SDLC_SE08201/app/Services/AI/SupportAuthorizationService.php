<?php

namespace App\Services\AI;

use App\Models\BtecSupportConversation;
use App\Models\BtecSupportMessage;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Tầng kiểm soát phân quyền tập trung (RBAC-before-read) cho AI Support.
 *
 * Nguyên tắc bắt buộc:
 *  - Chỉ tin vào danh tính đã xác thực của Laravel ($actor). Không tin role/user_id/tên lớp tự khai trong prompt.
 *  - Kiểm tra quyền TRƯỚC khi đọc dữ liệu cá nhân từ CSDL.
 *  - Áp dụng phạm vi hẹp đã xác minh trong code:
 *      + STUDENT: chỉ lớp đã ghi danh (enrollments.user_id = $actor->user_id) và bản ghi cá nhân của chính mình.
 *      + TEACHER: CHỈ lớp được phân công trực tiếp (classes.user_id = $actor->user_id).
 *                 (Ghi nhận chênh lệch: TeacherClassService::authorizeClass() hiện có bypass demo cho mọi TEACHER;
 *                  AI Support áp dụng phạm vi hẹp đúng phân công.)
 *      + ADMIN: phạm vi quản trị lớp/môn/quy chế theo route admin; không tự động đọc thay sinh viên nếu không rõ mục tiêu.
 */
class SupportAuthorizationService
{
    /**
     * Danh sách class_id mà tài khoản $actor được phép truy cập.
     */
    public function accessibleClassIds(User $actor): Collection
    {
        if ($actor->isStudent()) {
            return $actor->enrollments()->pluck('class_id')->map(fn ($id) => (int) $id)->unique()->values();
        }

        if ($actor->isTeacher()) {
            return ClassRoom::where('user_id', $actor->user_id)
                ->pluck('class_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        if ($actor->isAdmin()) {
            return ClassRoom::pluck('class_id')->map(fn ($id) => (int) $id)->unique()->values();
        }

        return collect();
    }

    /**
     * Danh sách lớp kèm môn học trong phạm vi quyền của $actor.
     */
    public function accessibleClasses(User $actor): Collection
    {
        $ids = $this->accessibleClassIds($actor);
        if ($ids->isEmpty()) {
            return collect();
        }

        return ClassRoom::with(['subject', 'teacher'])
            ->whereIn('class_id', $ids)
            ->orderBy('class_name')
            ->get();
    }

    /**
     * Kiểm tra $actor có quyền đọc dữ liệu của 1 lớp học cụ thể không.
     */
    public function canAccessClass(User $actor, int $classId): bool
    {
        return $this->accessibleClassIds($actor)->contains($classId);
    }

    /**
     * Kiểm tra $actor có quyền đọc dữ liệu cá nhân (điểm / chuyên cần / lịch) của $targetUserId không.
     */
    public function canViewStudentRecord(User $actor, int $targetUserId, ?int $classId = null): bool
    {
        if ($actor->isStudent()) {
            if ($actor->user_id !== $targetUserId) {
                return false;
            }
            return $classId === null || $this->canAccessClass($actor, $classId);
        }

        if ($actor->isTeacher()) {
            $myClassIds = $this->accessibleClassIds($actor);
            if ($myClassIds->isEmpty()) {
                return false;
            }
            if ($classId !== null) {
                if (! $myClassIds->contains($classId)) {
                    return false;
                }
                return ClassRoom::where('class_id', $classId)
                    ->whereHas('enrollments', fn ($q) => $q->where('user_id', $targetUserId))
                    ->exists();
            }

            return ClassRoom::whereIn('class_id', $myClassIds)
                ->whereHas('enrollments', fn ($q) => $q->where('user_id', $targetUserId))
                ->exists();
        }

        return $actor->isAdmin();
    }

    /**
     * Kiểm tra $actor có quyền truy cập cuộc hội thoại AI Support này không.
     */
    public function canAccessConversation(User $actor, BtecSupportConversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $actor->user_id;
    }

    /**
     * Kiểm tra lại quyền khi mở lại lịch sử hội thoại (ví dụ sau khi bị thu hồi lớp / đổi quyền).
     */
    public function canRehydrateMessage(User $actor, BtecSupportMessage $message): bool
    {
        if (! $message->is_private) {
            return true;
        }

        $refs = $message->private_refs ?? [];
        if (empty($refs)) {
            return (int) ($message->conversation?->user_id) === (int) $actor->user_id;
        }

        if (isset($refs['owner_user_id']) && (int) $refs['owner_user_id'] !== (int) $actor->user_id) {
            return false;
        }

        if (isset($refs['required_role']) && $refs['required_role'] !== $actor->role) {
            return false;
        }

        if (! empty($refs['class_ids']) && is_array($refs['class_ids'])) {
            $allowed = $this->accessibleClassIds($actor);
            foreach ($refs['class_ids'] as $cid) {
                if (! $allowed->contains((int) $cid)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Trả về nội dung đã kiểm tra lại quyền cho lịch sử chat.
     */
    public function formatHistoryMessageForActor(User $actor, BtecSupportMessage $message): array
    {
        $allowed = $this->canRehydrateMessage($actor, $message);

        return [
            'message_id' => $message->message_id,
            'role' => $message->role,
            'message' => $allowed
                ? $message->message
                : 'Nội dung dữ liệu riêng tư ở lượt này không còn thuộc phạm vi quyền hiện tại của tài khoản.',
            'citations' => $allowed ? ($message->citations ?? []) : [],
            'actions' => $allowed ? ($message->actions ?? []) : [],
            'feedback' => $message->feedback,
            'is_private' => (bool) $message->is_private,
            'redacted' => ! $allowed,
            'created_at' => $message->created_at->format('H:i d/m'),
        ];
    }

    /**
     * Ghi nhận sự kiện bảo mật tối thiểu khi chặn thao tác trái quyền (không tự khóa tài khoản).
     */
    public function logDeniedAttempt(User $actor, string $action, string $reason): void
    {
        Log::info('AI Support authorization denied', [
            'user_id' => $actor->user_id,
            'role' => $actor->role,
            'action' => $action,
            'reason' => $reason,
        ]);
    }
}
