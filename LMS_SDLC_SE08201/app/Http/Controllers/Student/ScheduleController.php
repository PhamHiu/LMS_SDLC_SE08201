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

    public function index(Request $request): View
    {
        $user = $request->user();
        $years = $this->scheduleService->availableYears($user);
        
        $selectedYear = $request->filled('year') ? (int) $request->query('year') : Carbon::now()->year;
        if (!in_array($selectedYear, $years) && !empty($years)) {
            $selectedYear = $years[0];
        }

        $weeksOfYear = $this->scheduleService->weeksOfYear($selectedYear);

        if ($request->filled('week_start')) {
            $weekStart = Carbon::parse($request->query('week_start'))->startOfWeek(Carbon::MONDAY);
        } else {
            if ($selectedYear === Carbon::now()->year) {
                $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
            } else {
                $weekStart = Carbon::parse($weeksOfYear[0]['week_start']);
            }
        }

        return view('student.schedule', [
            'years' => $years,
            'weeksOfYear' => $weeksOfYear,
            'selectedYear' => $selectedYear,
            'selectedWeekStart' => $weekStart->toDateString(),
            'slots' => $this->scheduleService->allSlots(),
            'sessionGrid' => $this->scheduleService->weeklyTimetable($user, $weekStart),
            
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'isCurrentWeek' => $weekStart->equalTo(Carbon::now()->startOfWeek(Carbon::MONDAY)),
        ]);
    }

    /** Lịch sử điểm danh */
    public function attendance(Request $request): View
    {
        $user = $request->user();
        $enrolledClasses = $this->scheduleService->enrolledClassesForFilter($user);
        
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;

        $searchQuery = $request->query('search');

        $historyData = $this->scheduleService->attendanceHistory($user, $selectedClassId, $searchQuery);

        return view('student.attendance', array_merge($historyData, [
            'enrolledClasses' => $enrolledClasses,
            'selectedClassId' => $selectedClassId,
            'searchQuery' => $searchQuery,
        ]));
    }
}
