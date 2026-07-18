<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use App\Models\ClassRoom;

class CourseReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Mã lớp',
            'Tên lớp',
            'Môn học',
            'Chuyên ngành',
            'Giảng viên',
            'Sĩ số',
            'Số buổi học',
            'Trạng thái',
        ];
    }

    public function map($class): array
    {
        $statusMap = [
            ClassRoom::STATUS_PENDING => 'Chờ mở',
            ClassRoom::STATUS_ACTIVE => 'Đang học',
            ClassRoom::STATUS_CLOSED => 'Đã đóng',
        ];

        return [
            ++$this->rowNumber,
            $class->class_id,
            $class->class_name,
            $class->subject?->subject_name ?? '',
            $class->major?->major_name ?? '',
            $class->teacher?->full_name ?? '',
            $class->students_count ?? 0,
            $class->sessions_count ?? 0,
            $statusMap[$class->status] ?? $class->status,
        ];
    }
}
