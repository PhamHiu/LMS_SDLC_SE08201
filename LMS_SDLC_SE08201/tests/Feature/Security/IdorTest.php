<?php

namespace Tests\Feature\Security;

use App\Models\Material;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\SeedsDomain;
use Tests\TestCase;

class IdorTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDomain;

    public function test_student_cannot_view_assignment_of_class_not_enrolled(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass($teacher);
        $assignment = $this->makeEssay($class);
        $outsider = $this->makeStudent(); // không ghi danh lớp này

        $this->actingAs($outsider)
            ->get(route('student.assignments.show', $assignment))
            ->assertForbidden();
    }

    public function test_teacher_cannot_grade_submission_of_another_teachers_class(): void
    {
        $owner = $this->makeTeacher();
        $classA = $this->makeClass($owner);
        $student = $this->makeStudent();
        $this->enroll($student, $classA);
        $essay = $this->makeEssay($classA);

        $submission = Submission::create([
            'user_id' => $student->user_id,
            'assignment_id' => $essay->assignment_id,
            'submit_file_path' => 'submissions/x.pdf',
            'submitted_at' => Carbon::now(),
        ]);

        $intruder = $this->makeTeacher();

        $this->actingAs($intruder)
            ->post(route('teacher.grading.grade', $submission), ['score' => 5])
            ->assertForbidden();
    }

    public function test_teacher_cannot_download_material_of_subject_not_taught(): void
    {
        // GV A dạy lớp thuộc môn A → có học liệu môn A
        $teacherA = $this->makeTeacher();
        $classA = $this->makeClass($teacherA);
        $material = Material::create([
            'subject_id' => $classA->subject_id,
            'user_id' => $teacherA->user_id,
            'title' => 'Giáo trình A',
            'description' => null,
            'file_path' => 'materials/a.pdf',
        ]);

        // GV B dạy môn khác → không được tải học liệu môn A (T10 IDOR)
        $teacherB = $this->makeTeacher();
        $this->makeClass($teacherB);

        $this->actingAs($teacherB)
            ->get(route('teacher.materials.download', $material))
            ->assertForbidden();
    }
}
