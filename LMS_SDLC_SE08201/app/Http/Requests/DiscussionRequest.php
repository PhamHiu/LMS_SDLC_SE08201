<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dùng chung cho Teacher & Student khi bình luận dưới bài tập.
 */
class DiscussionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:discussions,discussion_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Vui lòng nhập nội dung bình luận.',
            'comment.max' => 'Bình luận tối đa 2000 ký tự.',
        ];
    }
}
