<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID14: ユーザー情報取得機能（管理者）
 */
class StaffTest extends TestCase
{
    use ActsAsAdmin;
    use RefreshDatabase;

    private User $staff1;

    private User $staff2;

    protected function setUp(): void
    {
        parent::setUp();

        // 「今月」を 2026年10月 にする
        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));

        $this->staff1 = User::factory()->create(['name' => 'スタッフ 一郎', 'email' => 'staff1@example.com']);
        $this->staff2 = User::factory()->create(['name' => 'スタッフ 二郎', 'email' => 'staff2@example.com']);
    }

    private function createRecord(User $user, string $date, string $clockIn): AttendanceRecord
    {
        return AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => '18:00:00',
        ]);
    }

    public function test_管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $response = $this->actingAsAdmin()->get('/admin/staff/list');

        $response->assertSee('スタッフ 一郎');
        $response->assertSee('staff1@example.com');
        $response->assertSee('スタッフ 二郎');
        $response->assertSee('staff2@example.com');
        // スタッフ一覧に管理者は含めない（FN041「全一般ユーザー」）
        $response->assertDontSee('管理 者');
    }

    public function test_ユーザーの勤怠情報が正しく表示される(): void
    {
        $this->createRecord($this->staff1, '2026-10-01', '08:01:00');
        // 他のスタッフの勤怠は出ない
        $this->createRecord($this->staff2, '2026-10-01', '07:30:00');

        $response = $this->actingAsAdmin()->get('/admin/attendance/staff/'.$this->staff1->id);

        $response->assertSee('スタッフ 一郎さんの勤怠');
        $response->assertSee('08:01');
        $response->assertDontSee('07:30');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $this->createRecord($this->staff1, '2026-09-30', '08:30:00');

        $response = $this->actingAsAdmin()->get('/admin/attendance/staff/'.$this->staff1->id.'?date=2026-09');

        $response->assertSee('2026/09');
        $response->assertSee('08:30');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $this->createRecord($this->staff1, '2026-11-02', '08:45:00');

        $response = $this->actingAsAdmin()->get('/admin/attendance/staff/'.$this->staff1->id.'?date=2026-11');

        $response->assertSee('2026/11');
        $response->assertSee('08:45');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $record = $this->createRecord($this->staff1, '2026-10-01', '08:01:00');

        $this->actingAsAdmin()->get('/admin/attendance/staff/'.$this->staff1->id)
            ->assertSee('/attendance/'.$record->id);

        $response = $this->get('/attendance/'.$record->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-detail');
        $response->assertSee('10月1日');
    }
}
