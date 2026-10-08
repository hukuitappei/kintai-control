<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID6: 出勤機能
 */
class ClockInTest extends TestCase
{
    use CreatesAttendanceStatus;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 7, 9, 0));
    }

    public function test_出勤ボタンが正しく機能する(): void
    {
        $user = $this->createOffDutyUser();

        // 第2引数 false: HTMLタグを含む文字列をエスケープせずに探す
        $this->actingAs($user)->get('/attendance')
            ->assertSee('>出勤</button>', false);

        $this->post('/attendance', ['action' => 'clock_in']);

        // 要件の「勤務中」は、このアプリでは「出勤中」と表示する（FN019）
        $this->get('/attendance')->assertSee('出勤中');
    }

    public function test_出勤は一日一回のみできる(): void
    {
        $user = $this->createFinishedUser();

        $this->actingAs($user)->get('/attendance')
            ->assertDontSee('>出勤</button>', false);
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $user = $this->createOffDutyUser();

        $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $this->get('/attendance/list')->assertSee('09:00');
    }
}
