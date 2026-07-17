<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleService $scheduleService)
    {
    }

    /** Thời khóa biểu theo tuần (Thứ 2 → Chủ nhật) */
    public function index(Request $request): View
    {
        $weekStart = ($request->filled('week')
            ? Carbon::parse($request->query('week'))
            : Carbon::now())->startOfWeek(Carbon::MONDAY);

        return view('student.schedule', [
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'sessionsByDay' => $this->scheduleService->weeklyTimetable($request->user(), $weekStart),
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'isCurrentWeek' => $weekStart->equalTo(Carbon::now()->startOfWeek(Carbon::MONDAY)),
        ]);
    }

    /** Lịch sử điểm danh */
    public function attendance(Request $request): View
    {
        return view('student.attendance', $this->scheduleService->attendanceHistory($request->user()));
    }
}
