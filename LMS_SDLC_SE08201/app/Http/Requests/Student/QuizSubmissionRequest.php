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
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'in:A,B,C,D'],
            'answer_text' => ['nullable', 'array'],
            'answer_text.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.*.in' => 'Đáp án không hợp lệ.',
            'answer_text.*.max' => 'Câu trả lời quá dài (tối đa 500 ký tự).',
        ];
    }
}
