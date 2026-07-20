<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AttendanceLocationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware(['jwt', 'status'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/check', [AuthController::class, 'check']);
    });
});

Route::prefix('v1')->middleware(['jwt', 'status'])->group(function () {
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('schedules', WorkScheduleController::class);
    Route::apiResource('locations', AttendanceLocationController::class);
    Route::apiResource('employees', EmployeeController::class);

    Route::apiResource('users', UserController::class);
    Route::get('/roles', [UserController::class, 'roles']);

    Route::prefix('attendances')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::post('/check-in', [AttendanceController::class, 'store']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/{id}', [AttendanceController::class, 'show']);
    });

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});
