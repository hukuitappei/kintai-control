<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID4: 日時取得機能
 */
class DateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報が_u_iと同じ形式で出力されている(): void
    {
        // 「現在の日時」をテスト中だけ 2026-10-07(水) 09:05 に固定する（Phase9-6）
        $this->travelTo(Carbon::create(2026, 10, 7, 9, 5));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('2026年10月7日(水)');
        $response->assertSee('09:05');
    }
}
