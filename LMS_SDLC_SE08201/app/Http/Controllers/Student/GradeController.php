<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\GradeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function __construct(private readonly GradeService $gradeService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $searchQuery = $request->query('search');

        $enrolledClasses = $this->gradeService->enrolledClassesForFilter($user);
        $transcriptData = $this->gradeService->transcript($user, $selectedClassId, $searchQuery);

        return view('student.grades', array_merge($transcriptData, [
            'enrolledClasses' => $enrolledClasses,
            'selectedClassId' => $selectedClassId,
            'searchQuery' => $searchQuery,
        ]));
    }
}
