<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportAttendanceSeeder extends Seeder
{
    /**
     * 当月17日分の勤務パターン（出勤・退勤）。開発プロセスシートの「user1 の意図的データ」。
     */
    private const CURRENT_MONTH_PATTERNS = [
        ['count' => 10, 'clock_in' => '09:00:00', 'clock_out' => '18:00:00'], // 通常
        ['count' => 3, 'clock_in' => '09:00:00', 'clock_out' => '20:00:00'], // 残業
        ['count' => 2, 'clock_in' => '09:30:00', 'clock_out' => '18:00:00'], // 遅刻
        ['count' => 1, 'clock_in' => '09:00:00', 'clock_out' => '17:00:00'], // 早退
        ['count' => 1, 'clock_in' => '08:00:00', 'clock_out' => '21:00:00'], // 長時間労働
    ];

    /**
     * user1 に、マイ勤怠レポートの想定値（総労働744h／残業10h／平均8h5m／遅刻2／早退1／長時間1）になる勤怠を作成する。
     * 過去5ヶ月は各月の平日15日を通常勤務、当月は平日17日を上記のパターンにし、全レコードに休憩 12:00-13:00 を付ける。
     */
    public function run(): void
    {
        $user = User::where('email', 'user1@example.com')->firstOrFail();

        $pastMonths = collect(range(5, 1))
            ->flatMap(fn (int $monthsAgo) => $this->weekdays(now()->subMonthsNoOverflow($monthsAgo), 15)
                ->map(fn (Carbon $date) => ['date' => $date, 'clock_in' => '09:00:00', 'clock_out' => '18:00:00']));

        $patterns = collect(self::CURRENT_MONTH_PATTERNS)
            ->flatMap(fn (array $pattern) => array_fill(0, $pattern['count'], $pattern));

        $currentMonth = $this->weekdays(now(), $patterns->count())
            ->zip($patterns)
            ->map(fn (Collection $pair) => ['date' => $pair[0]] + $pair[1]);

        $pastMonths->concat($currentMonth)->each(function (array $attendance) use ($user) {
            $record = $user->attendanceRecords()->create([
                'date' => $attendance['date']->toDateString(),
                'clock_in' => $attendance['clock_in'],
                'clock_out' => $attendance['clock_out'],
            ]);

            $record->breaks()->create([
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
            ]);
        });
    }

    /**
     * 指定した月の1日から数えて、平日を指定した日数分返す。
     *
     * @param  Carbon  $month  対象の月（日は問わない）
     * @param  int  $count  返す平日の日数
     * @return Collection<int, Carbon> 平日の日付
     */
    private function weekdays(Carbon $month, int $count): Collection
    {
        return collect(range(0, $month->daysInMonth - 1))
            ->map(fn (int $day) => $month->copy()->startOfMonth()->addDays($day))
            ->filter(fn (Carbon $date) => $date->isWeekday())
            ->take($count)
            ->values();
    }
}
