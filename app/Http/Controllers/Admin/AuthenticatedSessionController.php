<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * ログイン画面（管理者）を表示する。FN014〜FN016。
     *
     * @return View 管理者ログイン画面のビュー
     */
    public function create(): View
    {
        return view('admin.admin-login');
    }

    /**
     * 管理者として認証し、セッションを再生成する。成功したら勤怠一覧画面（管理者）へ遷移する。FN014〜FN016。
     *
     * @param  AdminLoginRequest  $request  管理者ログイン画面で入力されたメールアドレスとパスワード
     * @return RedirectResponse 勤怠一覧画面（管理者）へのリダイレクト
     */
    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // 管理者ログイン画面から入った印。ログアウト時のinvalidate()で消える
        $request->session()->put('admin_login', true);

        return redirect('/admin/attendance/list');
    }

    /**
     * 管理者をログアウトさせ、ログイン画面（管理者）へ遷移する。FN017。
     *
     * @param  Request  $request  セッションの破棄に使うリクエスト
     * @return RedirectResponse ログイン画面（管理者）へのリダイレクト
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
