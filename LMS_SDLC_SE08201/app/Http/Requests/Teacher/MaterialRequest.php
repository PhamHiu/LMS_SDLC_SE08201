<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,subject_id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,mp4,webm', 'max:51200'], // 50MB
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.required' => 'Vui lòng chọn môn học.',
            'title.required' => 'Vui lòng nhập tiêu đề học liệu.',
            'file.required' => 'Vui lòng chọn file học liệu.',
            'file.mimes' => 'Định dạng cho phép: PDF, Word, PowerPoint, Excel, ZIP, MP4.',
            'file.max' => 'Dung lượng file tối đa 50MB.',
        ];
    }
}
