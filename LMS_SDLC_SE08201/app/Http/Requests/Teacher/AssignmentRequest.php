<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:essay,quiz'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date', 'after:now'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            // Câu hỏi trắc nghiệm — bắt buộc khi type = quiz
            'questions' => ['required_if:type,quiz', 'array', 'min:1'],
            'questions.*.content' => ['required_with:questions', 'string'],
            'questions.*.option_a' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.option_b' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.option_c' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.option_d' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.correct_answer' => ['required_with:questions', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề bài tập.',
            'due_date.required' => 'Vui lòng chọn hạn nộp.',
            'due_date.after' => 'Hạn nộp phải nằm trong tương lai.',
            'max_score.required' => 'Vui lòng nhập thang điểm.',
            'questions.required_if' => 'Bài Quiz phải có ít nhất 1 câu hỏi.',
            'questions.*.content.required_with' => 'Nội dung câu hỏi không được để trống.',
            'questions.*.correct_answer.in' => 'Đáp án đúng phải là A, B, C hoặc D.',
        ];
    }
}
