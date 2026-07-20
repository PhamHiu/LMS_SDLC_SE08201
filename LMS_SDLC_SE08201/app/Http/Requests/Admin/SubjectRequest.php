<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->subject_id;

        return [
            'subject_name' => ['required', 'string', 'max:255'],
            'subject_code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'subject_code')->ignore($subjectId, 'subject_id')],
            'description' => ['nullable', 'string'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => ['exists:majors,major_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_name.required' => 'Vui lòng nhập tên môn học.',
            'subject_code.required' => 'Vui lòng nhập mã môn học.',
            'subject_code.unique' => 'Mã môn học đã tồn tại.',
        ];
    }
}
