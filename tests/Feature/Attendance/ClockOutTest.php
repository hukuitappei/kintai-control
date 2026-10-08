<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID8: 退勤機能
 */
class ClockOutTest extends TestCase
{
    use CreatesAttendanceStatus;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 7, 18, 0));
    }

    public function test_退勤ボタンが正しく機能する(): void
    {
        $user = $this->createWorkingUser();

        $this->actingAs($user)->get('/attendance')
            ->assertSee('>退勤</button>', false);

        $this->post('/attendance', ['action' => 'clock_out']);

        $this->get('/attendance')->assertSee('退勤済');
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $this->travelTo(Carbon::create(2026, 10, 7, 9, 0));
        $user = $this->createOffDutyUser();

        // 9:00 に出勤 → 18:00 に退勤
        $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);
        $this->travelTo(Carbon::create(2026, 10, 7, 18, 0));
        $this->post('/attendance', ['action' => 'clock_out']);

        $this->get('/attendance/list')->assertSee('18:00');
    }
}
