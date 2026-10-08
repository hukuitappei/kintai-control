<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID15: 勤怠情報修正機能（管理者）
 */
class ApprovalTest extends TestCase
{
    use ActsAsAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 10, 15, 12, 0));
    }

    /**
     * スタッフの勤怠（9:00〜18:00）と、それに対する修正申請（出勤を9:30に）を作る。
     */
    private function createCorrectionApplication(string $name, string $date, string $status = '承認待ち'): Application
    {
        $staff = User::factory()->create(['name' => $name]);

        $record = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        return Application::factory()->create([
            'user_id' => $staff->id,
            'attendance_record_id' => $record->id,
            'new_date' => $date,
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:00:00',
            'comment' => $name.'の申請',
            'approval_status' => $status,
        ]);
    }

    public function test_承認待ちの修正申請が全て表示されている(): void
    {
        $this->createCorrectionApplication('スタッフ 一郎', '2026-10-01');
        $this->createCorrectionApplication('スタッフ 二郎', '2026-10-02');

        $response = $this->actingAsAdmin()->get('/stamp_correction_request/list');

        // 1つ目のタブ（id="content1"）が承認待ち、2つ目（id="content2"）が承認済み
        $response->assertSeeInOrder(['id="content1"', 'スタッフ 一郎', 'id="content2"'], false);
        $response->assertSeeInOrder(['id="content1"', 'スタッフ 二郎', 'id="content2"'], false);
    }

    public function test_承認済みの修正申請が全て表示されている(): void
    {
        $this->createCorrectionApplication('スタッフ 一郎', '2026-10-01', '承認済み');
        $this->createCorrectionApplication('スタッフ 二郎', '2026-10-02', '承認済み');

        $response = $this->actingAsAdmin()->get('/stamp_correction_request/list');

        $response->assertSeeInOrder(['id="content2"', 'スタッフ 一郎'], false);
        $response->assertSeeInOrder(['id="content2"', 'スタッフ 二郎'], false);
    }

    public function test_修正申請の詳細内容が正しく表示されている(): void
    {
        $application = $this->createCorrectionApplication('スタッフ 一郎', '2026-10-01');

        $response = $this->actingAsAdmin()->get('/stamp_correction_request/approve/'.$application->id);

        $response->assertSee('スタッフ 一郎');
        $response->assertSee('10月1日');
        $response->assertSee('09:30');
        $response->assertSee('スタッフ 一郎の申請');
    }

    public function test_修正申請の承認処理が正しく行われる(): void
    {
        $application = $this->createCorrectionApplication('スタッフ 一郎', '2026-10-01');

        $this->actingAsAdmin()->post('/stamp_correction_request/approve/'.$application->id);

        // 申請が承認済みになる
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'approval_status' => '承認済み',
        ]);
        // 勤怠が申請の内容（出勤 9:30）に更新される
        $this->assertDatabaseHas('attendance_records', [
            'id' => $application->attendance_record_id,
            'clock_in' => '09:30:00',
        ]);
    }
}
