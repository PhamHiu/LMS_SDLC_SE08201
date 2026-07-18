<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use App\Models\User;
use Illuminate\Support\Carbon;

class UserReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Mã người dùng',
            'Họ tên',
            'Email',
            'Số điện thoại',
            'Vai trò',
            'Chuyên ngành',
            'Ngày tham gia',
        ];
    }

    public function map($user): array
    {
        $roleMap = [
            User::ROLE_ADMIN => 'Quản trị viên',
            User::ROLE_TEACHER => 'Giảng viên',
            User::ROLE_STUDENT => 'Sinh viên',
        ];

        return [
            ++$this->rowNumber,
            $user->user_id,
            $user->full_name,
            $user->email,
            $user->phone ?? '',
            $roleMap[$user->role] ?? $user->role,
            $user->major?->major_name ?? '',
            $user->created_at ? Carbon::parse($user->created_at)->format('d/m/Y') : '',
        ];
    }
}
