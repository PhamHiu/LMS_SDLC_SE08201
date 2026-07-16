<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassRequest;
use App\Http\Requests\Admin\EnrollStudentsRequest;
use App\Models\ClassRoom;
use App\Models\Major;
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
        $class->load(['teacher', 'subject', 'major', 'students', 'sessions']);

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
