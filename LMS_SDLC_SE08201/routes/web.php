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

    // AI Early Warning System: Quản lý nguy cơ bỏ học
    Route::get('risk', [Admin\RiskManagementController::class, 'index'])->name('risk.index');
    Route::get('risk/{student}', [Admin\RiskManagementController::class, 'show'])->name('risk.show');

    // Quản lý & cập nhật kho tri thức RAG cho BTEC AI Student Support
    Route::prefix('rag-kb')->name('rag.')->group(function () {
        Route::get('/', [Admin\RagKnowledgeController::class, 'index'])->name('index');
        Route::post('/', [Admin\RagKnowledgeController::class, 'store'])->name('store');
        Route::post('/import', [Admin\RagKnowledgeController::class, 'import'])->name('import');
        Route::post('/reindex', [Admin\RagKnowledgeController::class, 'reindex'])->name('reindex');
        Route::put('/{chunkId}', [Admin\RagKnowledgeController::class, 'update'])->name('update');
        Route::delete('/{chunkId}', [Admin\RagKnowledgeController::class, 'destroy'])->name('destroy');
    });

    // Quản lý Phiên bản Nội quy & Quy chế có kiểm tra kiểu (Draft -> Simulate -> Approve -> Publish)
    Route::prefix('policies')->name('policies.')->group(function () {
        Route::get('/', [Admin\PolicyRuleController::class, 'index'])->name('index');
        Route::post('/', [Admin\PolicyRuleController::class, 'store'])->name('store');
        Route::post('/propose', [Admin\PolicyRuleController::class, 'proposeFromText'])->name('propose');
        Route::post('/{policy}/simulate', [Admin\PolicyRuleController::class, 'simulate'])->name('simulate');
        Route::post('/{policy}/approve', [Admin\PolicyRuleController::class, 'approve'])->name('approve');
        Route::post('/{policy}/publish', [Admin\PolicyRuleController::class, 'publish'])->name('publish');
        Route::post('/{policy}/archive', [Admin\PolicyRuleController::class, 'archive'])->name('archive');
    });

    // Quản lý & Chuyển tiếp Ticket Hỗ trợ Học vụ
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [Admin\TicketController::class, 'index'])->name('index');
        Route::post('/{ticket}/forward', [Admin\TicketController::class, 'forward'])->name('forward');
        Route::post('/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('reply');
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

    // Điểm danh theo buổi và quản lý điểm danh
    Route::get('attendance', [Teacher\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('classes/{class}/sessions/{session}/attendance', [Teacher\AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::post('classes/{class}/sessions/{session}/attendance', [Teacher\AttendanceController::class, 'update'])->name('attendance.update');
    Route::post('classes/{class}/sessions/{session}/attendance/restore/{student}', [Teacher\AttendanceController::class, 'restore'])->name('attendance.restore');

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
    Route::put('assignments/{assignment}/extend-deadline', [Teacher\AssignmentController::class, 'extendDeadline'])->name('assignments.extend-deadline');
    // ( Đăng thêm ) Thêm route để tải xuống file đính kèm của bài tập
    Route::get('assignments/{assignment}/download-attachment', [Teacher\AssignmentController::class, 'downloadAttachment'])->name('assignments.download-attachment');
    Route::delete('assignments/{assignment}', [Teacher\AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Chấm bài
    Route::get('assignments/{assignment}/submissions', [Teacher\GradingController::class, 'index'])->name('grading.index');
    Route::get('submissions/{submission}/grade', [Teacher\GradingController::class, 'show'])->name('grading.show');
    Route::get('submissions/{submission}/download', [Teacher\GradingController::class, 'download'])->name('grading.download');
    Route::post('submissions/{submission}/grade', [Teacher\GradingController::class, 'grade'])->name('grading.grade');
    Route::post('assignments/{assignment}/publish-grades', [Teacher\GradingController::class, 'publishGrades'])->name('grading.publish-grades');
    Route::post('assignments/{assignment}/remind', [Teacher\GradingController::class, 'remind'])->name('grading.remind');
    Route::post('assignments/{assignment}/remind/{student}', [Teacher\GradingController::class, 'remindStudent'])->name('grading.remind.student');

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

    // AI Early Warning System: Cảnh báo rủi ro sinh viên
    Route::get('risk', [Teacher\RiskDashboardController::class, 'index'])->name('risk.index');
    Route::get('risk/{student}', [Teacher\RiskDashboardController::class, 'show'])->name('risk.show');
    Route::post('risk/{student}/intervene', [Teacher\RiskDashboardController::class, 'recordIntervention'])->name('risk.intervene');

    // Ticket Hỗ trợ được Admin chuyển tiếp
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [Teacher\TicketController::class, 'index'])->name('index');
        Route::post('/{ticket}/reply', [Teacher\TicketController::class, 'reply'])->name('reply');
    });
});

// ===== NHÓM 3: STUDENT =====
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [Student\DashboardController::class, 'index'])->name('dashboard');

    // Lịch học & điểm danh
    Route::get('schedule', [Student\ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('attendance', [Student\ScheduleController::class, 'attendance'])->name('attendance.index');

    // Học liệu & Tóm tắt AI tiếng Việt (ViT5)
    Route::get('materials', [Student\MaterialController::class, 'index'])->name('materials.index');
    Route::get('materials/{material}/download', [Student\MaterialController::class, 'download'])->name('materials.download');
    Route::post('materials/{material}/summarize', [Student\MaterialController::class, 'summarize'])->name('materials.summarize');
    Route::post('materials/subject/{subject}/summarize', [Student\MaterialController::class, 'summarizeSubject'])->name('materials.summarize-subject');

    // Bài tập: xem, làm quiz, nộp tự luận
    Route::get('assignments', [Student\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/{assignment}', [Student\AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('assignments/{assignment}/essay', [Student\AssignmentController::class, 'submitEssay'])->name('assignments.essay.submit');
    Route::get('assignments/{assignment}/quiz', [Student\AssignmentController::class, 'takeQuiz'])->name('assignments.quiz.take');
    Route::post('assignments/{assignment}/quiz', [Student\AssignmentController::class, 'submitQuiz'])->name('assignments.quiz.submit');

    // Điểm số
    Route::get('grades', [Student\GradeController::class, 'index'])->name('grades.index');

    // Thông báo + thảo luận + phiếu hỗ trợ
    Route::get('notifications', [Student\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}/read', [Student\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [Student\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('tickets', [Student\TicketController::class, 'index'])->name('tickets.index');
    Route::post('assignments/{assignment}/discussions', [Student\DiscussionController::class, 'store'])->name('discussions.store');

    // AI Learning Tutor (Mr. Biết Tuốt): Chỉ dành riêng cho Sinh viên (STUDENT)
    Route::prefix('ai-tutor')->name('ai-tutor.')->group(function () {
        Route::get('/', [Student\AiTutorController::class, 'index'])->name('index');
        Route::post('/', [Student\AiTutorController::class, 'store'])->name('store');
        Route::post('/messages/{message}/generate-image', [Student\AiTutorController::class, 'generateImage'])->name('generate-image');
        Route::get('/{conversation}', [Student\AiTutorController::class, 'show'])->name('show');
        Route::post('/{conversation}/message', [Student\AiTutorController::class, 'sendMessage'])->name('message');
        Route::post('/{conversation}/stream', [Student\AiTutorController::class, 'stream'])->name('stream');
        Route::delete('/{conversation}', [Student\AiTutorController::class, 'destroy'])->name('destroy');
    });
});

// ===== BTEC AI SUPPORT ASSISTANT (Dùng chung cho STUDENT, TEACHER, ADMIN — Backend kiểm tra quyền chặt chẽ theo từng role) =====
Route::middleware(['auth', 'role:STUDENT,TEACHER,ADMIN'])->prefix('student/btec-ai')->name('student.btec-ai.')->group(function () {
    Route::match(['get', 'post'], '/init', [Student\BtecSupportController::class, 'init'])->name('init');
    Route::post('/chat', [Student\BtecSupportController::class, 'chat'])->name('chat');
    Route::post('/stream', [Student\BtecSupportController::class, 'stream'])->name('stream');
    Route::post('/ticket', [Student\BtecSupportController::class, 'submitTicket'])->name('ticket');
    Route::post('/feedback', [Student\BtecSupportController::class, 'feedback'])->name('feedback');
});