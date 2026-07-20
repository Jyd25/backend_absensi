<?php

use App\Http\Controllers\Api\V1\AttendanceCorrectionController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\FaceController;
use App\Http\Controllers\Api\V1\FaceUpdateRequestController;
use App\Http\Controllers\Api\V1\GeolocationController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['jwt', 'status'])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/weekly', [DashboardController::class, 'weekly']);
        Route::get('/monthly', [DashboardController::class, 'monthly']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/employee', [ReportController::class, 'employee']);
        Route::get('/department', [ReportController::class, 'department']);
    });

    Route::prefix('faces')->group(function () {
        Route::post('/register', [FaceController::class, 'register']);
        Route::post('/verify', [FaceController::class, 'verify']);
        Route::get('/history', [FaceController::class, 'history']);
        Route::delete('/{id}', [FaceController::class, 'destroy']);
    });

    Route::prefix('geolocation')->group(function () {
        Route::post('/validate', [GeolocationController::class, 'validate']);
    });

    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveController::class, 'index']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::post('/{id}/approve', [LeaveController::class, 'approve']);
        Route::post('/{id}/reject', [LeaveController::class, 'reject']);
        Route::delete('/{id}', [LeaveController::class, 'destroy']);
    });

    Route::prefix('corrections')->group(function () {
        Route::get('/', [AttendanceCorrectionController::class, 'index']);
        Route::post('/', [AttendanceCorrectionController::class, 'store']);
        Route::post('/{id}/approve', [AttendanceCorrectionController::class, 'approve']);
        Route::post('/{id}/reject', [AttendanceCorrectionController::class, 'reject']);
    });

    Route::prefix('export')->group(function () {
        Route::get('/attendance', [ExportController::class, 'attendance']);
    });

    Route::prefix('face-update-requests')->group(function () {
        Route::get('/', [FaceUpdateRequestController::class, 'index']);
        Route::post('/', [FaceUpdateRequestController::class, 'store']);
        Route::post('/{id}/approve', [FaceUpdateRequestController::class, 'approve']);
        Route::post('/{id}/reject', [FaceUpdateRequestController::class, 'reject']);
    });
});
