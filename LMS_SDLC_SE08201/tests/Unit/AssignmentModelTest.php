<?php

namespace Tests\Unit;

use App\Models\Assignment;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssignmentModelTest extends TestCase
{
    public function test_is_quiz_reflects_type(): void
    {
        $quiz = new Assignment(['type' => Assignment::TYPE_QUIZ]);
        $essay = new Assignment(['type' => Assignment::TYPE_ESSAY]);

        $this->assertTrue($quiz->isQuiz());
        $this->assertFalse($essay->isQuiz());
    }

    public function test_is_overdue_true_when_due_date_in_past(): void
    {
        $a = new Assignment();
        $a->due_date = Carbon::now()->subDay();

        $this->assertTrue($a->isOverdue());
    }

    public function test_is_overdue_false_when_due_date_in_future(): void
    {
        $a = new Assignment();
        $a->due_date = Carbon::now()->addDay();

        $this->assertFalse($a->isOverdue());
    }

    public function test_is_overdue_false_when_due_date_null(): void
    {
        $a = new Assignment();

        // Không được ném lỗi khi due_date chưa có (T1: phòng thủ null)
        $this->assertFalse($a->isOverdue());
    }

    public function test_type_label_vietnamese(): void
    {
        $this->assertSame('Trắc nghiệm', (new Assignment(['type' => 'quiz']))->typeLabel());
        $this->assertSame('Tự luận', (new Assignment(['type' => 'essay']))->typeLabel());
    }
}
