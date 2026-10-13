<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID17: 公開API 読み取り系
 */
class AttendanceRecordReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠一覧がjsonで取得できる(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['*' => ['id', 'user_id', 'date', 'clock_in', 'clock_out', 'total_time', 'total_break_time', 'comment']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        // 1ページの件数はデフォルトの20件
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', AttendanceRecord::count());
    }

    public function test_勤怠一覧をuser_id・date・monthで絞り込める(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-09-30']);
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-10-01']);
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-10-02']);
        AttendanceRecord::factory()->create(['user_id' => $otherUser->id, 'date' => '2026-10-01']);

        $this->getJson('/api/v1/attendance-records?user_id='.$user->id)->assertJsonPath('meta.total', 3);
        $this->getJson('/api/v1/attendance-records?date=2026-10-01')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/attendance-records?month=2026-10&user_id='.$user->id)->assertJsonPath('meta.total', 2);
    }

    public function test_勤怠一覧のper_pageは100件まで指定できる(): void
    {
        $this->getJson('/api/v1/attendance-records?per_page=100')->assertOk();
        $this->getJson('/api/v1/attendance-records?per_page=101')->assertUnprocessable();
    }

    public function test_勤怠詳細がユーザー・休憩・修正申請を含めてjsonで取得できる(): void
    {
        $user = User::factory()->create(['name' => 'テスト太郎']);
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-10-01']);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
        Application::factory()->create(['user_id' => $user->id, 'attendance_record_id' => $record->id]);

        $response = $this->getJson('/api/v1/attendance-records/'.$record->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $record->id);
        $response->assertJsonPath('data.date', '2026-10-01');
        $response->assertJsonPath('data.user.name', 'テスト太郎');
        $response->assertJsonPath('data.breaks.0.break_in', '12:00:00');
        $response->assertJsonCount(1, 'data.applications');
    }

    public function test_存在しないidでは404とエラーのjsonが返る(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertNotFound();
        $response->assertExactJson(['error' => '勤怠情報が見つかりませんでした。']);
    }
}
