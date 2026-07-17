<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsFilterRequest;
use App\Models\ClassRoom;
use App\Models\Major;
use App\Models\Subject;
use App\Models\User;
use App\Services\Admin\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    /**
     * Render trang thống kê chung
     */
    public function index(Request $request)
    {
        // Lấy data cho filter bar
        $majors = Major::all();
        $subjects = Subject::all();
        $classes = ClassRoom::all();
        $teachers = User::where('role', User::ROLE_TEACHER)->get();

        return view('admin.analytics.index', compact('majors', 'subjects', 'classes', 'teachers'));
    }

    /**
     * API: Thống kê Điểm số
     */
    public function grades(AnalyticsFilterRequest $request)
    {
        $stats = $this->analyticsService->getGradeStats($request->validated());
        return response()->json($stats);
    }

    /**
     * API: Thống kê Chuyên cần
     */
    public function attendance(AnalyticsFilterRequest $request)
    {
        $stats = $this->analyticsService->getAttendanceStats($request->validated());
        return response()->json($stats);
    }

    /**
     * API: Thống kê Khóa học
     */
    public function courses(AnalyticsFilterRequest $request)
    {
        $stats = $this->analyticsService->getCourseStats($request->validated());
        return response()->json($stats);
    }

    /**
     * API: Thống kê Người dùng
     */
    public function users(AnalyticsFilterRequest $request)
    {
        $stats = $this->analyticsService->getUserStats($request->validated());
        return response()->json($stats);
    }
}
