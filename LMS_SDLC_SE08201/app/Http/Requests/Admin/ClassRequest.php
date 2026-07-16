<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'class_name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,user_id'], // giảng viên
            'subject_id' => ['required', 'exists:subjects,subject_id'],
            'major_id' => ['required', 'exists:majors,major_id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:60'],
            'status' => ['required', 'in:0,1,2'],
            'room' => ['nullable', 'string', 'max:100'],
            'shift' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_name.required' => 'Vui lòng nhập tên lớp.',
            'user_id.required' => 'Vui lòng chọn giảng viên phụ trách.',
            'subject_id.required' => 'Vui lòng chọn môn học.',
            'major_id.required' => 'Vui lòng chọn chuyên ngành.',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'total_sessions.required' => 'Vui lòng nhập tổng số buổi học.',
        ];
    }
}
