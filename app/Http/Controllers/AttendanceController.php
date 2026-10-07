<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsMonthlyAttendance;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use BuildsMonthlyAttendance;

    /**
     * 勤怠一覧（一般ユーザー）。FN023〜FN025。
     */
    public function index(Request $request): View
    {
        return view('user.user-attendance-list', $this->monthlyAttendanceData(Auth::user(), $request));
    }

    /**
     * 勤怠詳細。一般ユーザー（FN026）と管理者（FN037）で同じURLを使い、どちらのログイン画面から入ったかで分岐する。
     */
    public function show(int $id): View
    {
        $user = Auth::user();

        $attendanceRecord = AttendanceRecord::with('breaks')->findOrFail($id);

        if ($this->loggedInAsAdmin()) {
            // 名前欄には勤怠の持ち主を表示する
            $staff = $attendanceRecord->user;

            return view('admin.admin-detail', [
                'user' => $staff,
                'attendanceRecord' => $this->detailData($attendanceRecord),
            ]);
        }

        abort_if($attendanceRecord->user_id !== $user->id, 403);

        // 承認待ちの申請があれば、Bladeは閲覧のみの表示になる
        $application = $attendanceRecord->applications()
            ->where('approval_status', '承認待ち')
            ->with('proposalBreaks')
            ->latest()
            ->first();

        $data = $this->detailData($attendanceRecord, $application);
        $data['application'] = $application;

        return view('user.user-detail', compact('user', 'data'));
    }

    /**
     * 勤怠詳細の表示データ。承認待ちの申請があるときは申請内容を表示する。
     */
    private function detailData(AttendanceRecord $attendanceRecord, ?Application $application = null): array
    {
        if ($application) {
            $clockIn = $application->new_clock_in;
            $clockOut = $application->new_clock_out;
            $breaks = $application->proposalBreaks;
            $comment = $application->comment;
        } else {
            $clockIn = $attendanceRecord->clock_in;
            $clockOut = $attendanceRecord->clock_out;
            $breaks = $attendanceRecord->breaks;
            $comment = $attendanceRecord->comment;
        }

        $date = Carbon::parse($attendanceRecord->date);

        return [
            'id' => $attendanceRecord->id,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'clock_in' => Carbon::parse($clockIn)->format('H:i'),
            'clock_out' => $clockOut ? Carbon::parse($clockOut)->format('H:i') : '',
            'breaks' => $breaks->map(fn ($break) => [
                'break_in' => Carbon::parse($break->break_in)->format('H:i'),
                'break_out' => $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : '',
            ])->all(),
            'comment' => $comment,
        ];
    }

    /**
     * 修正申請（一般ユーザー。FN027〜FN030）／直接修正（管理者。FN038〜FN040）。
     */
    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $user = Auth::user();

        $attendanceRecord = AttendanceRecord::findOrFail($id);

        if ($this->loggedInAsAdmin()) {
            DB::transaction(function () use ($request, $attendanceRecord) {
                $attendanceRecord->update([
                    'clock_in' => $request->input('new_clock_in'),
                    'clock_out' => $request->input('new_clock_out'),
                    'comment' => $request->input('comment'),
                ]);

                // 休憩の追加・変更・削除をまとめて扱うため、入力どおりに作り直す
                $attendanceRecord->breaks()->delete();

                $breakOuts = $request->input('new_break_out', []);
                foreach ($request->input('new_break_in', []) as $index => $breakIn) {
                    if (is_null($breakIn)) {
                        continue;
                    }

                    $attendanceRecord->breaks()->create([
                        'break_in' => $breakIn,
                        'break_out' => $breakOuts[$index] ?? null,
                    ]);
                }
            });

            return redirect('/attendance/'.$attendanceRecord->id);
        }

        abort_if($attendanceRecord->user_id !== $user->id, 403);

        // 承認待ちの申請がある間は新しい申請を受け付けない（FN027）
        $hasPendingApplication = $attendanceRecord->applications()
            ->where('approval_status', '承認待ち')
            ->exists();
        if ($hasPendingApplication) {
            return redirect('/attendance/'.$attendanceRecord->id);
        }

        DB::transaction(function () use ($request, $user, $attendanceRecord) {
            $application = $attendanceRecord->applications()->create([
                'user_id' => $user->id,
                // 日付は修正対象外のため勤怠の日付を使う
                'new_date' => $attendanceRecord->date,
                'new_clock_in' => $request->input('new_clock_in'),
                'new_clock_out' => $request->input('new_clock_out'),
                'comment' => $request->input('comment'),
                'application_date' => now()->toDateString(),
            ]);

            $breakOuts = $request->input('new_break_out', []);
            foreach ($request->input('new_break_in', []) as $index => $breakIn) {
                if (is_null($breakIn)) {
                    continue;
                }

                $application->proposalBreaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOuts[$index] ?? null,
                ]);
            }
        });

        return redirect('/attendance/'.$attendanceRecord->id);
    }

    /**
     * 打刻画面。FN018〜FN019。
     */
    public function create(): View
    {
        $user = Auth::user();
        $formattedDate = now()->isoFormat('YYYY年M月D日(ddd)');
        $formattedTime = now()->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 打刻処理。押されたボタン（name="action"）の値で振り分ける。FN020〜FN022。
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        match ($request->input('action')) {
            'clock_in' => $this->clockIn($user),
            'break_in' => $this->breakIn($user),
            'break_out' => $this->breakOut($user),
            'clock_out' => $this->clockOut($user),
            default => null,
        };

        return redirect('/attendance');
    }

    /**
     * 今日の勤怠（無ければnull）。
     */
    private function todayRecord(User $user): ?AttendanceRecord
    {
        return $user->attendanceRecords()->whereDate('date', now())->first();
    }

    /**
     * 出勤。勤務外のときのみ（1日1回）。FN020。
     */
    private function clockIn(User $user): void
    {
        if ($user->attendance_status !== '勤務外') {
            return;
        }

        $user->attendanceRecords()->create([
            'date' => today()->toDateString(),
            'clock_in' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 休憩入。出勤中のときのみ（何回でも）。FN021。
     */
    private function breakIn(User $user): void
    {
        if ($user->attendance_status !== '出勤中') {
            return;
        }

        $this->todayRecord($user)->breaks()->create([
            'break_in' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 休憩戻。休憩中のときのみ（何回でも）。FN021。
     */
    private function breakOut(User $user): void
    {
        if ($user->attendance_status !== '休憩中') {
            return;
        }

        $this->todayRecord($user)->breaks()->whereNull('break_out')->first()->update([
            'break_out' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 退勤。出勤中のときのみ（1日1回）。FN022。
     */
    private function clockOut(User $user): void
    {
        if ($user->attendance_status !== '出勤中') {
            return;
        }

        $this->todayRecord($user)->update([
            'clock_out' => now()->format('H:i:s'),
        ]);
    }
}
