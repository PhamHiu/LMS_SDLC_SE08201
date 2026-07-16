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

    public function delete(Assignment $assignment): void
    {
        $assignment->delete();
    }
}
