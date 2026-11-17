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
            // Câu hỏi quiz — bắt buộc khi type = quiz (hỗ trợ nhiều dạng bài)
            'questions' => ['required_if:type,quiz', 'array', 'min:1'],
            'questions.*.content' => ['required_with:questions', 'string'],
            'questions.*.question_type' => ['nullable', 'in:mcq,true_false,fill_blank,code_reading'],
            'questions.*.option_a' => ['nullable', 'string', 'max:255'],
            'questions.*.option_b' => ['nullable', 'string', 'max:255'],
            'questions.*.option_c' => ['nullable', 'string', 'max:255'],
            'questions.*.option_d' => ['nullable', 'string', 'max:255'],
            'questions.*.correct_answer' => ['nullable', 'in:A,B,C,D'],
            'questions.*.correct_text' => ['nullable', 'string', 'max:500'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Kiểm tra bắt buộc theo từng dạng bài (options cho dạng chọn; đáp án text cho điền khuyết). */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('questions', []) as $i => $q) {
                $type = $q['question_type'] ?? 'mcq';

                if ($type === 'fill_blank') {
                    if (trim((string) ($q['correct_text'] ?? '')) === '') {
                        $validator->errors()->add("questions.$i.correct_text", 'Vui lòng nhập đáp án đúng cho câu điền khuyết.');
                    }
                    continue;
                }

                if ($type === 'true_false') {
                    if (! in_array($q['correct_answer'] ?? '', ['A', 'B'], true)) {
                        $validator->errors()->add("questions.$i.correct_answer", 'Câu đúng/sai phải chọn đáp án A (Đúng) hoặc B (Sai).');
                    }
                    continue;
                }

                // mcq và code_reading: cần đủ 4 lựa chọn + đáp án A-D
                foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $opt) {
                    if (trim((string) ($q[$opt] ?? '')) === '') {
                        $validator->errors()->add("questions.$i.$opt", 'Vui lòng nhập đủ 4 đáp án cho câu trắc nghiệm.');
                    }
                }
                if (! in_array($q['correct_answer'] ?? '', ['A', 'B', 'C', 'D'], true)) {
                    $validator->errors()->add("questions.$i.correct_answer", 'Đáp án đúng phải là A, B, C hoặc D.');
                }
            }
        });
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
            // ( Đăng sửa ) Custom thông báo lỗi cho phần đính kèm file
            'attachment_file.mimes' => 'File đính kèm chỉ chấp nhận định dạng PDF, Word, TXT, ZIP, RAR.',
            // ( Đăng sửa ) Báo lỗi quá dung lượng
            'attachment_file.max' => 'File đính kèm không được vượt quá 10MB.',
        ];
    }
}
