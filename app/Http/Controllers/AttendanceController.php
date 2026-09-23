<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面（打刻画面）を表示する。FN018〜FN019。
     * PG03: /attendance
     */
    public function create(): View
    {
        $user = Auth::user();
        $formattedDate = now()->isoFormat('YYYY年M月D日(ddd)');
        $formattedTime = now()->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 打刻処理。resources/views/user/attendance-register.blade.php の
     * ボタン（name="action"）の値ごとに処理を振り分ける。
     * 4つのvalue="..."をBladeを見て埋めること。
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        match ($request->input('action')) {
            'clock_in' => $this->clockIn($user),
            'break_in' => $this->breakIn($user),
            'break_out' => $this->breakOut($user),
            'clock_out' => $this->clockOut($user),
            default => null,
        };

        return redirect('/attendance');
    }

    /**
     * 今日のattendance_recordsを1件取得する（無ければnull）。
     * User::getAttendanceStatusAttribute()と同じ絞り込み方。
     */
    private function todayRecord(User $user): ?AttendanceRecord
    {
        return $user->attendanceRecords()->whereDate('date', now())->first();
    }

    /**
     * 出勤処理。FN020: 「___」のときのみ、1日1回だけ出勤できる。
     * 属性値はUser::getAttendanceStatusAttribute()が返す文字列と揃えること。
     */
    private function clockIn(User $user): void
    {
        if ($user->attendance_status !== '勤務外') {
            return;
        }

        $user->attendanceRecords()->create([
            'date' => today()->toDateString(),
            'clock_in' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 休憩入処理。FN021: 「___」のときのみ、何回でも押下できる。
     */
    private function breakIn(User $user): void
    {
        if ($user->attendance_status !== '出勤中') {
            return;
        }

        $this->todayRecord($user)->breaks()->create([
            'break_in' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 休憩戻処理。FN021: 「___」のときのみ、何回でも押下できる。
     * まだbreak_outが入っていない（休憩中の）breaksレコードを更新する。
     */
    private function breakOut(User $user): void
    {
        if ($user->attendance_status !== '休憩中') {
            return;
        }

        $this->todayRecord($user)->breaks()->whereNull('break_out')->first()->update([
            'break_out' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 退勤処理。FN022: 「___」のときのみ、1日1回だけ押下できる。
     */
    private function clockOut(User $user): void
    {
        if ($user->attendance_status !== '出勤中') {
            return;
        }

        $this->todayRecord($user)->update([
            'clock_out' => now()->format('H:i:s'),
        ]);
    }
}
