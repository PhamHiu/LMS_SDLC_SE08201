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
        // Nếu là reply, đảm bảo bình luận gốc thuộc đúng bài tập
        if ($parentId) {
            $parent = Discussion::findOrFail($parentId);
            if ($parent->assignment_id !== $assignment->assignment_id) {
                abort(422, 'Bình luận gốc không thuộc bài tập này.');
            }
        }

        return Discussion::create([
            'user_id' => $author->user_id,
            'assignment_id' => $assignment->assignment_id,
            'parent_id' => $parentId,
            'comment' => $comment,
        ]);
    }
}
