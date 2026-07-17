<?php

namespace App\Services\Teacher;

use App\Models\Assignment;
use App\Models\ClassRoom;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
    public function create(ClassRoom $class, array $data): Assignment
    {
        return DB::transaction(function () use ($class, $data) {
            $assignment = Assignment::create([
                'class_id' => $class->class_id,
                'title' => $data['title'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'max_score' => $data['max_score'],
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

    /**
     * Cập nhật bài tập. Nếu bài tập ĐÃ có bài nộp thì KHÓA loại bài & bộ câu hỏi
     * (chỉ cho sửa tiêu đề/mô tả/hạn nộp/thang điểm) để tránh làm hỏng dữ liệu
     * đã chấm. Nếu CHƯA có bài nộp thì đồng bộ lại câu hỏi cho quiz.
     */
    public function update(Assignment $assignment, array $data): Assignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $hasSubmissions = $assignment->submissions()->exists();
            $type = $hasSubmissions ? $assignment->type : $data['type'];

            $assignment->update([
                'title' => $data['title'],
                'type' => $type,
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'max_score' => $data['max_score'],
            ]);

            if (! $hasSubmissions) {
                // Đồng bộ lại câu hỏi: xóa hết rồi tạo mới theo dữ liệu gửi lên
                $assignment->questions()->delete();

                if ($type === Assignment::TYPE_QUIZ) {
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
            }

            return $assignment;
        });
    }

    public function delete(Assignment $assignment): void
    {
        $assignment->delete();
    }
}
