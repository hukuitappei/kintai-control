<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID7: 休憩機能
 */
class BreakTest extends TestCase
{
    use CreatesAttendanceStatus;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 7, 12, 0));
    }

    public function test_休憩ボタンが正しく機能する(): void
    {
        $user = $this->createWorkingUser();

        $this->actingAs($user)->get('/attendance')
            ->assertSee('>休憩入</button>', false);

        $this->post('/attendance', ['action' => 'break_in']);

        $this->get('/attendance')->assertSee('休憩中');
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $user = $this->createWorkingUser();

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->post('/attendance', ['action' => 'break_out']);

        $this->get('/attendance')->assertSee('>休憩入</button>', false);
    }

    public function test_休憩戻ボタンが正しく機能する(): void
    {
        $user = $this->createWorkingUser();

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->get('/attendance')->assertSee('>休憩戻</button>', false);

        $this->post('/attendance', ['action' => 'break_out']);

        $this->get('/attendance')->assertSee('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $user = $this->createWorkingUser();

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->post('/attendance', ['action' => 'break_out']);
        $this->post('/attendance', ['action' => 'break_in']);

        $this->get('/attendance')->assertSee('>休憩戻</button>', false);
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        $user = $this->createWorkingUser();

        // 12:00 に休憩入 → 30分進めて 12:30 に休憩戻
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->travel(30)->minutes();
        $this->post('/attendance', ['action' => 'break_out']);

        // 一覧の「休憩」列は休憩の合計時間を G:i 形式で表示する
        $this->get('/attendance/list')->assertSee('0:30');
    }
}
