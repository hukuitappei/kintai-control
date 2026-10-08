<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID13: 勤怠詳細情報取得・修正機能（管理者）
 */
class AdminAttendanceDetailTest extends TestCase
{
    use ActsAsAdmin;
    use RefreshDatabase;

    private AttendanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));

        $staff = User::factory()->create(['name' => 'スタッフ 三郎']);

        $this->record = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
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

    /**
     * 勤怠詳細フォームの正しい入力。各テストで1項目だけ上書きして使う。
     *
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [0 => '12:00'],
            'new_break_out' => [0 => '13:00'],
            'comment' => '打刻漏れのため',
        ], $overrides);
    }

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $response = $this->actingAsAdmin()->get('/attendance/'.$this->record->id);

        // 管理者ログインから入ったので管理者用の詳細画面になる（C6）
        $response->assertViewIs('admin.admin-detail');
        // 名前欄はログイン中の管理者ではなく、勤怠の持ち主
        $response->assertSee('スタッフ 三郎');
        $response->assertSee('10月7日');
        $response->assertSee('09:10');
        $response->assertSee('18:20');
        $response->assertSee('12:15');
        $response->assertSee('13:05');
    }

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $response = $this->actingAsAdmin()->post('/attendance/'.$this->record->id, $this->validData([
            'new_clock_in' => '19:00',
        ]));

        $response->assertSessionHasErrors(['new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $response = $this->actingAsAdmin()->post('/attendance/'.$this->record->id, $this->validData([
            'new_break_in' => [0 => '19:00'],
            'new_break_out' => [0 => ''],
        ]));

        $response->assertSessionHasErrors(['new_break_in.0' => '休憩時間が不適切な値です']);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $response = $this->actingAsAdmin()->post('/attendance/'.$this->record->id, $this->validData([
            'new_break_out' => [0 => '19:00'],
        ]));

        $response->assertSessionHasErrors(['new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $response = $this->actingAsAdmin()->post('/attendance/'.$this->record->id, $this->validData([
            'comment' => '',
        ]));

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    public function test_管理者の修正は申請を経ずに勤怠へ直接反映される(): void
    {
        $this->actingAsAdmin()->post('/attendance/'.$this->record->id, $this->validData());

        // FN040: 管理者は直接修正（申請は作られない）
        $this->assertDatabaseHas('attendance_records', [
            'id' => $this->record->id,
            'clock_in' => '09:00:00',
            'comment' => '打刻漏れのため',
        ]);
        $this->assertDatabaseCount('applications', 0);
    }
}
