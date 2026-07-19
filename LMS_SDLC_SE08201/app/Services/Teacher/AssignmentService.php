<?php

namespace App\Services\Teacher;

use App\Models\Assignment;
use App\Models\ClassRoom;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssignmentService
{
    public function authorizeAssignment(User $teacher, Assignment $assignment): void
    {
        if ($assignment->classRoom->user_id !== $teacher->user_id) {
            abort(403, 'Bài tập không thuộc lớp bạn phụ trách.');
        }
    }

    /**
     * Toàn bộ bài tập của các lớp giảng viên phụ trách.
     */
    public function listFor(User $teacher)
    {
        $classIds = $teacher->teachingClasses()->pluck('class_id');

        return Assignment::with('classRoom.subject')
            ->withCount(['submissions', 'questions'])
            ->whereIn('class_id', $classIds)
            ->orderByDesc('assignment_id')
            ->paginate(10);
    }

    /**
     * Tạo bài tập; nếu là Quiz thì tạo kèm bộ câu hỏi (1 transaction).
     */
    // ( Đăng sửa ) Bổ sung xử lý nhận thêm tham số UploadedFile $attachmentFile
    public function create(ClassRoom $class, array $data, ?UploadedFile $attachmentFile = null): Assignment
    {
        return DB::transaction(function () use ($class, $data, $attachmentFile) {
            // ( Đăng sửa ) Lưu trữ file nếu giáo viên có tải lên
            $attachmentPath = null;
            if ($attachmentFile) {
                $attachmentPath = $attachmentFile->store('assignments/attachments', 'public');
            }

            $assignment = Assignment::create([
                'class_id' => $class->class_id,
                'title' => $data['title'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'max_score' => $data['max_score'],
                'show_answers' => $data['show_answers'] ?? false,
                'attachment_path' => $attachmentPath,
            ]);

            if ($data['type'] === Assignment::TYPE_QUIZ) {
                foreach ($data['questions'] as $q) {
                    Question::create([
                        'assignment_id' => $assignment->assignment_id,
                        'content' => $q['content'],
                        'option_a' => $q['option_a'],
                        'option_b' => $q['option_b'],
                        'option_c' => $q['option_c'],
                        'option_d' => $q['option_d'],
                        'correct_answer' => $q['correct_answer'],
                    ]);
                }
            }

            return $assignment;
        });
    }

    // ( Đăng sửa ) Thêm hàm update để xử lý lưu thay đổi và thay thế file cũ nếu có
    public function update(Assignment $assignment, array $data, ?UploadedFile $attachmentFile = null): Assignment
    {
        return DB::transaction(function () use ($assignment, $data, $attachmentFile) {
            $updateData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'max_score' => $data['max_score'],
                'show_answers' => $data['show_answers'] ?? false,
            ];

            if ($attachmentFile) {
                if ($assignment->attachment_path) {
                    Storage::disk('public')->delete($assignment->attachment_path);
                }
                $updateData['attachment_path'] = $attachmentFile->store('assignments/attachments', 'public');
            }

            // Lưu ý: Type không được phép thay đổi sau khi tạo để tránh xung đột cấu trúc
            $assignment->update($updateData);

            // Cập nhật câu hỏi nếu là Quiz
            if ($assignment->type === Assignment::TYPE_QUIZ && isset($data['questions'])) {
                // Xóa câu hỏi cũ
                $assignment->questions()->delete();
                
                // Tạo câu hỏi mới
                foreach ($data['questions'] as $q) {
                    Question::create([
                        'assignment_id' => $assignment->assignment_id,
                        'content' => $q['content'],
                        'option_a' => $q['option_a'],
                        'option_b' => $q['option_b'],
                        'option_c' => $q['option_c'],
                        'option_d' => $q['option_d'],
                        'correct_answer' => $q['correct_answer'],
                    ]);
                }
            }

            return $assignment;
        });
    }

    public function delete(Assignment $assignment): void
    {
        if ($assignment->attachment_path) {
            Storage::disk('public')->delete($assignment->attachment_path);
        }
        $assignment->delete();
    }
}
