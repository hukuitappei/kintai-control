<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /**
     * 申請一覧。一般ユーザー（FN031〜FN032）と管理者（FN047〜FN049）で共用。
     * /stamp_correction_request/list（admin_statusで分岐）
     * Blade: 一般 user/user-application-list.blade.php（$user, $formattedApplications）
     *        管理者 admin/admin-application-list.blade.php（$applications）
     * 承認待ち/承認済みのタブ分けはBladeがapproval_statusで行うので、ここでは全件渡す。
     */
    public function index(): View
    {
        $user = Auth::user();

        // 管理者（FN047〜FN049）: 全員分の申請を出す。
        // 管理者用Bladeは整形済みの配列ではなくモデルのCollectionを受け取り、
        // $application->user->name と $application->AttendanceRecord->date を自分で読む。
        // その2つのリレーションをEager Loadingしておく（N+1を避ける）。
        if ($user->admin_status) {
            $applications = Application::with(['user', 'AttendanceRecord'])
                ->latest()
                ->get();

            return view('admin.admin-application-list', compact('applications'));
        }

        // 対象日時は勤怠の日付を使うので、勤怠をEager Loadingしておく
        $applications = $user->applications()
            ->with('AttendanceRecord')
            ->latest()
            ->get();

        // Figma（申請一覧画面）の表示形式: 対象日時・申請日時とも「2023/06/01」
        $formattedApplications = $applications->map(fn ($application) => [
            'id' => $application->id,
            'approval_status' => $application->approval_status,
            'date' => Carbon::parse($application->AttendanceRecord->date)->format('Y/m/d'),
            'comment' => $application->comment,
            'application_date' => Carbon::parse($application->application_date)->format('Y/m/d'),
        ])->all();

        return view('user.user-application-list', compact('user', 'formattedApplications'));
    }

    /**
     * 申請の「詳細」（FN033）。Blade原文のリンクは /application/{申請id}。
     * FN033は「勤怠詳細画面に遷移」なので、申請に紐づく勤怠の詳細（/attendance/{id}）へリダイレクトする
     * （docs/blade-contract.md 6章「申請一覧（一般）の詳細リンク」）。
     */
    public function show(int $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        abort_if($application->user_id !== Auth::id(), 403);

        return redirect('/attendance/'.$application->attendance_record_id);
    }
}
