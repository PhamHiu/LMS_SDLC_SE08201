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
     * API: Trả về options phụ thuộc nhau cho Filter
     */
    public function filterOptions(Request $request)
    {
        $majorId = $request->query('major_id');
        $subjectId = $request->query('subject_id');
        $classId = $request->query('class_id');

        $subjects = Subject::when($majorId, function($q) use ($majorId) {
            $q->whereHas('classes', fn($c) => $c->where('major_id', $majorId));
        })->get(['subject_id', 'subject_name']);

        $classes = ClassRoom::when($majorId, fn($q) => $q->where('major_id', $majorId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->get(['class_id', 'class_name']);

        $teachersQuery = User::where('role', User::ROLE_TEACHER);
        if ($majorId || $subjectId || $classId) {
            $teachersQuery->whereHas('teachingClasses', function($q) use ($majorId, $subjectId, $classId) {
                if ($majorId) $q->where('major_id', $majorId);
                if ($subjectId) $q->where('subject_id', $subjectId);
                if ($classId) $q->where('class_id', $classId);
            });
        }
        $teachers = $teachersQuery->get(['user_id', 'full_name']);

        return response()->json([
            'subjects' => $subjects,
            'classes' => $classes,
            'teachers' => $teachers,
        ]);
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
