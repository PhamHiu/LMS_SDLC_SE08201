<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $majorId = $this->route('major')?->major_id;

        return [
            'major_name' => ['required', 'string', 'max:255'],
            'major_code' => ['required', 'string', 'max:20', Rule::unique('majors', 'major_code')->ignore($majorId, 'major_id')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'major_name.required' => 'Vui lòng nhập tên chuyên ngành.',
            'major_code.required' => 'Vui lòng nhập mã chuyên ngành.',
            'major_code.unique' => 'Mã chuyên ngành đã tồn tại.',
        ];
    }
}
