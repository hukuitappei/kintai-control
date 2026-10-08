<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID12: 勤怠一覧情報取得機能（管理者）
 */
class AdminAttendanceListTest extends TestCase
{
    use ActsAsAdmin;
    use RefreshDatabase;

    private User $staff1;

    private User $staff2;

    protected function setUp(): void
    {
        parent::setUp();

        // 「今日」を 2026-10-07 にする
        $this->travelTo(Carbon::create(2026, 10, 7, 10, 0));

        $this->staff1 = User::factory()->create(['name' => 'スタッフ 一郎']);
        $this->staff2 = User::factory()->create(['name' => 'スタッフ 二郎']);

        // どの日の勤怠か見分けられるよう、出勤時刻を変えておく
        $this->createRecord($this->staff1, '2026-10-07', '09:01:00');
        $this->createRecord($this->staff2, '2026-10-07', '09:02:00');
        $this->createRecord($this->staff1, '2026-10-06', '08:31:00');
        $this->createRecord($this->staff2, '2026-10-08', '08:45:00');
    }

    private function createRecord(User $user, string $date, string $clockIn): void
    {
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => '18:00:00',
        ]);
    }

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $response = $this->actingAsAdmin()->get('/admin/attendance/list');

        $response->assertSee('スタッフ 一郎');
        $response->assertSee('09:01');
        $response->assertSee('スタッフ 二郎');
        $response->assertSee('09:02');
        // 別の日の勤怠は出ない
        $response->assertDontSee('08:31');
    }

    public function test_遷移した際に現在の日付が表示される(): void
    {
        $response = $this->actingAsAdmin()->get('/admin/attendance/list');

        // Blade の current-day は $date->format('Y/m/d')
        $response->assertSee('2026/10/07');
    }

    public function test_前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        // 「前日」リンクは href="?date={{ $previousDay }}"（Y-m-d 形式）
        $response = $this->actingAsAdmin()->get('/admin/attendance/list?date=2026-10-06');

        $response->assertSee('2026/10/06');
        $response->assertSee('08:31');
    }

    public function test_翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        $response = $this->actingAsAdmin()->get('/admin/attendance/list?date=2026-10-08');

        $response->assertSee('2026/10/08');
        $response->assertSee('08:45');
    }
}
