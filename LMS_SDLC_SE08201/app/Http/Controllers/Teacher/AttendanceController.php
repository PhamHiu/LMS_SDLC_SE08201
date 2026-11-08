<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttendanceRequest;
use App\Models\ClassRoom;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\Teacher\AttendanceService;
use App\Services\Teacher\TeacherClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly TeacherClassService $classService,
    ) {
    }

    /**
     * Màn hình quản lý điểm danh tổng hợp theo lớp và theo buổi học.
     */
    public function index(Request $request): View
    {
        $teacher = $request->user() ?? Auth::user();

        // Lấy danh sách lớp mà giáo viên phụ trách (nếu là Admin hoặc demo teacher thì lấy tất cả lớp)
        $classesQuery = ClassRoom::query()->with(['subject', 'major'])->withCount('students');

        if ($teacher && $teacher->role !== User::ROLE_ADMIN && $teacher->email !== 'teacher@lms.vn') {
            $classesQuery->where('user_id', $teacher->user_id);
        }

        // Đưa SE1801-Java lên đầu tiên để giáo viên bấm vào là thấy ngay full data
        $classes = $classesQuery->orderByRaw("CASE WHEN class_name = 'SE1801-Java' THEN 0 ELSE 1 END")
            ->orderBy('class_name')
            ->get();

        // Lớp được chọn (từ query ?class_id=... hoặc ưu tiên SE1801-Java hoặc lớp đầu tiên)
        $selectedClassId = $request->query('class_id')
            ?? $classes->firstWhere('class_name', 'SE1801-Java')?->class_id
            ?? $classes->first()?->class_id;
        $selectedClass = $classes->firstWhere('class_id', $selectedClassId);

        $sessions = collect();
        if ($selectedClass) {
            $sessions = $selectedClass->sessions()
                ->with(['slot', 'room'])
                ->withCount([
                    'attendances as present_count' => function ($q) {
                        $q->where('status', 'P');
                    },
                    'attendances as late_count' => function ($q) {
                        $q->where('status', 'L');
                    },
                    'attendances as absent_count' => function ($q) {
                        $q->where('status', 'A');
                    },
                    'attendances as total_attended' => function ($q) {
                        $q->whereIn('status', ['P', 'L', 'A']);
                    },
                ])
                ->orderBy('session_date')
                ->get();
        }

        return view('teacher.attendance.index', [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'sessions' => $sessions,
        ]);
    }

    /** Form điểm danh chi tiết một buổi học */
    public function edit(Request $request, ClassRoom $class, ClassSession $session): View
    {
        $teacher = $request->user() ?? Auth::user();
        if ($teacher) {
            $this->classService->authorizeClass($teacher, $class);
        }
        abort_if($session->class_id !== $class->class_id, 404);

        $session->load(['slot', 'room']);
        $sheetData = $this->attendanceService->getSheet($class, $session);

        return view('teacher.attendance.edit', array_merge(
            ['class' => $class, 'session' => $session],
            $sheetData,
        ));
    }

    /** Lưu điểm danh */
    public function update(AttendanceRequest $request, ClassRoom $class, ClassSession $session): RedirectResponse
    {
        $teacher = $request->user() ?? Auth::user();
        if ($teacher) {
            $this->classService->authorizeClass($teacher, $class);
        }
        abort_if($session->class_id !== $class->class_id, 404);

        $this->attendanceService->save($session, $request->validated('attendances'));

        return redirect()
            ->route('teacher.attendance.index', ['class_id' => $class->class_id])
            ->with('success', 'Đã lưu điểm danh buổi ' . $session->session_date->format('d/m/Y') . ' thành công và tự động đồng bộ cảnh báo nguy cơ AI.');
    }
}
