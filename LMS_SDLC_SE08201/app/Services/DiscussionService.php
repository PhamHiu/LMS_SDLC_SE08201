<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Discussion;
use App\Models\User;

class DiscussionService
{
    /**
     * Đăng bình luận / trả lời dưới một bài tập.
     */
    public function post(User $author, Assignment $assignment, string $comment, ?int $parentId = null): Discussion
    {
        // Nếu là reply, đảm bảo bình luận gốc thuộc đúng bài tập và LÀ bình luận gốc
        // (cây thảo luận chỉ 2 cấp — không cho trả lời vào một câu trả lời để tránh
        // bình luận bị "mất tích" khỏi giao diện).
        if ($parentId) {
            $parent = Discussion::findOrFail($parentId);
            if ($parent->assignment_id !== $assignment->assignment_id) {
                abort(422, 'Bình luận gốc không thuộc bài tập này.');
            }
            if ($parent->parent_id !== null) {
                abort(422, 'Không thể trả lời vào một câu trả lời.');
            }
        }

        return Discussion::create([
            'user_id' => $author->user_id,
            'assignment_id' => $assignment->assignment_id,
            'parent_id' => $parentId,
            'comment' => $comment,
        ]);
    }

    /**
     * Xóa bình luận. Chỉ CHỦ bình luận hoặc GIẢNG VIÊN đứng lớp mới được xóa.
     * Xóa bình luận gốc sẽ tự động xóa các trả lời (FK cascade ở CSDL).
     */
    public function delete(User $user, Discussion $discussion): void
    {
        $isOwner = $discussion->user_id === $user->user_id;
        $isClassTeacher = $user->isTeacher()
            && $discussion->assignment?->classRoom?->user_id === $user->user_id;

        abort_unless($isOwner || $isClassTeacher, 403, 'Bạn không có quyền xóa bình luận này.');

        $discussion->delete();
    }
}
