<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dùng chung: Admin gửi thông báo toàn hệ thống (class_id = null),
 * Teacher gửi thông báo nội bộ lớp (class_id bắt buộc).
 */
class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'class_id' => [
                $this->user()->isTeacher() ? 'required' : 'nullable',
                'exists:classes,class_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề thông báo.',
            'body.required' => 'Vui lòng nhập nội dung thông báo.',
            'class_id.required' => 'Vui lòng chọn lớp nhận thông báo.',
        ];
    }
}
