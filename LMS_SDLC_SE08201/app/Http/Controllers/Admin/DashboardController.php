<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(): View
    {
        $data = $this->dashboardService->getStats();
        $data['majors'] = \App\Models\Major::all();
        $data['subjects'] = \App\Models\Subject::all();
        return view('admin.dashboard', $data);
    }

    public function chartsDataApi(\Illuminate\Http\Request $request)
    {
        return response()->json(
            $this->dashboardService->getDashboardCharts($request->all())
        );
    }
}
