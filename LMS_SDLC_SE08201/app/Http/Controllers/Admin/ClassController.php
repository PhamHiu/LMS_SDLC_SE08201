<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassRequest;
use App\Http\Requests\Admin\EnrollStudentsRequest;
use App\Models\ClassRoom;
use App\Models\Major;
use App\Models\Room;
use App\Models\Slot;
use App\Models\Subject;
use App\Models\User;
use App\Services\Admin\ClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(private readonly ClassService $classService)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.classes.index', [
            'classes' => $this->classService->paginate(
                $request->query('search'),
                $status === null || $status === '' ? null : (int) $status,
            ),
            'search' => $request->query('search'),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.classes.form', [
            'class' => null,
            'teachers' => User::where('role', User::ROLE_TEACHER)->orderBy('full_name')->get(),
            'subjects' => Subject::orderBy('subject_name')->get(),
            'majors' => Major::orderBy('major_name')->get(),
            'rooms' => Room::orderBy('room_name')->get(),
            'slots' => Slot::orderBy('slot_number')->get(),
        ]);
    }

    public function store(ClassRequest $request): RedirectResponse
    {
        $class = $this->classService->create($request->validated());

        return redirect()
            ->route('admin.classes.show', $class)
            ->with('success', 'Tạo lớp học thành công. Hệ thống đã tự sinh '.$class->total_sessions.' buổi học.');
    }

    /** Trang quản lý chi tiết lớp: gán GV, danh sách SV, xếp SV */
    public function show(ClassRoom $class): View
    {
        $class->load(['teacher', 'subject', 'major', 'students', 'sessions.slot', 'sessions.room']);

        return view('admin.classes.show', [
            'class' => $class,
            'availableStudents' => $this->classService->availableStudents($class),
        ]);
    }

    public function edit(ClassRoom $class): View
    {
        return view('admin.classes.form', [
            'class' => $class,
            'teachers' => User::where('role', User::ROLE_TEACHER)->orderBy('full_name')->get(),
            'subjects' => Subject::orderBy('subject_name')->get(),
            'majors' => Major::orderBy('major_name')->get(),
            'rooms' => Room::orderBy('room_name')->get(),
            'slots' => Slot::orderBy('slot_number')->get(),
        ]);
    }

    public function update(ClassRequest $request, ClassRoom $class): RedirectResponse
    {
        $this->classService->update($class, $request->validated());

        return redirect()->route('admin.classes.show', $class)->with('success', 'Cập nhật lớp học thành công.');
    }

    public function destroy(ClassRoom $class): RedirectResponse
    {
        $this->classService->delete($class);

        return redirect()->route('admin.classes.index')->with('success', 'Đã xóa lớp học.');
    }

    public function subjectsByMajor(Request $request)
    {
        $majorId = $request->query('major_id');
        if (!$majorId) {
            return response()->json([]);
        }

        $major = Major::with('subjects')->find($majorId);
        if (!$major) {
            return response()->json([]);
        }

        return response()->json($major->subjects()->orderBy('subject_name')->get(['subjects.subject_id', 'subject_name']));
    }

    public function teachersByMajor(Request $request)
    {
        $majorId = $request->query('major_id');
        if (!$majorId) {
            return response()->json([]);
        }

        $teachers = User::where('role', User::ROLE_TEACHER)
            ->where(function($query) use ($majorId) {
                $query->where('major_id', $majorId)
                      ->orWhereNull('major_id');
            })
            ->orderBy('major_id', 'desc')
            ->orderBy('full_name')
            ->get(['user_id', 'full_name', 'major_id']);

        return response()->json($teachers);
    }

    public function timetable(Request $request)
    {
        $startDate = $request->query('start_date');
        $teacherId = $request->query('teacher_id');
        $roomId = $request->query('room_id');
        
        if (!$startDate) {
            return response()->json([]);
        }

        return response()->json($this->classService->getExistingTimetable(
            $startDate,
            $teacherId ? (int) $teacherId : null,
            $roomId ? (int) $roomId : null
        ));
    }

    public function checkConflict(Request $request)
    {
        $validated = $request->validate([
            'session_date' => 'required|date',
            'slot_id' => 'required|exists:slots,slot_id',
            'teacher_id' => 'required|exists:users,user_id',
            'room_id' => 'required|exists:rooms,room_id',
        ]);

        return response()->json($this->classService->checkConflicts(
            $validated['session_date'],
            $validated['slot_id'],
            $validated['teacher_id'],
            $validated['room_id']
        ));
    }

    public function getSlots()
    {
        return response()->json(Slot::orderBy('slot_number')->get());
    }

    public function getRooms()
    {
        return response()->json(Room::orderBy('room_name')->get());
    }

    public function updateSession(Request $request, \App\Models\ClassSession $session)
    {
        $validated = $request->validate([
            'session_date' => 'required|date',
            'slot_id' => 'required|exists:slots,slot_id',
            'room_id' => 'required|exists:rooms,room_id',
            'teacher_id' => 'required|exists:users,user_id',
        ]);

        $conflictResult = $this->classService->checkConflicts(
            $validated['session_date'],
            $validated['slot_id'],
            $validated['teacher_id'],
            $validated['room_id'],
            $session->session_id
        );

        if ($conflictResult['is_conflict']) {
            return response()->json($conflictResult);
        }

        $session->update([
            'session_date' => $validated['session_date'],
            'slot_id' => $validated['slot_id'],
            'room_id' => $validated['room_id'],
        ]);
        
        $class = $session->classRoom;
        $maxDate = $class->sessions()->max('session_date');
        if ($maxDate) {
            $class->update(['end_date' => $maxDate]);
        }

        return response()->json(['success' => true]);
    }

    public function enrollStudents(EnrollStudentsRequest $request, ClassRoom $class): RedirectResponse
    {
        $count = $this->classService->enrollStudents($class, $request->validated('student_ids'));

        return back()->with('success', "Đã xếp {$count} sinh viên vào lớp.");
    }

    public function removeStudent(ClassRoom $class, User $student): RedirectResponse
    {
        $this->classService->removeStudent($class, $student);

        return back()->with('success', 'Đã xóa sinh viên khỏi lớp.');
    }
}
