<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * 管理者ログイン画面（/admin/login）から入っていなければ403にする（authミドルウェアの後に使う）。
     * 管理者でも一般ログイン画面から入った場合はスタッフとして扱うため、管理者用画面は開けない。
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('admin_login', false)) {
            abort(403);
        }

        return $next($request);
    }
}
