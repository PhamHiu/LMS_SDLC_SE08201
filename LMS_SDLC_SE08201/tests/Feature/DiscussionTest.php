<?php

namespace Tests\Feature;

use App\Models\Discussion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsDomain;
use Tests\TestCase;

class DiscussionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDomain;

    public function test_enrolled_student_can_post_comment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $assignment = $this->makeEssay($class);

        $this->actingAs($student)
            ->post(route('student.discussions.store', $assignment), ['comment' => 'Thầy ơi cho hỏi ạ'])
            ->assertRedirect();

        $this->assertDatabaseHas('discussions', [
            'assignment_id' => $assignment->assignment_id,
            'user_id' => $student->user_id,
            'comment' => 'Thầy ơi cho hỏi ạ',
        ]);
    }

    public function test_whitespace_only_comment_is_rejected(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $assignment = $this->makeEssay($class);

        $this->actingAs($student)
            ->post(route('student.discussions.store', $assignment), ['comment' => '     '])
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('discussions', 0);
    }

    public function test_cannot_reply_to_a_reply(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $assignment = $this->makeEssay($class);

        $root = Discussion::create([
            'user_id' => $teacher->user_id,
            'assignment_id' => $assignment->assignment_id,
            'comment' => 'Bình luận gốc',
        ]);
        $reply = Discussion::create([
            'user_id' => $student->user_id,
            'assignment_id' => $assignment->assignment_id,
            'parent_id' => $root->discussion_id,
            'comment' => 'Trả lời',
        ]);

        // Trả lời vào một câu trả lời → chặn (cây chỉ 2 cấp)
        $this->actingAs($student)
            ->post(route('student.discussions.store', $assignment), [
                'comment' => 'Trả lời lồng',
                'parent_id' => $reply->discussion_id,
            ])
            ->assertStatus(422);
    }

    public function test_student_can_delete_own_comment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $assignment = $this->makeEssay($class);

        $comment = Discussion::create([
            'user_id' => $student->user_id,
            'assignment_id' => $assignment->assignment_id,
            'comment' => 'Của tôi',
        ]);

        $this->actingAs($student)
            ->delete(route('student.discussions.destroy', $comment))
            ->assertRedirect();

        $this->assertDatabaseMissing('discussions', ['discussion_id' => $comment->discussion_id]);
    }

    public function test_student_cannot_delete_others_comment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $author = $this->makeStudent();
        $other = $this->makeStudent();
        $this->enroll($author, $class);
        $this->enroll($other, $class);
        $assignment = $this->makeEssay($class);

        $comment = Discussion::create([
            'user_id' => $author->user_id,
            'assignment_id' => $assignment->assignment_id,
            'comment' => 'Của người khác',
        ]);

        $this->actingAs($other)
            ->delete(route('student.discussions.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('discussions', ['discussion_id' => $comment->discussion_id]);
    }

    public function test_class_teacher_can_delete_any_comment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $assignment = $this->makeEssay($class);

        $comment = Discussion::create([
            'user_id' => $student->user_id,
            'assignment_id' => $assignment->assignment_id,
            'comment' => 'Sinh viên viết',
        ]);

        $this->actingAs($teacher)
            ->delete(route('teacher.discussions.destroy', $comment))
            ->assertRedirect();

        $this->assertDatabaseMissing('discussions', ['discussion_id' => $comment->discussion_id]);
    }
}
