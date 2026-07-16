<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleService $scheduleService)
    {
    }

    /** Thời khóa biểu */
    public function index(Request $request): View
    {
        return view('student.schedule', [
            'timetable' => $this->scheduleService->timetable($request->user()),
        ]);
    }

    /** Lịch sử điểm danh */
    public function attendance(Request $request): View
    {
        return view('student.attendance', $this->scheduleService->attendanceHistory($request->user()));
    }
}
