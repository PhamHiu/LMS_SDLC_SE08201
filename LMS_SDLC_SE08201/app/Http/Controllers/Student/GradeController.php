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
        return view('student.grades', $this->gradeService->transcript($request->user()));
    }
}
