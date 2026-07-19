<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Luồng xử lý: Browser → Route → Middleware → FormRequest → Controller
|              → Service → Model → DB → View → Browser
|--------------------------------------------------------------------------
*/

// Trang chủ: điều hướng theo trạng thái đăng nhập + role
Route::get('/', function (AuthService $authService) {
    return Auth::check()
        ? redirect($authService->homeRouteFor(Auth::user()))
        : redirect()->route('login');
})->name('home');

// ===== AUTH (guest) =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
});

// ===== Dùng chung (đã đăng nhập) =====
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ===== NHÓM 1: ADMIN =====
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/charts-data', [Admin\DashboardController::class, 'chartsDataApi'])->name('dashboard.charts-data');
    });

    // Quản lý người dùng + reset mật khẩu
    Route::resource('users', Admin\UserController::class)->except('show');
    Route::post('users/{user}/reset-password', [Admin\UserController::class, 'resetPassword'])->name('users.reset-password');

    // Quản lý chuyên ngành
    Route::resource('majors', Admin\MajorController::class)->except('show');

    // Quản lý môn học
    Route::resource('subjects', Admin\SubjectController::class)->except('show');

    Route::prefix('classes/api')->name('classes.api.')->group(function () {
        Route::get('slots', [Admin\ClassController::class, 'getSlots'])->name('slots');
        Route::get('rooms', [Admin\ClassController::class, 'getRooms'])->name('rooms');
        Route::get('subjects-by-major', [Admin\ClassController::class, 'subjectsByMajor'])->name('subjects');
        Route::get('teachers-by-major', [Admin\ClassController::class, 'teachersByMajor'])->name('teachers');
        Route::get('timetable', [Admin\ClassController::class, 'timetable'])->name('timetable');
        Route::post('check-conflict', [Admin\ClassController::class, 'checkConflict'])->name('conflict');
        Route::post('sessions/{session}/update', [Admin\ClassController::class, 'updateSession'])->name('sessions.update');
    });
    Route::resource('classes', Admin\ClassController::class);
    Route::post('classes/{class}/students', [Admin\ClassController::class, 'enrollStudents'])->name('classes.students.enroll');
    Route::delete('classes/{class}/students/{student}', [Admin\ClassController::class, 'removeStudent'])->name('classes.students.remove');

    // Thông báo toàn hệ thống
    Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [Admin\NotificationController::class, 'store'])->name('notifications.store');
    Route::delete('notifications/{notification}', [Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Thống kê & Báo cáo
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [Admin\AnalyticsController::class, 'index'])->name('index');
        Route::get('/filter-options', [Admin\AnalyticsController::class, 'filterOptions'])->name('filter-options');
        Route::get('/grades', [Admin\AnalyticsController::class, 'grades'])->name('grades');
        Route::get('/attendance', [Admin\AnalyticsController::class, 'attendance'])->name('attendance');
        Route::get('/courses', [Admin\AnalyticsController::class, 'courses'])->name('courses');
        Route::get('/users', [Admin\AnalyticsController::class, 'users'])->name('users');
    });
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/grades/export', [Admin\ReportController::class, 'exportGrades'])->name('grades.export');
        Route::get('/attendance/export', [Admin\ReportController::class, 'exportAttendance'])->name('attendance.export');
        Route::get('/courses/export', [Admin\ReportController::class, 'exportCourses'])->name('courses.export');
        Route::get('/users/export', [Admin\ReportController::class, 'exportUsers'])->name('users.export');
    });
});

