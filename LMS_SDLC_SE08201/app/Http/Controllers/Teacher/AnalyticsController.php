<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\TeacherAnalyticsFilterRequest;
use App\Models\ClassRoom;
use App\Services\Teacher\TeacherAnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly TeacherAnalyticsService $analyticsService)
    {
    }

    /**
     * Render trang thống kê cho Teacher
     */
    public function index(Request $request)
    {
        $teacher = $request->user();
        $classes = ClassRoom::where('user_id', $teacher->user_id)->get();

        return view('teacher.analytics.index', compact('classes'));
    }

    /**
     * API: Thống kê Điểm số
     */
    public function grades(TeacherAnalyticsFilterRequest $request)
    {
        $stats = $this->analyticsService->getGradeStats($request->user(), $request->validated());
        return response()->json($stats);
    }

    /**
     * API: Thống kê Chuyên cần
     */
    public function attendance(TeacherAnalyticsFilterRequest $request)
    {
        $stats = $this->analyticsService->getAttendanceStats($request->user(), $request->validated());
        return response()->json($stats);
    }
}
