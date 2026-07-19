<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        $maxScore = $this->route('submission')?->assignment?->max_score ?? 10;

        return [
            'score' => ['required', 'numeric', 'min:0', "max:{$maxScore}"],
            'feedback' => ['nullable', 'string'],
            'other_file' => ['nullable', 'file', 'max:10240'],
            'canvas_image_data' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'Vui lòng nhập điểm.',
            'score.max' => 'Điểm không được vượt quá thang điểm của bài tập.',
            'score.min' => 'Điểm không được âm.',
        ];
    }
}
