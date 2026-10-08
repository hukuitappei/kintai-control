<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID5: ステータス確認機能
 */
class StatusTest extends TestCase
{
    use CreatesAttendanceStatus;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 7, 9, 0));
    }

    public function test_勤務外の場合勤怠ステータスが正しく表示される(): void
    {
        $user = $this->createOffDutyUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合勤怠ステータスが正しく表示される(): void
    {
        $user = $this->createWorkingUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合勤怠ステータスが正しく表示される(): void
    {
        $user = $this->createOnBreakUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合勤怠ステータスが正しく表示される(): void
    {
        $user = $this->createFinishedUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}
