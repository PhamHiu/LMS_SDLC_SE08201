<?php

namespace Tests\Feature\Teacher;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\SeedsDomain;
use Tests\TestCase;

class AssignmentManageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDomain;

    private function essayPayload(array $override = []): array
    {
        return array_merge([
            'title' => 'Bài kiểm tra giữa kỳ',
            'type' => 'essay',
            'description' => 'Nộp file PDF',
            'due_date' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'max_score' => 10,
        ], $override);
    }

    private function quizQuestions(int $n = 2): array
    {
        $qs = [];
        for ($i = 0; $i < $n; $i++) {
            $qs[] = [
                'content' => 'Câu '.($i + 1),
                'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
                'correct_answer' => 'A',
            ];
        }

        return $qs;
    }

    public function test_teacher_creates_essay_assignment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store', $class), $this->essayPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'class_id' => $class->class_id,
            'title' => 'Bài kiểm tra giữa kỳ',
            'type' => 'essay',
        ]);
    }

    public function test_teacher_creates_quiz_with_questions(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store', $class), $this->essayPayload([
                'type' => 'quiz',
                'questions' => $this->quizQuestions(3),
            ]))
            ->assertRedirect();

        $assignment = Assignment::where('class_id', $class->class_id)->first();
        $this->assertNotNull($assignment);
        $this->assertSame(3, $assignment->questions()->count());
    }

    public function test_quiz_without_questions_is_rejected(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store', $class), $this->essayPayload(['type' => 'quiz']))
            ->assertSessionHasErrors('questions');

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_past_due_date_is_rejected_on_create(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store', $class), $this->essayPayload([
                'due_date' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            ]))
            ->assertSessionHasErrors('due_date');
    }

    public function test_teacher_cannot_create_assignment_for_another_teachers_class(): void
    {
        $owner = $this->makeTeacher();
        $intruder = $this->makeTeacher();
        $class = $this->makeClass($owner);

        $this->actingAs($intruder)
            ->post(route('teacher.assignments.store', $class), $this->essayPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_teacher_can_edit_assignment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $assignment = $this->makeEssay($class, ['title' => 'Cũ']);

        $this->actingAs($teacher)
            ->put(route('teacher.assignments.update', $assignment), $this->essayPayload([
                'title' => 'Tiêu đề mới',
                'max_score' => 20,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'assignment_id' => $assignment->assignment_id,
            'title' => 'Tiêu đề mới',
            'max_score' => 20,
        ]);
    }

    public function test_editing_quiz_resyncs_questions_when_no_submissions(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $quiz = $this->makeQuiz($class, 3);

        $this->actingAs($teacher)
            ->put(route('teacher.assignments.update', $quiz), $this->essayPayload([
                'type' => 'quiz',
                'questions' => $this->quizQuestions(1),
            ]))
            ->assertRedirect();

        $this->assertSame(1, $quiz->fresh()->questions()->count());
    }

    public function test_quiz_cannot_be_graded_by_hand(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $quiz = $this->makeQuiz($class);

        $submission = Submission::create([
            'user_id' => $student->user_id,
            'assignment_id' => $quiz->assignment_id,
            'submitted_at' => Carbon::now(),
            'score' => 8,
            'graded_at' => Carbon::now(),
        ]);

        // Quiz tự chấm → chặn chấm tay kể cả POST trực tiếp (T5)
        $this->actingAs($teacher)
            ->post(route('teacher.grading.grade', $submission), ['score' => 3])
            ->assertForbidden();
    }
}
