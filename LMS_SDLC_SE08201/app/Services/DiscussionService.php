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

        $moderation = app(ContentModerationService::class)->moderate($comment, 'comment');
        $storedComment = $moderation['flagged']
            ? "[Nội dung đã bị làm mờ do ngôn từ không phù hợp] {$moderation['masked_text']}"
            : $comment;

        $discussion = Discussion::create([
            'user_id' => $author->user_id,
            'assignment_id' => $assignment->assignment_id,
            'parent_id' => $parentId,
            'comment' => $storedComment,
        ]);

        $discussion->setAttribute('was_moderated', $moderation['flagged']);
        $discussion->setAttribute('moderation_warning', $moderation['warning']);

        return $discussion;
    }
}
