<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * 修正申請承認画面（管理者）。FN050。
     * /stamp_correction_request/approve/{id}（申請一覧〈管理者〉の「詳細」から）
     * Blade: admin/admin-application-detail.blade.php（$application（モデル）, $user）
     */
    public function show(int $id): View
    {
        $application = Application::with('proposalBreaks')->findOrFail($id);

        // 「名前」欄に出すのは申請したユーザー（ログイン中の管理者ではない）
        $user = $application->user;

        return view('admin.admin-application-detail', compact('application', 'user'));
    }

    /**
     * 承認処理（管理者）。FN051。
     * 申請内容で勤怠と休憩を書き換え、申請を「承認済み」にする。
     * attendance_records / breaks を直接更新するので、一般ユーザー側の一覧・詳細・申請一覧にもそのまま反映される。
     */
    public function approve(int $id): RedirectResponse
    {
        $application = Application::with(['proposalBreaks', 'AttendanceRecord'])->findOrFail($id);

        // 承認済みの申請をもう一度承認しない（Bladeは承認済みならボタンを出さないが、POSTを直接送られる場合に備える）
        if ($application->approval_status !== '承認待ち') {
            return redirect('/stamp_correction_request/approve/'.$application->id);
        }

        // 勤怠・休憩・申請の3つを「全部更新できたときだけ」確定させる
        DB::transaction(function () use ($application) {
            $attendanceRecord = $application->AttendanceRecord;

            // キーは attendance_records の列名、値は申請（applications）の列
            $attendanceRecord->update([
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            // 休憩は管理者の直接修正（Phase7-3）と同じく「全部消して、申請の休憩どおりに作り直す」
            $attendanceRecord->breaks()->delete();
            foreach ($application->proposalBreaks as $proposalBreak) {
                $attendanceRecord->breaks()->create([
                    'break_in' => $proposalBreak->break_in,
                    'break_out' => $proposalBreak->break_out,
                ]);
            }

            $application->update(['approval_status' => '承認済み']);
        });

        // 同じ承認画面に戻ると、Bladeが「承認済み」の表示に切り替わる
        return redirect('/stamp_correction_request/approve/'.$application->id);
    }
}
