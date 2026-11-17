<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'questions';
    protected $primaryKey = 'question_id';
    public $timestamps = false;

    public const TYPE_MCQ = 'mcq';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_FILL_BLANK = 'fill_blank';
    public const TYPE_CODE_READING = 'code_reading';

    public const TYPES = [
        self::TYPE_MCQ,
        self::TYPE_TRUE_FALSE,
        self::TYPE_FILL_BLANK,
        self::TYPE_CODE_READING,
    ];

    protected $fillable = [
        'assignment_id', 'content', 'question_type',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer', 'explanation', 'correct_text',
    ];

    protected $attributes = [
        'question_type' => self::TYPE_MCQ,
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id', 'assignment_id');
    }

    /** Trả về mảng đáp án [A => ..., B => ...], BỎ các lựa chọn rỗng (phục vụ đúng/sai 2 lựa chọn). */
    public function options(): array
    {
        $all = [
            'A' => $this->option_a,
            'B' => $this->option_b,
            'C' => $this->option_c,
            'D' => $this->option_d,
        ];

        return array_filter($all, fn ($v) => $v !== null && trim((string) $v) !== '');
    }

    public function isChoiceType(): bool
    {
        return in_array($this->question_type, [self::TYPE_MCQ, self::TYPE_TRUE_FALSE, self::TYPE_CODE_READING], true);
    }

    public function isFillBlank(): bool
    {
        return $this->question_type === self::TYPE_FILL_BLANK;
    }

    public function typeLabel(): string
    {
        return match ($this->question_type) {
            self::TYPE_TRUE_FALSE => 'Đúng/Sai',
            self::TYPE_FILL_BLANK => 'Điền khuyết',
            self::TYPE_CODE_READING => 'Đọc code',
            default => 'Trắc nghiệm',
        };
    }

    /** Chuẩn hóa chuỗi để so khớp đáp án điền khuyết (bỏ hoa/thường, dấu cách thừa). */
    public static function normalizeText(string $text): string
    {
        $text = trim(mb_strtolower($text));
        $text = preg_replace('/\s+/u', ' ', $text);

        return (string) $text;
    }

    /**
     * Chấm một câu trả lời của sinh viên.
     * - Dạng chọn (mcq/true_false/code_reading): so khớp chữ cái với correct_answer.
     * - Dạng điền khuyết: so khớp chuẩn hóa với correct_text (nhiều đáp án cách nhau bằng |).
     */
    public function isAnswerCorrect(?string $selectedOption, ?string $answerText): bool
    {
        if ($this->isFillBlank()) {
            if ($answerText === null || trim($answerText) === '' || $this->correct_text === null) {
                return false;
            }
            $given = self::normalizeText($answerText);
            foreach (explode('|', $this->correct_text) as $accepted) {
                if ($given === self::normalizeText($accepted)) {
                    return true;
                }
            }

            return false;
        }

        return $selectedOption !== null && $selectedOption === $this->correct_answer;
    }
}
