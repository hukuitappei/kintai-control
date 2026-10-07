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
     * 申請一覧。一般ユーザー（FN031〜FN032）と管理者（FN047〜FN049）で同じURLを使い、どちらのログイン画面から入ったかで分岐する。
     * 承認待ち/承認済みのタブ分けはBladeが行う。
     */
    public function index(): View
    {
        $user = Auth::user();

        if ($this->loggedInAsAdmin()) {
            $applications = Application::with(['user', 'AttendanceRecord'])
                ->latest()
                ->get();

            return view('admin.admin-application-list', compact('applications'));
        }

        $applications = $user->applications()
            ->with('AttendanceRecord')
            ->latest()
            ->get();

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
     * 申請一覧の「詳細」（提供Bladeのリンクは /application/{申請id}）。紐づく勤怠詳細へ遷移する。FN033。
     */
    public function show(int $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        abort_if($application->user_id !== Auth::id(), 403);

        return redirect('/attendance/'.$application->attendance_record_id);
    }
}
