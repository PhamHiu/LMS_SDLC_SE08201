<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class GradeReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private Collection $data;
    private int $rowNumber = 0;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Lớp',
            'Môn',
            'Sinh viên',
            'Bài tập',
            'Điểm',
            'Điểm tối đa',
            'Ngày chấm',
        ];
    }

    public function map($submission): array
    {
        return [
            ++$this->rowNumber,
            $submission->assignment?->classRoom?->class_name ?? '',
            $submission->assignment?->classRoom?->subject?->subject_name ?? '',
            $submission->student?->full_name ?? '',
            $submission->assignment?->title ?? '',
            $submission->score ?? '',
            $submission->assignment?->max_score ?? '',
            $submission->graded_at ? Carbon::parse($submission->graded_at)->format('d/m/Y H:i') : '',
        ];
    }
}
