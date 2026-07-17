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
    public function grade(Submission $submission, array $data, ?UploadedFile $resultFile = null): Submission
    {
        $payload = [
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
        ];

        if ($resultFile) {
            $payload['result_file_path'] = $resultFile->store('results', 'public');
        } elseif (!empty($data['annotated_image'])) {
            $image = $data['annotated_image'];
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageName = 'graded_' . $submission->submission_id . '_' . time() . '.png';
            $path = 'results/' . $imageName;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode($image));
            $payload['result_file_path'] = $path;
        }

        $submission->update($payload);

        return $submission;
    }
}
