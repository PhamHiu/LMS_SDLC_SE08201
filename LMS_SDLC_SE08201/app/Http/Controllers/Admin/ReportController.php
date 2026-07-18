<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsFilterRequest;
use App\Services\Admin\AnalyticsService;
use App\Services\Shared\ReportExportService;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly ReportExportService $exportService
    ) {}

    public function exportGrades(AnalyticsFilterRequest $request)
    {
        $filters = $request->validated();
        $stats = $this->analyticsService->getGradeStats($filters);
        $data = $stats['data']; // Collection

        if ($request->query('format') === 'pdf') {
            $viewData = ['data' => $data, 'date' => Carbon::now()->format('d/m/Y')];
            return $this->exportService->exportPdf('admin.reports.pdf.grades', $viewData, 'Bao_Cao_Diem_So.pdf');
        }

        return $this->exportService->exportGradesExcel($data);
    }

    public function exportAttendance(AnalyticsFilterRequest $request)
    {
        $filters = $request->validated();
        $stats = $this->analyticsService->getAttendanceStats($filters);
        $data = $stats['data'];

        if ($request->query('format') === 'pdf') {
            $viewData = ['data' => $data, 'date' => Carbon::now()->format('d/m/Y')];
            return $this->exportService->exportPdf('admin.reports.pdf.attendance', $viewData, 'Bao_Cao_Chuyen_Can.pdf');
        }

        return $this->exportService->exportAttendanceExcel($data);
    }

    public function exportCourses(AnalyticsFilterRequest $request)
    {
        $filters = $request->validated();
        $stats = $this->analyticsService->getCourseStats($filters);
        $data = $stats['data'];

        if ($request->query('format') === 'pdf') {
            $viewData = ['data' => $data, 'date' => Carbon::now()->format('d/m/Y')];
            return $this->exportService->exportPdf('admin.reports.pdf.courses', $viewData, 'Bao_Cao_Khoa_Hoc.pdf');
        }

        return $this->exportService->exportCourseExcel($data);
    }

    public function exportUsers(AnalyticsFilterRequest $request)
    {
        $filters = $request->validated();
        $stats = $this->analyticsService->getUserStats($filters);
        $data = $stats['data'];

        if ($request->query('format') === 'pdf') {
            $viewData = ['data' => $data, 'date' => Carbon::now()->format('d/m/Y')];
            return $this->exportService->exportPdf('admin.reports.pdf.users', $viewData, 'Bao_Cao_Nguoi_Dung.pdf');
        }

        return $this->exportService->exportUserExcel($data);
    }
}
