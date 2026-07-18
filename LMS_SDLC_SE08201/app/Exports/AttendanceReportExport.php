<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Ngày/Buổi',
            'Sinh viên',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($attendance): array
    {
        $statusMap = [
            'P' => 'Có mặt',
            'A' => 'Vắng',
            'L' => 'Muộn',
        ];

        return [
            ++$this->rowNumber,
            $attendance->session?->classRoom?->class_name ?? '',
            $attendance->attendance_time ? Carbon::parse($attendance->attendance_time)->format('d/m/Y') : '',
            $attendance->student?->full_name ?? '',
            $statusMap[$attendance->status] ?? $attendance->status,
            $attendance->remarks ?? '',
        ];
    }
}
