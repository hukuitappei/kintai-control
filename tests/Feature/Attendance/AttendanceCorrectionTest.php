<?php

namespace Tests\Feature\Attendance;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID11: 勤怠詳細情報修正機能（一般ユーザー）
 */
class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AttendanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));

        $this->user = User::factory()->create(['name' => '申請 花子']);

        $this->record = $this->createRecord('2026-10-07');
    }

    private function createRecord(string $date): AttendanceRecord
    {
        return AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
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
            'new_clock_in' => '09:30',
            'new_clock_out' => '18:00',
            'new_break_in' => [0 => '12:00'],
            'new_break_out' => [0 => '13:00'],
            'comment' => '電車遅延のため',
        ], $overrides);
    }

    /**
     * 勤怠詳細の「修正」ボタンを押す。
     */
    private function submitCorrection(AttendanceRecord $record, array $data)
    {
        return $this->actingAs($this->user)->post('/attendance/'.$record->id, $data);
    }

    /**
     * 管理者ログイン画面（/admin/login）から入った管理者としてログインする（C6）。
     */
    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create();

        return $this->actingAs($admin)->withSession(['admin_login' => true]);
    }

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $response = $this->submitCorrection($this->record, $this->validData([
            'new_clock_in' => '19:00',
        ]));

        // テストケースの文言は「出勤時間が不適切な値です」だが、FN029 の文言に合わせて実装している（requirements-review C3）
        $response->assertSessionHasErrors(['new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $response = $this->submitCorrection($this->record, $this->validData([
            'new_break_in' => [0 => '19:00'],
            'new_break_out' => [0 => ''],
        ]));

        // 配列の入力欄のエラーは「項目名.番号」のキーに付く
        $response->assertSessionHasErrors(['new_break_in.0' => '休憩時間が不適切な値です']);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $response = $this->submitCorrection($this->record, $this->validData([
            'new_break_out' => [0 => '19:00'],
        ]));

        $response->assertSessionHasErrors(['new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $response = $this->submitCorrection($this->record, $this->validData([
            'comment' => '',
        ]));

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    public function test_修正申請処理が実行される(): void
    {
        $this->submitCorrection($this->record, $this->validData());

        $this->assertDatabaseHas('applications', [
            'attendance_record_id' => $this->record->id,
            'comment' => '電車遅延のため',
            'approval_status' => '承認待ち',
        ]);

        $application = Application::first();

        // 管理者の申請一覧に表示される
        $this->actingAsAdmin()->get('/stamp_correction_request/list')
            ->assertSee('申請 花子')
            ->assertSee('電車遅延のため');

        // 管理者の承認画面に表示される
        $this->get('/stamp_correction_request/approve/'.$application->id)
            ->assertSee('申請 花子')
            ->assertSee('09:30');
    }

    public function test_承認待ちにログインユーザーが行った申請が全て表示されている(): void
    {
        $otherRecord = $this->createRecord('2026-10-08');

        $this->submitCorrection($this->record, $this->validData());
        $this->submitCorrection($otherRecord, $this->validData());

        $response = $this->get('/stamp_correction_request/list');

        // 1つ目のタブ（id="content1"）が承認待ち、2つ目（id="content2"）が承認済み。
        // 対象日（Y/m/d）がどちらのタブの中にあるかを、HTMLの並び順で確かめる
        $response->assertSeeInOrder(['id="content1"', '2026/10/07', 'id="content2"'], false);
        $response->assertSeeInOrder(['id="content1"', '2026/10/08', 'id="content2"'], false);
    }

    public function test_承認済みに管理者が承認した修正申請が全て表示されている(): void
    {
        $this->submitCorrection($this->record, $this->validData());
        $application = Application::first();

        // 管理者が承認する
        $this->actingAsAdmin()->post('/stamp_correction_request/approve/'.$application->id);

        // 一般ユーザーとして申請一覧を開く（管理者のセッションを引き継がないよう、印を消してから）
        $response = $this->actingAs($this->user)->withSession(['admin_login' => false])
            ->get('/stamp_correction_request/list');

        $response->assertSeeInOrder(['id="content2"', '承認済み', '2026/10/07'], false);
    }

    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        $this->submitCorrection($this->record, $this->validData());
        $application = Application::first();

        // 申請一覧の「詳細」リンク
        $this->get('/stamp_correction_request/list')
            ->assertSee('/application/'.$application->id);

        $response = $this->get('/application/'.$application->id);

        // 申請の対象になった勤怠の詳細画面へ遷移する
        $response->assertRedirect('/attendance/'.$this->record->id);
    }
}
