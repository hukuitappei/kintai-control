<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * 全ユーザーに、直近の平日30日分の勤怠（09:00-18:00）と、固定休憩（12:00-13:00）を作成する。
     * 開発プロセスシート「件数・日付分布は実運用に近い内容にすること」に対応する基本データ。
     * user1の意図的な精密データ（過去5ヶ月分の遅刻・早退等のパターン）は応用（Phase12-1）で別途作成する。
     */
    public function run(): void
    {
        User::all()->each(function (User $user) {
            $date = Carbon::yesterday();
            $created = 0;

            while ($created < 30) {
                if ($date->isWeekday()) {
                    $record = $user->attendanceRecords()->create([
                        'date' => $date->toDateString(),
                        'clock_in' => '09:00:00',
                        'clock_out' => '18:00:00',
                    ]);

                    $record->breaks()->create([
                        'break_in' => '12:00:00',
                        'break_out' => '13:00:00',
                    ]);

                    $created++;
                }

                $date = $date->copy()->subDay();
            }
        });
    }
}
