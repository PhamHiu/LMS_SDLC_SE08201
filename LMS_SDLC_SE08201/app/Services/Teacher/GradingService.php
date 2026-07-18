<?php

namespace App\Services\Teacher;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;

class GradingService
{
    /**
     * Danh sách nộp bài của một bài tập + các SV chưa nộp.
     */
    public function submissionsOverview(Assignment $assignment): array
    {
        $submissions = $assignment->submissions()
            ->with('student')
            ->orderByDesc('submitted_at')
            ->get();

        $submittedIds = $submissions->pluck('user_id');
        $notSubmitted = $assignment->classRoom->students()
            ->whereNotIn('users.user_id', $submittedIds)
            ->orderBy('full_name')
            ->get();

        return [
            'submissions' => $submissions,
            'notSubmitted' => $notSubmitted,
        ];
    }

    /**
     * Chấm điểm bài tự luận: nhập điểm + nhận xét + file feedback (tùy chọn).
     */
    public function grade(Submission $submission, array $data, ?UploadedFile $submitFile = null, ?UploadedFile $otherFile = null): Submission
    {
        $payload = [
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
        ];

        if ($submitFile) {
            $payload['submit_file_path'] = $submitFile->store('results', 'public');
        }
        
        if ($otherFile) {
            $payload['other_file_path'] = $otherFile->store('results/others', 'public');
        }

        $submission->update($payload);

        return $submission;
    }
}
