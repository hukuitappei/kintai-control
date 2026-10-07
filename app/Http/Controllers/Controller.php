<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * 管理者ログイン画面（/admin/login）から入ったか。共用URLの一般用・管理者用の切り替えに使う（PG12）。
     * 管理者が一般ログイン画面（/login）から入った場合はスタッフとして扱う。
     */
    protected function loggedInAsAdmin(): bool
    {
        return session()->get('admin_login', false);
    }
}
