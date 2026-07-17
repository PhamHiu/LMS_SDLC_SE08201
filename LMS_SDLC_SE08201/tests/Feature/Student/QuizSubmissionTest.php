<?php

namespace Tests\Feature\Student;

use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\SeedsDomain;
use Tests\TestCase;

class QuizSubmissionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDomain;

    public function test_quiz_is_auto_graded_by_ratio(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $quiz = $this->makeQuiz($class, 2); // 2 câu, đáp án đúng đều 'A', max 10

        $answers = [];
        $questions = $quiz->questions->values();
        $answers[$questions[0]->question_id] = 'A'; // đúng
        $answers[$questions[1]->question_id] = 'B'; // sai

        $this->actingAs($student)
            ->post(route('student.assignments.quiz.submit', $quiz), ['answers' => $answers])
            ->assertRedirect(route('student.assignments.show', $quiz));

        $submission = Submission::where('user_id', $student->user_id)
            ->where('assignment_id', $quiz->assignment_id)->first();

        $this->assertNotNull($submission);
        $this->assertEquals(5.0, $submission->score);        // 1/2 * 10
        $this->assertNotNull($submission->graded_at);
        $this->assertSame(2, $submission->details()->count()); // lưu đủ đáp án từng câu
    }

    public function test_all_correct_gets_full_score(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $quiz = $this->makeQuiz($class, 2);

        $answers = $quiz->questions->mapWithKeys(fn ($q) => [$q->question_id => 'A'])->all();

        $this->actingAs($student)->post(route('student.assignments.quiz.submit', $quiz), ['answers' => $answers]);

        $this->assertEquals(10.0, Submission::first()->score);
    }

    public function test_cannot_submit_quiz_twice(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $quiz = $this->makeQuiz($class, 1);

        Submission::create([
            'user_id' => $student->user_id,
            'assignment_id' => $quiz->assignment_id,
            'submitted_at' => Carbon::now(),
            'score' => 10,
            'graded_at' => Carbon::now(),
        ]);

        $answers = $quiz->questions->mapWithKeys(fn ($q) => [$q->question_id => 'A'])->all();

        $this->actingAs($student)
            ->post(route('student.assignments.quiz.submit', $quiz), ['answers' => $answers])
            ->assertStatus(422);

        $this->assertSame(1, Submission::count());
    }

    public function test_cannot_submit_quiz_after_deadline(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $quiz = $this->makeQuiz($class, 1, ['due_date' => Carbon::now()->subDay()]);

        $answers = $quiz->questions->mapWithKeys(fn ($q) => [$q->question_id => 'A'])->all();

        $this->actingAs($student)
            ->post(route('student.assignments.quiz.submit', $quiz), ['answers' => $answers])
            ->assertStatus(422);
    }

    public function test_student_not_in_class_cannot_submit(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $outsider = $this->makeStudent(); // KHÔNG ghi danh
        $quiz = $this->makeQuiz($class, 1);

        $answers = $quiz->questions->mapWithKeys(fn ($q) => [$q->question_id => 'A'])->all();

        $this->actingAs($outsider)
            ->post(route('student.assignments.quiz.submit', $quiz), ['answers' => $answers])
            ->assertForbidden();
    }
}
