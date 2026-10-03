<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

/**
 * 月次の勤怠一覧の表示データを組み立てる。
 * 一般ユーザーの勤怠一覧（AttendanceController::index()、FN023〜FN025）と
 * 管理者のスタッフ別月次勤怠一覧（Admin\AttendanceController::staff()、FN043〜FN044）で共用する。
 * どちらのBladeも同じ変数（$date, $previousMonth, $nextMonth, $formattedAttendanceRecords）を受け取る。
 */
trait BuildsMonthlyAttendance
{
    /**
     * @return array{date: Carbon, previousMonth: string, nextMonth: string, formattedAttendanceRecords: array}
     */
    private function monthlyAttendanceData(User $user, Request $request): array
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m']]);

        // 「!」を付けると、書式に無い部分（日・時刻）が今日ではなく初期値（1日 00:00:00）になる。
        // 付けないと、今日が31日のときに createFromFormat('Y-m', '2026-02') が「2月31日」→3月3日にずれる。
        $date = $request->filled('date')
            ? Carbon::createFromFormat('!Y-m', $request->input('date'))
            : now()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // その月の勤怠を、休憩ごとまとめて取得する（Eager Loading。N+1を避ける）。
        // keyBy()で「'2026-09-01' => 勤怠」の形にして、日付から引けるようにしておく。
        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()])
            ->get()
            ->keyBy('date');

        // 勤怠が無い日も1行として並べる（FN023: 勤怠情報が無いフィールドは空白）。
        $formattedAttendanceRecords = [];
        foreach (CarbonPeriod::create($date->copy()->startOfMonth(), $date->copy()->endOfMonth()) as $day) {
            $record = $attendanceRecords->get($day->toDateString());

            $formattedAttendanceRecords[] = [
                'date' => $day->isoFormat('MM/DD(ddd)'),
                'clock_in' => $record ? Carbon::parse($record->clock_in)->format('H:i') : '',
                'clock_out' => $record?->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                'total_break_time' => $record?->total_break_time,
                'total_time' => $record?->total_time,
                // idが空の行はBladeが「詳細」リンクを出さない（FN025）
                'id' => $record?->id,
            ];
        }

        return compact('date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords');
    }
}
