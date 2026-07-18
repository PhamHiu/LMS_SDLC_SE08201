<?php

namespace App\Services\Shared;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\GradeReportExport;
use App\Exports\AttendanceReportExport;
use App\Exports\CourseReportExport;
use App\Exports\UserReportExport;
use Illuminate\Support\Collection;

class ReportExportService
{
    /**
     * Xuất Excel Điểm số
     */
    public function exportGradesExcel(Collection $data, string $filename = 'Bao_Cao_Diem_So.xlsx')
    {
        return Excel::download(new GradeReportExport($data), $filename);
    }

    /**
     * Xuất Excel Chuyên cần
     */
    public function exportAttendanceExcel(Collection $data, string $filename = 'Bao_Cao_Chuyen_Can.xlsx')
    {
        return Excel::download(new AttendanceReportExport($data), $filename);
    }

    /**
     * Xuất Excel Khóa học
     */
    public function exportCourseExcel(Collection $data, string $filename = 'Bao_Cao_Khoa_Hoc.xlsx')
    {
        return Excel::download(new CourseReportExport($data), $filename);
    }

    /**
     * Xuất Excel Người dùng
     */
    public function exportUserExcel(Collection $data, string $filename = 'Bao_Cao_Nguoi_Dung.xlsx')
    {
        return Excel::download(new UserReportExport($data), $filename);
    }

    /**
     * Xuất PDF chung
     */
    public function exportPdf(string $viewPath, array $viewData, string $filename = 'Bao_Cao.pdf')
    {
        $pdf = Pdf::loadView($viewPath, $viewData);
        // Có thể config orientation, paper size ở đây nếu cần, ví dụ: $pdf->setPaper('a4', 'landscape');
        return $pdf->download($filename);
    }
}
