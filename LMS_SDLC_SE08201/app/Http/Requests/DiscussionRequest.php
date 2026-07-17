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

    /**
     * Cắt khoảng trắng đầu/cuối trước khi validate để chặn bình luận rỗng
     * hoặc chỉ gồm dấu cách (Laravel `required` vẫn coi "   " là hợp lệ).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('comment')) {
            $this->merge(['comment' => trim((string) $this->input('comment'))]);
        }
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
