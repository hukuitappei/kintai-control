<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;

/**
 * 打刻画面のステータス（勤務外/出勤中/休憩中/退勤済）になっているユーザーを作る。
 * User::attendance_status アクセサの判定条件に合わせて「今日」の勤怠を用意する。
 */
trait CreatesAttendanceStatus
{
    /**
     * 勤務外: 今日の勤怠が無い。
     */
    private function createOffDutyUser(): User
    {
        return User::factory()->create();
    }

    /**
     * 出勤中: 今日の勤怠があり、退勤していない。
     */
    private function createWorkingUser(): User
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        return $user;
    }

    /**
     * 休憩中: 出勤中で、終わっていない休憩がある。
     */
    private function createOnBreakUser(): User
    {
        $user = $this->createWorkingUser();

        BreakTime::factory()->create([
            'attendance_record_id' => $user->attendanceRecords()->first()->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        return $user;
    }

    /**
     * 退勤済: 今日の勤怠があり、退勤している。
     */
    private function createFinishedUser(): User
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        return $user;
    }
}
