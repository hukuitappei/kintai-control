<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * テストケース一覧 ID19: Sanctum 認証
 */
class AttendanceRecordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証時に書き込み系のapiで401が返る(): void
    {
        $record = AttendanceRecord::factory()->create();
        $url = '/api/v1/attendance-records/'.$record->id;

        $this->postJson('/api/v1/attendance-records', ['date' => '2026-10-01', 'clock_in' => '09:00:00'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
        $this->putJson($url, ['comment' => '電車遅延'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
        $this->deleteJson($url)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_認証済みユーザーは自分の勤怠を更新・削除できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/attendance-records/'.$record->id, ['comment' => '電車遅延'])->assertOk();
        $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'comment' => '電車遅延']);

        $this->deleteJson('/api/v1/attendance-records/'.$record->id)->assertNoContent();
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
    }

    public function test_他ユーザーの勤怠を更新・削除しようとすると403が返る(): void
    {
        $otherRecord = AttendanceRecord::factory()->create(['comment' => null]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->putJson('/api/v1/attendance-records/'.$otherRecord->id, ['comment' => '電車遅延'])
            ->assertForbidden()
            ->assertExactJson(['error' => 'この操作を実行する権限がありません。']);
        $this->deleteJson('/api/v1/attendance-records/'.$otherRecord->id)
            ->assertForbidden()
            ->assertExactJson(['error' => 'この操作を実行する権限がありません。']);

        // 変更・削除されていない
        $this->assertDatabaseHas('attendance_records', ['id' => $otherRecord->id, 'comment' => null]);
    }

    public function test_管理者は他ユーザーの勤怠を更新・削除できる(): void
    {
        $otherRecord = AttendanceRecord::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

        $this->putJson('/api/v1/attendance-records/'.$otherRecord->id, ['comment' => '管理者が修正'])->assertOk();
        $this->assertDatabaseHas('attendance_records', ['id' => $otherRecord->id, 'comment' => '管理者が修正']);

        $this->deleteJson('/api/v1/attendance-records/'.$otherRecord->id)->assertNoContent();
        $this->assertDatabaseMissing('attendance_records', ['id' => $otherRecord->id]);
    }
}