// ===== NHÓM 2: TEACHER =====
Route::middleware(['auth', 'role:TEACHER'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [Teacher\DashboardController::class, 'index'])->name('dashboard');

    // Lớp phụ trách + danh sách sinh viên
    Route::get('classes', [Teacher\ClassController::class, 'index'])->name('classes.index');
    Route::get('classes/{class}', [Teacher\ClassController::class, 'show'])->name('classes.show');

    // Học liệu
    Route::get('materials', [Teacher\MaterialController::class, 'index'])->name('materials.index');
    Route::post('materials', [Teacher\MaterialController::class, 'store'])->name('materials.store');
    Route::delete('materials/{material}', [Teacher\MaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('materials/{material}/download', [Teacher\MaterialController::class, 'download'])->name('materials.download');

    // Điểm danh theo buổi
    Route::get('classes/{class}/sessions/{session}/attendance', [Teacher\AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::post('classes/{class}/sessions/{session}/attendance', [Teacher\AttendanceController::class, 'update'])->name('attendance.update');

    // Bài tập / Quiz
    Route::get('assignments', [Teacher\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('classes/{class}/assignments/create', [Teacher\AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('classes/{class}/assignments', [Teacher\AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('assignments/{assignment}', [Teacher\AssignmentController::class, 'show'])->name('assignments.show');
    // ( Đăng thêm ) Route để gọi API sinh câu hỏi
    Route::post('assignments/ai-generate', [Teacher\AiController::class, 'generate'])->name('assignments.ai-generate');
    // ( Đăng thêm ) Thêm route để lấy giao diện cập nhật bài tập
    Route::get('assignments/{assignment}/edit', [Teacher\AssignmentController::class, 'edit'])->name('assignments.edit');
    // ( Đăng thêm ) Thêm route để thực thi cập nhật bài tập
    Route::put('assignments/{assignment}', [Teacher\AssignmentController::class, 'update'])->name('assignments.update');
    // ( Đăng thêm ) Thêm route để tải xuống file đính kèm của bài tập
    Route::get('assignments/{assignment}/download-attachment', [Teacher\AssignmentController::class, 'downloadAttachment'])->name('assignments.download-attachment');
    Route::delete('assignments/{assignment}', [Teacher\AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Chấm bài
    Route::get('assignments/{assignment}/submissions', [Teacher\GradingController::class, 'index'])->name('grading.index');
    Route::get('submissions/{submission}/grade', [Teacher\GradingController::class, 'show'])->name('grading.show');
    Route::get('submissions/{submission}/download', [Teacher\GradingController::class, 'download'])->name('grading.download');
    Route::post('submissions/{submission}/grade', [Teacher\GradingController::class, 'grade'])->name('grading.grade');

    // Thông báo lớp
    Route::get('notifications', [Teacher\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [Teacher\NotificationController::class, 'store'])->name('notifications.store');
    Route::delete('notifications/{notification}', [Teacher\NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Thảo luận dưới bài tập
    Route::post('assignments/{assignment}/discussions', [Teacher\DiscussionController::class, 'store'])->name('discussions.store');

    // Thống kê lớp
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [Teacher\AnalyticsController::class, 'index'])->name('index');
        Route::get('/grades', [Teacher\AnalyticsController::class, 'grades'])->name('grades');
        Route::get('/attendance', [Teacher\AnalyticsController::class, 'attendance'])->name('attendance');
    });
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/grades/export', [Teacher\ReportController::class, 'exportGrades'])->name('grades.export');
        Route::get('/attendance/export', [Teacher\ReportController::class, 'exportAttendance'])->name('attendance.export');
    });
});

// ===== NHÓM 3: STUDENT =====
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [Student\DashboardController::class, 'index'])->name('dashboard');

    // Lịch học & điểm danh
    Route::get('schedule', [Student\ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('attendance', [Student\ScheduleController::class, 'attendance'])->name('attendance.index');

    // Học liệu
    Route::get('materials', [Student\MaterialController::class, 'index'])->name('materials.index');
    Route::get('materials/{material}/download', [Student\MaterialController::class, 'download'])->name('materials.download');

    // Bài tập: xem, làm quiz, nộp tự luận
    Route::get('assignments', [Student\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/{assignment}', [Student\AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('assignments/{assignment}/essay', [Student\AssignmentController::class, 'submitEssay'])->name('assignments.essay.submit');
    Route::get('assignments/{assignment}/quiz', [Student\AssignmentController::class, 'takeQuiz'])->name('assignments.quiz.take');
    Route::post('assignments/{assignment}/quiz', [Student\AssignmentController::class, 'submitQuiz'])->name('assignments.quiz.submit');

    // Điểm số
    Route::get('grades', [Student\GradeController::class, 'index'])->name('grades.index');

    // Thông báo + thảo luận
    Route::get('notifications', [Student\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('assignments/{assignment}/discussions', [Student\DiscussionController::class, 'store'])->name('discussions.store');
});
