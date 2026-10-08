<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID9: 勤怠一覧情報取得機能（一般ユーザー）
 */
class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 「今月」を 2026年10月 にする
        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));

        $this->user = User::factory()->create();
    }

    /**
     * ログインユーザーの勤怠を1件作る。どの日の勤怠か一覧で見分けられるよう、出勤時刻を変えて使う。
     */
    private function createRecord(string $date, string $clockIn, ?User $user = null): AttendanceRecord
    {
        return AttendanceRecord::factory()->create([
            'user_id' => ($user ?? $this->user)->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => '18:00:00',
        ]);
    }

    public function test_自分が行った勤怠情報が全て表示されている(): void
    {
        $this->createRecord('2026-10-01', '08:01:00');
        $this->createRecord('2026-10-02', '08:02:00');
        $this->createRecord('2026-10-05', '08:05:00');

        // 他のユーザーの勤怠は表示されない
        $otherUser = User::factory()->create();
        $this->createRecord('2026-10-01', '07:30:00', $otherUser);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertSee('08:01');
        $response->assertSee('08:02');
        $response->assertSee('08:05');
        $response->assertDontSee('07:30');
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance/list');

        // Blade の current-month は $date->format('Y/m')
        $response->assertSee('2026/10');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $this->createRecord('2026-09-30', '08:30:00');

        // 「前月」リンクは href="?date={{ $previousMonth }}"（Y-m 形式）
        $response = $this->actingAs($this->user)->get('/attendance/list?date=2026-09');

        $response->assertSee('2026/09');
        $response->assertSee('08:30');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $this->createRecord('2026-11-02', '08:45:00');

        $response = $this->actingAs($this->user)->get('/attendance/list?date=2026-11');

        $response->assertSee('2026/11');
        $response->assertSee('08:45');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $record = $this->createRecord('2026-10-01', '08:01:00');

        // 一覧に、その日の詳細画面へのリンクがある
        $this->actingAs($this->user)->get('/attendance/list')
            ->assertSee('/attendance/'.$record->id);

        // リンク先を開くと、その日の勤怠詳細画面が表示される
        $response = $this->get('/attendance/'.$record->id);

        $response->assertStatus(200);
        $response->assertViewIs('user.user-detail');
        $response->assertSee('10月1日');
    }
}
