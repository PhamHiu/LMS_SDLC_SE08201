<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\Student\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StudentDashboardService $dashboardService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->dashboardService->getStats($request->user());
        $stats['notifications'] = $this->notificationService->latestFor($request->user());

        return view('student.dashboard', $stats);
    }
}
