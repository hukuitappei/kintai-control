<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * 修正申請承認画面（管理者）。FN050。
     *
     * @param  int  $id  修正申請のID
     * @return View 修正申請承認画面（管理者）のビュー
     */
    public function show(int $id): View
    {
        $application = Application::with('proposalBreaks')->findOrFail($id);

        // 名前欄には申請者を表示する
        $user = $application->user;

        // 提供Bladeは出勤・退勤を整形せずに表示するため、H:i にしてから渡す（保存はしない）
        $application->new_clock_in = Carbon::parse($application->new_clock_in)->format('H:i');
        $application->new_clock_out = $application->new_clock_out ? Carbon::parse($application->new_clock_out)->format('H:i') : null;

        return view('admin.admin-application-detail', compact('application', 'user'));
    }

    /**
     * 承認処理（管理者）。申請内容で勤怠と休憩を更新し、申請を承認済みにする。FN051。
     *
     * @param  int  $id  修正申請のID
     * @return RedirectResponse 修正申請承認画面（管理者）へのリダイレクト
     */
    public function approve(int $id): RedirectResponse
    {
        $application = Application::with(['proposalBreaks', 'AttendanceRecord'])->findOrFail($id);

        if ($application->approval_status !== '承認待ち') {
            return redirect('/stamp_correction_request/approve/'.$application->id);
        }

        DB::transaction(function () use ($application) {
            $attendanceRecord = $application->AttendanceRecord;

            $attendanceRecord->update([
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            // 休憩は申請の内容どおりに作り直す
            $attendanceRecord->breaks()->delete();
            $attendanceRecord->breaks()->createMany(
                $application->proposalBreaks->map(fn ($proposalBreak) => [
                    'break_in' => $proposalBreak->break_in,
                    'break_out' => $proposalBreak->break_out,
                ])->all()
            );

            $application->update(['approval_status' => '承認済み']);
        });

        return redirect('/stamp_correction_request/approve/'.$application->id);
    }
}
