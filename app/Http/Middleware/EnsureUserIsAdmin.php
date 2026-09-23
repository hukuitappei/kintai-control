<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * 管理者ルートを一般ユーザーから守る。authミドルウェアの後に使う想定
     * （未認証はauthが先にadmin.loginへ弾くので、ここに来る時点でログイン済み）。
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->admin_status) {
            abort(403);
        }

        return $next($request);
    }
}
