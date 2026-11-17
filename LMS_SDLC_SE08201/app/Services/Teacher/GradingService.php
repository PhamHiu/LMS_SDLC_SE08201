<?php

namespace App\Services\Teacher;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;

class GradingService
{
    /**
     * Danh sách nộp bài của một bài tập + các SV chưa nộp + thống kê tỷ lệ nộp bài.
     */
    public function submissionsOverview(Assignment $assignment): array
    {
        $assignment->loadMissing(['classRoom.subject', 'classRoom.students']);

        $submissions = $assignment->submissions()
            ->with('student')
            ->orderByDesc('submitted_at')
            ->get();

        $submittedIds = $submissions->pluck('user_id');
        $notSubmitted = $assignment->classRoom->students()
            ->whereNotIn('users.user_id', $submittedIds)
            ->orderBy('full_name')
            ->get();

        $allStudents = $assignment->classRoom->students()
            ->orderBy('full_name')
            ->get();

        $totalStudents = $allStudents->count();
        $submittedCount = $submissions->count();
        $notSubmittedCount = $notSubmitted->count();
        $submissionRate = $totalStudents > 0 ? round(($submittedCount / $totalStudents) * 100, 1) : 0;

        $gradedCount = $submissions->filter(fn ($s) => $s->isGraded())->count();
        $pendingCount = $submissions->filter(fn ($s) => ! $s->isGraded())->count();
        $gradingRate = $submittedCount > 0 ? round(($gradedCount / $submittedCount) * 100, 1) : 0;

        // Tạo danh sách tổng hợp toàn bộ sinh viên kèm trạng thái
        $allStudentRows = $allStudents->map(function ($student) use ($submissions) {
            $sub = $submissions->firstWhere('user_id', $student->user_id);
            return [
                'student' => $student,
                'has_submitted' => $sub !== null,
                'submission' => $sub,
                'is_graded' => $sub?->isGraded() ?? false,
                'score' => $sub?->score,
                'submitted_at' => $sub?->submitted_at,
                'is_late' => $sub?->submitted_at && $sub->submitted_at->gt($sub->assignment->due_date),
            ];
        });

        return [
            'submissions' => $submissions,
            'notSubmitted' => $notSubmitted,
            'allStudentRows' => $allStudentRows,
            'totalStudents' => $totalStudents,
            'submittedCount' => $submittedCount,
            'notSubmittedCount' => $notSubmittedCount,
            'submissionRate' => $submissionRate,
            'gradedCount' => $gradedCount,
            'pendingCount' => $pendingCount,
            'gradingRate' => $gradingRate,
        ];
    }

    /**
     * Chấm điểm bài tự luận: nhập điểm + nhận xét + file feedback (tùy chọn).
     */
    public function grade(Submission $submission, array $data, ?UploadedFile $otherFile = null): Submission
    {
        $payload = [
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
        ];
        
        if ($otherFile) {
            $payload['other_file_path'] = $otherFile->store('results/others', 'public');
        }

        // Xử lý lưu ảnh vẽ tay (nếu có)
        if (!empty($data['canvas_image_data'])) {
            $imageData = $data['canvas_image_data'];
            // Xóa tiền tố data:image/png;base64,
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]); // thường là png
                $decodedData = base64_decode($imageData);

                if ($decodedData !== false) {
                    $filename = 'results/canvas/submission_' . $submission->submission_id . '_' . time() . '.' . $type;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decodedData);
                    // Ghi đè file bài gốc theo yêu cầu: submit_file_path chứa bài đã chấm
                    $payload['submit_file_path'] = $filename;
                }
            }
        }

        $submission->update($payload);

        return $submission;
    }
}
