<?php

namespace Tests\Feature\Student;

use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SeedsDomain;
use Tests\TestCase;

class EssaySubmissionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDomain;

    public function test_student_submits_essay_file(): void
    {
        Storage::fake('public');
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $essay = $this->makeEssay($class);

        $file = UploadedFile::fake()->create('baitap.pdf', 120, 'application/pdf');

        $this->actingAs($student)
            ->post(route('student.assignments.essay.submit', $essay), ['file' => $file])
            ->assertRedirect();

        $submission = Submission::where('user_id', $student->user_id)
            ->where('assignment_id', $essay->assignment_id)->first();

        $this->assertNotNull($submission);
        $this->assertNotNull($submission->submit_file_path);
        Storage::disk('public')->assertExists($submission->submit_file_path);
    }

    public function test_cannot_submit_essay_after_deadline(): void
    {
        Storage::fake('public');
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $essay = $this->makeEssay($class, ['due_date' => Carbon::now()->subDay()]);

        $this->actingAs($student)
            ->post(route('student.assignments.essay.submit', $essay), [
                'file' => UploadedFile::fake()->create('muon.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422);
    }

    public function test_cannot_resubmit_after_graded(): void
    {
        Storage::fake('public');
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $student = $this->makeStudent();
        $this->enroll($student, $class);
        $essay = $this->makeEssay($class);

        Submission::create([
            'user_id' => $student->user_id,
            'assignment_id' => $essay->assignment_id,
            'submit_file_path' => 'submissions/cu.pdf',
            'submitted_at' => Carbon::now()->subHour(),
            'graded_at' => Carbon::now(),
            'score' => 9,
        ]);

        $this->actingAs($student)
            ->post(route('student.assignments.essay.submit', $essay), [
                'file' => UploadedFile::fake()->create('moi.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422);
    }
}
