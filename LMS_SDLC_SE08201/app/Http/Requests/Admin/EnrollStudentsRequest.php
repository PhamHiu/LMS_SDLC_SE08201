<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,user_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Vui lòng chọn ít nhất một sinh viên.',
        ];
    }
}
