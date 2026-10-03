<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * スタッフ一覧（管理者）。FN041〜FN042。
     * PG10: /admin/staff/list
     * Blade: admin/staff-list.blade.php（$users。各要素の name / email / id を読む）
     */
    public function index(): View
    {
        // 一般ユーザー全員（管理者自身はスタッフではないので除く）
        $users = User::where('admin_status', false)->orderBy('id')->get();

        return view('admin.staff-list', compact('users'));
    }
}
