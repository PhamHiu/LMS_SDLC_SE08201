<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\TeacherAnalyticsFilterRequest;
use App\Services\Teacher\TeacherAnalyticsService;
use App\Services\Shared\ReportExportService;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private readonly TeacherAnalyticsService $analyticsService,
        private readonly ReportExportService $exportService
    ) {}

    public function exportGrades(TeacherAnalyticsFilterRequest $request)
    {
        $teacher = $request->user();
        $filters = $request->validated();
        $stats = $this->analyticsService->getGradeStats($teacher, $filters);
        $data = $stats['data'];

        if ($request->query('format') === 'pdf') {
            $viewData = ['data' => $data, 'date' => Carbon::now()->format('d/m/Y')];
            return $this->exportService->exportPdf('teacher.reports.pdf.grades', $viewData, 'Bao_Cao_Diem_So_GV.pdf');
        }

        return $this->exportService->exportGradesExcel($data, 'Bao_Cao_Diem_So_GV.xlsx');
    }

    public function exportAttendance(TeacherAnalyticsFilterRequest $request)
    {
        $teacher = $request->user();
        $filters = $request->validated();
        $stats = $this->analyticsService->getAttendanceStats($teacher, $filters);
        $data = $stats['data'];

        if ($request->query('format') === 'pdf') {
            $viewData = ['data' => $data, 'date' => Carbon::now()->format('d/m/Y')];
            return $this->exportService->exportPdf('teacher.reports.pdf.attendance', $viewData, 'Bao_Cao_Chuyen_Can_GV.pdf');
        }

        return $this->exportService->exportAttendanceExcel($data, 'Bao_Cao_Chuyen_Can_GV.xlsx');
    }
}
