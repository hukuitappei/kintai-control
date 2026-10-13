<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * 残業とみなす1日の労働時間（分）。FN053（8時間/日を超えた分）。
     */
    private const REGULAR_WORK_MINUTES = 8 * 60;

    /**
     * 長時間労働とみなす1日の労働時間（分）。FN056（10時間を超えた日）。
     */
    private const LONG_WORK_MINUTES = 10 * 60;

    /**
     * マイ勤怠レポート（一般ユーザー）。FN052〜FN054、FN056。
     * 過去6ヶ月（当月を含む）の勤怠から、基本サマリー・月次推移・当月の異常検知を集計する。
     *
     * @return View マイ勤怠レポート画面のビュー
     */
    public function index(): View
    {
        $from = now()->subMonthsNoOverflow(5)->startOfMonth();

        // 退勤前の勤怠は労働時間が決まらないため集計しない
        $attendanceRecords = Auth::user()->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$from->toDateString(), now()->endOfMonth()->toDateString()])
            ->whereNotNull('clock_out')
            ->get();

        return view('reports.index', [
            'summary' => $this->summary($attendanceRecords),
            'monthlyTrend' => $this->monthlyTrend($attendanceRecords, $from),
            'anomalies' => $this->anomalies($attendanceRecords),
        ]);
    }

    /**
     * 基本サマリー（総労働時間・総残業時間・1日あたりの平均労働時間）。FN053。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  集計する勤怠
     * @return array{total_work_minutes: int, total_overtime_minutes: int, avg_work_minutes: int} いずれも分
     */
    private function summary(Collection $attendanceRecords): array
    {
        $totalWorkMinutes = $attendanceRecords->sum('work_minutes');

        return [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $this->overtimeMinutes($attendanceRecords),
            // 勤怠が無いときに0で割らないようにする
            'avg_work_minutes' => $attendanceRecords->isEmpty() ? 0 : intdiv($totalWorkMinutes, $attendanceRecords->count()),
        ];
    }

    /**
     * 月次推移（過去6ヶ月の月別の労働時間・残業時間）。データが無い月は0。FN054。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  集計する勤怠
     * @param  Carbon  $from  集計を始める月（6ヶ月前の月初）
     * @return array<int, array{month: string, work_minutes: int, overtime_minutes: int}>
     */
    private function monthlyTrend(Collection $attendanceRecords, Carbon $from): array
    {
        $recordsByMonth = $attendanceRecords->groupBy(fn (AttendanceRecord $record) => Carbon::parse($record->date)->format('Y/m'));

        return collect(range(0, 5))
            ->map(function (int $i) use ($from, $recordsByMonth) {
                $month = $from->copy()->addMonths($i)->format('Y/m');
                $records = $recordsByMonth->get($month, collect());

                return [
                    'month' => $month,
                    'work_minutes' => $records->sum('work_minutes'),
                    'overtime_minutes' => $this->overtimeMinutes($records),
                ];
            })
            ->all();
    }

    /**
     * 当月の異常検知（遅刻・早退・長時間労働の回数）。FN056。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  集計する勤怠
     * @return array{late_count: int, early_leave_count: int, long_work_count: int}
     */
    private function anomalies(Collection $attendanceRecords): array
    {
        $currentMonthRecords = $attendanceRecords
            ->filter(fn (AttendanceRecord $record) => Carbon::parse($record->date)->isSameMonth(now()));

        return [
            // 時刻は "H:i:s" の文字列なので、そのまま大小を比べられる
            'late_count' => $currentMonthRecords->filter(fn (AttendanceRecord $record) => $record->clock_in > '09:00:00')->count(),
            'early_leave_count' => $currentMonthRecords->filter(fn (AttendanceRecord $record) => $record->clock_out < '18:00:00')->count(),
            'long_work_count' => $currentMonthRecords->filter(fn (AttendanceRecord $record) => $record->work_minutes > self::LONG_WORK_MINUTES)->count(),
        ];
    }

    /**
     * 残業時間の合計（分）。1日ごとに8時間を超えた分だけを足す。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  集計する勤怠
     * @return int 残業時間の合計（分）
     */
    private function overtimeMinutes(Collection $attendanceRecords): int
    {
        return $attendanceRecords->sum(fn (AttendanceRecord $record) => max(0, $record->work_minutes - self::REGULAR_WORK_MINUTES));
    }
}
