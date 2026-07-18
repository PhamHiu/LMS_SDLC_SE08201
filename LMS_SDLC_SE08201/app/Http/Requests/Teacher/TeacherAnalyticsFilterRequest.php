<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class TeacherAnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền role:TEACHER đã check qua route middleware
    }

    public function rules(): array
    {
        return [
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'class_id'  => 'nullable|exists:classes,class_id',
        ];
    }
}
