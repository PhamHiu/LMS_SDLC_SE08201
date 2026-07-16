<?php

namespace App\Services\Student;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionDetail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    /**
     * Danh sách bài tập của sinh viên kèm trạng thái nộp.
     */
    public function assignmentsFor(User $student, ?string $type = null)
    {
        $classIds = $student->enrollments()->pluck('class_id');

        return Assignment::with('classRoom.subject')
            ->whereIn('class_id', $classIds)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('due_date')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($assignment) use ($student) {
                $assignment->my_submission = Submission::where('user_id', $student->user_id)
                    ->where('assignment_id', $assignment->assignment_id)
                    ->first();

                return $assignment;
            });
    }

    public function submissionOf(User $student, Assignment $assignment): ?Submission
    {
        return Submission::with('details.question')
            ->where('user_id', $student->user_id)
            ->where('assignment_id', $assignment->assignment_id)
            ->first();
    }

    /**
     * Nộp bài tự luận (upload file). Cho phép nộp lại trước hạn nếu chưa chấm.
     */
    public function submitEssay(User $student, Assignment $assignment, UploadedFile $file): Submission
    {
        abort_if($assignment->isQuiz(), 422, 'Bài này là Quiz, không nộp file.');
        abort_if($assignment->isOverdue(), 422, 'Đã quá hạn nộp bài.');

        $existing = $this->submissionOf($student, $assignment);
        abort_if($existing && $existing->isGraded(), 422, 'Bài đã được chấm, không thể nộp lại.');

        $path = $file->store('submissions', 'public');

        return Submission::updateOrCreate(
            ['user_id' => $student->user_id, 'assignment_id' => $assignment->assignment_id],
            ['submit_file_path' => $path, 'submitted_at' => now()],
        );
    }

    /**
     * Nộp bài trắc nghiệm: lưu chi tiết đáp án + hệ thống TỰ ĐỘNG CHẤM ĐIỂM.
     */
    public function submitQuiz(User $student, Assignment $assignment, array $answers): Submission
    {
        abort_unless($assignment->isQuiz(), 422, 'Bài này không phải Quiz.');
        abort_if($assignment->isOverdue(), 422, 'Đã quá hạn làm bài.');
        abort_if($this->submissionOf($student, $assignment) !== null, 422, 'Bạn đã làm bài Quiz này rồi.');

        return DB::transaction(function () use ($student, $assignment, $answers) {
            $submission = Submission::create([
                'user_id' => $student->user_id,
                'assignment_id' => $assignment->assignment_id,
                'submitted_at' => now(),
            ]);

            $questions = $assignment->questions;
            $correct = 0;

            foreach ($questions as $question) {
                $selected = $answers[$question->question_id] ?? null;

                SubmissionDetail::create([
                    'submission_id' => $submission->submission_id,
                    'question_id' => $question->question_id,
                    'selected_option' => $selected,
                ]);

                if ($selected === $question->correct_answer) {
                    $correct++;
                }
            }

            // Tự động chấm điểm theo tỷ lệ câu đúng
            $score = $questions->count() > 0
                ? round($correct / $questions->count() * $assignment->max_score, 2)
                : 0;

            $submission->update(['score' => $score, 'graded_at' => now()]);

            return $submission;
        });
    }
}
