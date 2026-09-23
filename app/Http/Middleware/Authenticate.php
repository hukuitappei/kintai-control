<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // /admin/... への未認証アクセスは、一般用のloginではなくadmin.loginへ。
        // $request->is('パターン') は現在のURLパスがパターンに一致するか調べる（*はワイルドカード）。
        if ($request->is('admin/*')) {
            return route('admin.login');
        }

        return route('login');
    }
}
