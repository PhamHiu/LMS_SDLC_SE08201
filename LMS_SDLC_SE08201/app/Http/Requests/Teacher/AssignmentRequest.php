<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:essay,quiz'],
            'description' => ['nullable', 'string'],
            'due_date' => $this->isMethod('POST') ? ['required', 'date', 'after:now'] : ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            // ( Đăng sửa ) Validate file đính kèm với dung lượng và định dạng cho phép
            'attachment_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,txt,zip,rar', 'max:10240'],
            // ( Đăng sửa ) Validate checkbox cho phép sinh viên xem đáp án
            'show_answers' => ['nullable', 'boolean'],
            // Câu hỏi trắc nghiệm — bắt buộc khi type = quiz
            'questions' => ['required_if:type,quiz', 'array', 'min:1'],
            'questions.*.content' => ['required_with:questions', 'string'],
            'questions.*.option_a' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.option_b' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.option_c' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.option_d' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.correct_answer' => ['required_with:questions', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề bài tập.',
            'due_date.required' => 'Vui lòng chọn hạn nộp.',
            'due_date.after' => 'Hạn nộp phải nằm trong tương lai.',
            'max_score.required' => 'Vui lòng nhập thang điểm.',
            'questions.required_if' => 'Bài Quiz phải có ít nhất 1 câu hỏi.',
            'questions.*.content.required_with' => 'Nội dung câu hỏi không được để trống.',
            'questions.*.option_a.required_with' => 'Vui lòng nhập đáp án A.',
            'questions.*.option_b.required_with' => 'Vui lòng nhập đáp án B.',
            'questions.*.option_c.required_with' => 'Vui lòng nhập đáp án C.',
            'questions.*.option_d.required_with' => 'Vui lòng nhập đáp án D.',
            'questions.*.correct_answer.in' => 'Đáp án đúng phải là A, B, C hoặc D.',
            // ( Đăng sửa ) Custom thông báo lỗi cho phần đính kèm file
            'attachment_file.mimes' => 'File đính kèm chỉ chấp nhận định dạng PDF, Word, TXT, ZIP, RAR.',
            // ( Đăng sửa ) Báo lỗi quá dung lượng
            'attachment_file.max' => 'File đính kèm không được vượt quá 10MB.',
        ];
    }
}
