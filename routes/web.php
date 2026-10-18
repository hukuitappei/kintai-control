<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// 会員登録の送信を RegisterRequest で検証する（FN002）。Fortify の同じルートより後に定義されるため、こちらが使われる
Route::post('/register', [RegisterController::class, 'store'])->middleware('guest');

// 管理者のログイン・ログアウト（FN014・FN017）。Fortify のコントローラーを使い、管理者かどうかは FortifyServiceProvider でパスを見て分ける
Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

// 管理者
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/staff/list', [StaffController::class, 'index']);
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staff']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [ApprovalController::class, 'show']);
    Route::post('/stamp_correction_request/approve/{id}', [ApprovalController::class, 'approve']);
    // スタッフ別月次勤怠一覧の「CSV出力」（提供Bladeのフォームの送信先）
    Route::post('/export', [AdminAttendanceController::class, 'export']);
});

// 一般ユーザー（勤怠詳細・修正・申請一覧は管理者と共用し、コントローラーでログインした入口により分岐）
// メール未認証のユーザーはメール認証誘導画面へ送る（FN011）
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    // /attendance/{id} より先に定義する
    Route::get('/attendance/list', [AttendanceController::class, 'index']);
    Route::get('/attendance/report', [ReportController::class, 'index']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);

    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index']);
    // 提供Bladeの申請一覧「詳細」リンク
    Route::get('/application/{id}', [ApplicationController::class, 'show']);
});
