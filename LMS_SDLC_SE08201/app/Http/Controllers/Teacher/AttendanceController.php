<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttendanceRequest;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Services\Teacher\AttendanceService;
use App\Services\Teacher\TeacherClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly TeacherClassService $classService,
    ) {
    }

    /** Form điểm danh một buổi học */
    public function edit(Request $request, ClassRoom $class, ClassSession $session): View
    {
        $this->classService->authorizeClass($request->user(), $class);
        abort_if($session->class_id !== $class->class_id, 404);

        return view('teacher.attendance.edit', array_merge(
            ['class' => $class, 'session' => $session],
            $this->attendanceService->getSheet($class, $session),
        ));
    }

    /** Lưu điểm danh */
    public function update(AttendanceRequest $request, ClassRoom $class, ClassSession $session): RedirectResponse
    {
        $this->classService->authorizeClass($request->user(), $class);
        abort_if($session->class_id !== $class->class_id, 404);

        $this->attendanceService->save($session, $request->validated('attendances'));

        return redirect()
            ->route('teacher.classes.show', $class)
            ->with('success', 'Đã lưu điểm danh buổi '.$session->session_date->format('d/m/Y').'.');
    }
}
