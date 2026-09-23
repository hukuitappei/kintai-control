<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
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

// 一般ユーザーの会員登録・ログイン・ログアウト（/register, /login, /logout）は
// Fortifyパッケージ自身がルートを自動登録するため、ここで定義する必要はない。
// ビュー・アクションはPhase2のFortifyServiceProviderで設定済み。

// 管理者ログイン・ログアウトはFortifyの対象外（Fortifyは1系統のログインのみ）なので、
// 独自にコントローラーとルートを用意する（docs/unspecified-decisions.md #実装判断）。
Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);
Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

// 勤怠打刻（一般ユーザー、要ログイン）。PG03: /attendance（docs/paste配下の画面設計シート）。
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
});
