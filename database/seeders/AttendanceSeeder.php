<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * user1 以外の全ユーザーに、直近の平日30日分の勤怠（09:00-18:00）と休憩（12:00-13:00）を作成する。
     * user1 はマイ勤怠レポートの想定値に合わせるため、ReportAttendanceSeeder で作成する。
     */
    public function run(): void
    {
        // 昨日から遡った平日30日分（60日遡れば平日は30日以上ある）
        $dates = collect(range(1, 60))
            ->map(fn (int $daysAgo) => Carbon::today()->subDays($daysAgo))
            ->filter(fn (Carbon $date) => $date->isWeekday())
            ->take(30);

        User::where('email', '!=', 'user1@example.com')->get()->each(function (User $user) use ($dates) {
            $dates->each(function (Carbon $date) use ($user) {
                $record = $user->attendanceRecords()->create([
                    'date' => $date->toDateString(),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                ]);

                $record->breaks()->create([
                    'break_in' => '12:00:00',
                    'break_out' => '13:00:00',
                ]);
            });
        });
    }
}
