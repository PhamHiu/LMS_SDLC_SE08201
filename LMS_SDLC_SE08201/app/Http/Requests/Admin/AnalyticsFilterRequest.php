<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền đã có ở route middleware (role:ADMIN)
    }

    public function rules(): array
    {
        return [
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
            'major_id'   => 'nullable|exists:majors,major_id',
            'subject_id' => 'nullable|exists:subjects,subject_id',
            'class_id'   => 'nullable|exists:classes,class_id',
            'teacher_id' => 'nullable|exists:users,user_id',
            'student_id' => 'nullable|exists:users,user_id',
        ];
    }
}
