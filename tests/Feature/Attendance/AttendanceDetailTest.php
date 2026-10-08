<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID10: 勤怠詳細情報取得機能（一般ユーザー）
 */
class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AttendanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));

        $this->user = User::factory()->create(['name' => '勤怠 太郎']);

        $this->record = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:10:00',
            'clock_out' => '18:20:00',
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $this->record->id,
            'break_in' => '12:15:00',
            'break_out' => '13:05:00',
        ]);
    }

    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance/'.$this->record->id);

        $response->assertSee('勤怠 太郎');
    }

    public function test_勤怠詳細画面の日付が選択した日付になっている(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance/'.$this->record->id);

        // 日付欄は「年」と「月日」の2つの入力欄に分かれている（AttendanceController::detailData）
        $response->assertSee('2026年');
        $response->assertSee('10月7日');
    }

    public function test_出勤退勤にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance/'.$this->record->id);

        $response->assertSee('09:10');
        $response->assertSee('18:20');
    }

    public function test_休憩にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance/'.$this->record->id);

        $response->assertSee('12:15');
        $response->assertSee('13:05');
    }
}
