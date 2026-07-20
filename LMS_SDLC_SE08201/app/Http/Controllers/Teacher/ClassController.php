<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Services\Teacher\TeacherClassService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(private readonly TeacherClassService $classService)
    {
    }

    public function index(Request $request): View
    {
        return view('teacher.classes.index', [
            'classes' => $this->classService->listFor($request->user()),
        ]);
    }

    public function show(Request $request, ClassRoom $class): View
    {
        return view('teacher.classes.show', $this->classService->detail($request->user(), $class));
    }
}
