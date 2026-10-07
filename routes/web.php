<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);
Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

// 管理者
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/staff/list', [StaffController::class, 'index']);
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staff']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [ApprovalController::class, 'show']);
    Route::post('/stamp_correction_request/approve/{id}', [ApprovalController::class, 'approve']);
});

// 一般ユーザー（勤怠詳細・修正・申請一覧は管理者と共用し、コントローラーでログインした入口により分岐）
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    // /attendance/{id} より先に定義する
    Route::get('/attendance/list', [AttendanceController::class, 'index']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);

    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index']);
    // 提供Bladeの申請一覧「詳細」リンク
    Route::get('/application/{id}', [ApplicationController::class, 'show']);
});
