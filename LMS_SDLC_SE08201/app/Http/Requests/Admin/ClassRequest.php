<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation()
    {
        if ($this->has('selected_slots') && is_string($this->selected_slots)) {
            $this->merge([
                'selected_slots' => json_decode($this->selected_slots, true)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'class_name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,user_id'], // giảng viên
            'subject_id' => ['required', 'exists:subjects,subject_id'],
            'major_id' => ['required', 'exists:majors,major_id'],
            'start_date' => [$this->isMethod('post') ? 'required' : 'nullable', 'date'],
            'total_sessions' => [$this->isMethod('post') ? 'required' : 'nullable', 'integer', 'min:1', 'max:60'],
            'status' => ['required', 'in:0,1,2'],
            'room_id' => ['nullable', 'exists:rooms,room_id'],
            'selected_slots' => [$this->isMethod('post') ? 'required' : 'nullable', 'array'],
            'selected_slots.*.day_of_week' => ['required_with:selected_slots', 'integer', 'min:1', 'max:7'],
            'selected_slots.*.slot_id' => ['required_with:selected_slots', 'exists:slots,slot_id'],
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
            'total_sessions.required' => 'Vui lòng nhập tổng số buổi học.',
            'selected_slots.required' => 'Vui lòng chọn ít nhất một buổi học trong thời khóa biểu.',
        ];
    }
}
