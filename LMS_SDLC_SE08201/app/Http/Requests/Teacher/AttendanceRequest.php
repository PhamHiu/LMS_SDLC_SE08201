<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array'],
            'attendances.*.status' => ['required', 'in:P,A,L'],
            'attendances.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendances.required' => 'Không có dữ liệu điểm danh.',
            'attendances.*.status.in' => 'Trạng thái điểm danh không hợp lệ.',
        ];
    }
}
