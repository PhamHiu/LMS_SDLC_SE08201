<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class EssaySubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStudent();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,zip,rar', 'max:20480'], // 20MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file bài làm.',
            'file.mimes' => 'Định dạng cho phép: PDF, Word, ZIP, RAR.',
            'file.max' => 'Dung lượng file tối đa 20MB.',
        ];
    }
}
