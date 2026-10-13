<?php

namespace Tests\Feature\Report;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID20: マイ勤怠レポート機能
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 「今月」を 2026年10月 にする（過去6ヶ月は 2026/05〜2026/10）
        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));

        $this->user = User::factory()->create();
    }

    /**
     * 休憩 12:00-13:00 付きの勤怠を1件作る。
     */
    private function createRecord(string $date, string $clockIn, ?string $clockOut, ?User $user = null): void
    {
        $record = AttendanceRecord::factory()->create([
            'user_id' => ($user ?? $this->user)->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
    }

    public function test_ゲストはレポートページにアクセスできずログイン画面へリダイレクトされる(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報が正しく計算される(): void
    {
        $this->createRecord('2026-10-01', '09:00:00', '18:00:00'); // 8h（通常）
        $this->createRecord('2026-10-02', '09:30:00', '20:00:00'); // 9h30m（遅刻・残業1h30m）
        $this->createRecord('2026-10-05', '08:00:00', '21:00:00'); // 12h（長時間労働・残業4h）
        $this->createRecord('2026-10-06', '09:00:00', '17:00:00'); // 7h（早退）
        $this->createRecord('2026-08-03', '09:00:00', '19:00:00'); // 9h（残業1h）

        // 集計しないもの: 6ヶ月より前の月、退勤前の勤怠、他のユーザーの勤怠
        $this->createRecord('2026-04-30', '09:00:00', '18:00:00');
        $this->createRecord('2026-10-15', '10:00:00', null);
        $this->createRecord('2026-10-07', '10:00:00', '23:00:00', User::factory()->create());

        $response = $this->actingAs($this->user)->get('/attendance/report');

        $response->assertOk();
        // 労働時間の合計 480+570+720+420+540 = 2730分、残業 90+240+60 = 390分、平均 2730÷5日 = 546分
        $response->assertViewHas('summary', [
            'total_work_minutes' => 2730,
            'total_overtime_minutes' => 390,
            'avg_work_minutes' => 546,
        ]);
        $response->assertViewHas('monthlyTrend', [
            ['month' => '2026/05', 'work_minutes' => 0, 'overtime_minutes' => 0],
            ['month' => '2026/06', 'work_minutes' => 0, 'overtime_minutes' => 0],
            ['month' => '2026/07', 'work_minutes' => 0, 'overtime_minutes' => 0],
            ['month' => '2026/08', 'work_minutes' => 540, 'overtime_minutes' => 60],
            ['month' => '2026/09', 'work_minutes' => 0, 'overtime_minutes' => 0],
            ['month' => '2026/10', 'work_minutes' => 2190, 'overtime_minutes' => 330],
        ]);
        // 9:00 ちょうどの出勤・18:00 以降の退勤・ちょうど10時間以下は数えない
        $response->assertViewHas('anomalies', [
            'late_count' => 1,
            'early_leave_count' => 1,
            'long_work_count' => 1,
        ]);
    }

    public function test_勤怠記録がないユーザーでもエラーにならず各統計が0になる(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance/report');

        $response->assertOk();
        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);
        // データが無い月も0として6ヶ月分並ぶ（FN054）
        $response->assertViewHas('monthlyTrend', fn (array $monthlyTrend) => count($monthlyTrend) === 6
            && collect($monthlyTrend)->every(fn (array $row) => $row['work_minutes'] === 0 && $row['overtime_minutes'] === 0));
        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);
    }
}
