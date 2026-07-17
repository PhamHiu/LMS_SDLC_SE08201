<?php

namespace Tests\Support;

use App\Models\Assignment;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Helper tạo nhanh dữ liệu miền (dự án không dùng factory — seed thủ công).
 * Dùng bộ đếm tĩnh để mã/email luôn duy nhất trong 1 lần chạy test.
 */
trait SeedsDomain
{
    protected static int $seedCounter = 0;

    protected function nextSeq(): int
    {
        return ++static::$seedCounter;
    }

    protected function makeMajor(array $attrs = []): Major
    {
        $n = $this->nextSeq();

        return Major::create(array_merge([
            'major_name' => 'Ngành '.$n,
            'major_code' => 'MJ'.$n,
            'description' => null,
        ], $attrs));
    }

    protected function makeSubject(array $attrs = []): Subject
    {
        $n = $this->nextSeq();

        return Subject::create(array_merge([
            'subject_code' => 'SUB'.$n,
            'subject_name' => 'Môn học '.$n,
            'description' => null,
        ], $attrs));
    }

    protected function makeUser(string $role, array $attrs = []): User
    {
        $n = $this->nextSeq();

        return User::create(array_merge([
            'role' => $role,
            'email' => strtolower($role).$n.'@test.vn',
            'hashpassword' => Hash::make('password'),
            'full_name' => ucfirst(strtolower($role)).' '.$n,
            'gender' => 1,
        ], $attrs));
    }

    protected function makeAdmin(array $attrs = []): User
    {
        return $this->makeUser(User::ROLE_ADMIN, $attrs);
    }

    protected function makeTeacher(array $attrs = []): User
    {
        return $this->makeUser(User::ROLE_TEACHER, $attrs);
    }

    protected function makeStudent(array $attrs = []): User
    {
        return $this->makeUser(User::ROLE_STUDENT, $attrs);
    }

    protected function makeClass(User $teacher, array $attrs = []): ClassRoom
    {
        $n = $this->nextSeq();
        $subject = $attrs['subject'] ?? $this->makeSubject();
        $major = $attrs['major'] ?? $this->makeMajor();
        unset($attrs['subject'], $attrs['major']);

        return ClassRoom::create(array_merge([
            'user_id' => $teacher->user_id,
            'subject_id' => $subject->subject_id,
            'major_id' => $major->major_id,
            'class_name' => 'CLS'.$n,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeeks(10),
            'total_sessions' => 10,
            'status' => ClassRoom::STATUS_ACTIVE,
        ], $attrs));
    }

    protected function enroll(User $student, ClassRoom $class): Enrollment
    {
        return Enrollment::create([
            'user_id' => $student->user_id,
            'class_id' => $class->class_id,
            'enrolled_at' => Carbon::now(),
        ]);
    }

    protected function makeEssay(ClassRoom $class, array $attrs = []): Assignment
    {
        $n = $this->nextSeq();

        return Assignment::create(array_merge([
            'class_id' => $class->class_id,
            'title' => 'Essay '.$n,
            'type' => Assignment::TYPE_ESSAY,
            'description' => 'Mô tả',
            'due_date' => Carbon::now()->addDays(3),
            'max_score' => 10,
        ], $attrs));
    }

    /**
     * Tạo quiz kèm câu hỏi. Mỗi câu đáp án đúng là 'A' để test tự chấm dễ tính.
     */
    protected function makeQuiz(ClassRoom $class, int $numQuestions = 2, array $attrs = []): Assignment
    {
        $n = $this->nextSeq();

        $quiz = Assignment::create(array_merge([
            'class_id' => $class->class_id,
            'title' => 'Quiz '.$n,
            'type' => Assignment::TYPE_QUIZ,
            'description' => 'Mô tả',
            'due_date' => Carbon::now()->addDays(3),
            'max_score' => 10,
        ], $attrs));

        for ($i = 0; $i < $numQuestions; $i++) {
            Question::create([
                'assignment_id' => $quiz->assignment_id,
                'content' => 'Câu hỏi '.($i + 1),
                'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
                'correct_answer' => 'A',
            ]);
        }

        return $quiz->load('questions');
    }
}
