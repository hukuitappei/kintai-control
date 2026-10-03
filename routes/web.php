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

// 管理者専用の画面。ログイン必須（auth）かつ admin_status が true（app/Http/Kernel.php のミドルウェアエイリアス）。
// prefix('admin') で、中のパスの先頭に /admin が付く。
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // 日次勤怠一覧。PG08: /admin/attendance/list
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);
    // スタッフ一覧。PG10: /admin/staff/list（このグループはprefix('admin')付きなので、/adminより後ろだけを書く）
    Route::get('/staff/list', [StaffController::class, 'index']);
    // スタッフ別月次勤怠一覧。PG11: /admin/attendance/staff/{id}（スタッフ一覧の「詳細」から）
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staff']);
});

// 修正申請承認（管理者専用）。URLが /admin で始まらないので、prefixなしの別グループにする。
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [ApprovalController::class, 'show']);
    // 承認ボタン。Blade: admin-application-detail.blade.php の <form> の method を見ること
    Route::post('/stamp_correction_request/approve/{id}', [ApprovalController::class, 'approve']);
});

// 勤怠打刻（一般ユーザー、要ログイン）。PG03: /attendance（docs/paste配下の画面設計シート）。
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create']);
    Route::post('/attendance', [AttendanceController::class, 'store']);

    // 勤怠一覧（一般ユーザー）。PG04: /attendance/list
    // Phase6-2で /attendance/{id} を追加するとき、{id}に"list"が当てはまらないよう
    // このルートを先に書いておく（ルートは上から順に照合される）。
    Route::get('/attendance/list', [AttendanceController::class, 'index']);

    // 勤怠詳細（一般ユーザー・管理者で共用。コントローラー内でadmin_statusにより分岐）。Blade: url('/attendance/' . $data['id'])
    Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
    // 修正申請（一般ユーザー）／直接修正（管理者）で共用。コントローラー内でadmin_statusにより分岐。Blade: user-detail.blade.php の <form> の method を見ること
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);

    // 申請一覧（一般ユーザー・管理者で共用。コントローラー内でadmin_statusにより分岐）。ヘッダーの「申請」リンク（layouts/app.blade.php）と同じパス
    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index']);
    // 申請一覧の「詳細」。Blade原文のリンク先（申請id）から、紐づく勤怠詳細へリダイレクトする
    Route::get('/application/{id}', [ApplicationController::class, 'show']);
});
