<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Services\Student\StudentClassService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(private readonly StudentClassService $classService)
    {
    }

    /** Tab "Lớp của tôi" — danh sách các lớp sinh viên đã ghi danh. */
    public function index(Request $request): View
    {
        $classes = $this->classService->enrolledClasses($request->user());

        return view('student.classes', [
            'classes' => $classes,
            'summary' => $this->classService->summary($classes),
        ]);
    }

    /** Trang chi tiết 1 lớp — chỉ hiển thị nội dung thuộc phạm vi lớp đó. */
    public function show(Request $request, ClassRoom $class): View
    {
        $student = $request->user();
        $this->classService->authorizeClass($student, $class);

        return view('student.class-detail', $this->classService->classDetail($student, $class));
    }
}
