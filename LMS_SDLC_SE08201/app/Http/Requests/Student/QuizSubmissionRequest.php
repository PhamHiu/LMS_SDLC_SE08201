<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class QuizSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStudent();
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['nullable', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Bạn chưa trả lời câu hỏi nào.',
            'answers.*.in' => 'Đáp án không hợp lệ.',
        ];
    }
}
