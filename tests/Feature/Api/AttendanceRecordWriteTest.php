<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * テストケース一覧 ID18: 公開API 書き込み系
 */
class AttendanceRecordWriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        // Bearer トークンで認証した状態にする
        Sanctum::actingAs($this->user, ['*']);
    }

    public function test_postで勤怠が作成される(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);
    }

    public function test_必須項目が欠けていると422と日本語エラーメッセージが返る(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'date' => '勤怠日は必須です。',
            'clock_in' => '出勤時刻は必須です。',
        ]);
    }

    public function test_形式が不正だと422と日本語エラーメッセージが返る(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026/10/01',
            'clock_in' => '9:00',
            'clock_out' => '18:00',
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'date' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'clock_in' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'comment' => '備考は 255 文字以内で入力してください。',
        ]);
    }

    public function test_同じ日付の勤怠や出勤より前の退勤は422と日本語エラーメッセージが返る(): void
    {
        AttendanceRecord::factory()->create(['user_id' => $this->user->id, 'date' => '2026-10-01']);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '08:00:00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'date' => 'この日付の勤怠は既に登録されています。',
            'clock_out' => '退勤時刻は出勤時刻より後の時刻を指定してください。',
        ]);
    }

    public function test_putで勤怠が更新される(): void
    {
        $record = AttendanceRecord::factory()->create(['user_id' => $this->user->id, 'date' => '2026-10-01']);

        $response = $this->putJson('/api/v1/attendance-records/'.$record->id, [
            'date' => '2026-10-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
    }

    public function test_patchでは送信した項目だけが更新され残りは保持される(): void
    {
        $record = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->patchJson('/api/v1/attendance-records/'.$record->id, ['comment' => '電車遅延']);

        $response->assertOk();
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '電車遅延',
        ]);
    }

    public function test_存在しないidをputすると404が返る(): void
    {
        $response = $this->putJson('/api/v1/attendance-records/99999', ['comment' => '電車遅延']);

        $response->assertNotFound();
        $response->assertExactJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    public function test_deleteで勤怠が削除される(): void
    {
        $record = AttendanceRecord::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson('/api/v1/attendance-records/'.$record->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
    }

    public function test_存在しないidをdeleteすると404が返る(): void
    {
        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertNotFound();
        $response->assertExactJson(['error' => '勤怠情報が見つかりませんでした。']);
    }
}
