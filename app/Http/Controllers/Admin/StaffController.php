<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * スタッフ一覧（管理者）。FN041〜FN042。
     *
     * @return View スタッフ一覧画面（管理者）のビュー
     */
    public function index(): View
    {
        $users = User::where('admin_status', false)->orderBy('id')->get();

        return view('admin.staff-list', compact('users'));
    }
}
