<?php

use App\Http\Controllers\Api\RiskApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Smart LMS AI - RESTful API Routes
| Tuân thủ Master Prompt Section XI
|--------------------------------------------------------------------------
*/

Route::prefix('risk')->group(function () {
    Route::get('/configuration', [RiskApiController::class, 'configuration']);
    Route::get('/predictions/{id}', [RiskApiController::class, 'predictionDetail']);
    Route::post('/predictions/{id}/review', [RiskApiController::class, 'reviewPrediction']);
});

Route::prefix('teacher')->group(function () {
    Route::get('/students/{id}/risk', [RiskApiController::class, 'studentRisk']);
    Route::get('/classes/{id}/risk-summary', [RiskApiController::class, 'classRiskSummary']);
});

Route::prefix('admin')->group(function () {
    Route::get('/risk/dashboard', [RiskApiController::class, 'adminDashboard']);
});

Route::prefix('students')->group(function () {
    Route::get('/{id}/attendance-summary', [RiskApiController::class, 'studentAttendanceSummary']);
    Route::get('/{id}/academic-history', [RiskApiController::class, 'studentAcademicHistory']);
});

