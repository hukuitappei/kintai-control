<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

/**
 * 月次勤怠一覧の表示データ。一般ユーザーの勤怠一覧とスタッフ別月次勤怠一覧で共用する。
 */
trait BuildsMonthlyAttendance
{
    /**
     * 指定した月の勤怠を、勤怠が無い日も含めて1日1行の表示データにする。
     *
     * @param  User  $user  勤怠を表示するユーザー
     * @param  Request  $request  表示する月（Y-m）を持つリクエスト
     * @return array{date: Carbon, previousMonth: string, nextMonth: string, formattedAttendanceRecords: array}
     */
    private function monthlyAttendanceData(User $user, Request $request): array
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m']]);

        // 「!」で日を1日に固定する（月末日に実行すると翌月にずれるのを防ぐ）
        $date = $request->filled('date')
            ? Carbon::createFromFormat('!Y-m', $request->input('date'))
            : now()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()])
            ->get()
            ->keyBy('date');

        // 勤怠が無い日も1行として並べる
        $formattedAttendanceRecords = collect(CarbonPeriod::create($date->copy()->startOfMonth(), $date->copy()->endOfMonth()))
            ->map(function ($day) use ($attendanceRecords) {
                $record = $attendanceRecords->get($day->toDateString());

                return [
                    'date' => $day->isoFormat('MM/DD(ddd)'),
                    'clock_in' => $record ? Carbon::parse($record->clock_in)->format('H:i') : '',
                    'clock_out' => $record?->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                    'total_break_time' => $record?->total_break_time,
                    'total_time' => $record?->total_time,
                    'id' => $record?->id,
                ];
            })
            ->all();

        return compact('date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords');
    }
}
