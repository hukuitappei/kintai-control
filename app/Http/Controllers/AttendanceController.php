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
     * PG04: /attendance/list?date=2026-09（dateが無ければ今月）
     * Blade: user/user-attendance-list.blade.php（docs/blade-contract.md 2章）
     */
    public function index(Request $request): View
    {
        // 組み立て処理は管理者のスタッフ別月次勤怠一覧と共用（Concerns\BuildsMonthlyAttendance）
        return view('user.user-attendance-list', $this->monthlyAttendanceData(Auth::user(), $request));
    }

    /**
     * 勤怠詳細。一般ユーザー（FN026）と管理者（FN037）で共用。
     * URLは /attendance/{id}（Blade原文どおり。admin_statusで分岐: docs/blade-contract.md 6章）
     * Blade: 一般 user/user-detail.blade.php（$user, $data）／管理者 admin/admin-detail.blade.php（$user, $attendanceRecord）
     */
    public function show(int $id): View
    {
        $user = Auth::user();

        // 見つからなければ404
        $attendanceRecord = AttendanceRecord::with('breaks')->findOrFail($id);

        // 管理者（FN037）: 全員の勤怠を見られる。表示するのは勤怠そのものの値で、常に修正フォーム。
        // Blade: admin/admin-detail.blade.php（$user, $attendanceRecord（配列））
        if ($user->admin_status) {
            // 「名前」欄に出すのはログイン中の管理者ではなく、勤怠の持ち主（スタッフ）
            $staff = $attendanceRecord->user;

            return view('admin.admin-detail', [
                'user' => $staff,
                'attendanceRecord' => $this->detailData($attendanceRecord),
            ]);
        }

        // 他人の勤怠は見られないようにする（docs/blade-contract.md 6章「他人の勤怠へのアクセス防止」）
        abort_if($attendanceRecord->user_id !== $user->id, 403);

        // 承認待ちの申請（無ければnull）。あればBladeは閲覧のみの表示に切り替わる（FN030）
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
     * 勤怠詳細の表示データ（一般・管理者で共通の部分）。
     * 承認待ちの申請が渡されたときは、申請した内容（修正後の値）を表示する。
     * 無いときは勤怠そのものの値を表示する。
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

        // Figma（勤怠詳細画面）の表示形式: 「2023年」「6月1日」「09:00」
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
     * 修正申請（一般ユーザー。FN028〜FN030）／直接修正（管理者。FN038〜FN040）。
     * バリデーションはAttendanceCorrectionRequestが行い、失敗すると自動で詳細画面に戻る
     * （エラーはBladeの@errorで表示される）。
     */
    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $user = Auth::user();

        $attendanceRecord = AttendanceRecord::findOrFail($id);

        // 管理者（FN038〜FN040）: 申請を介さず、勤怠と休憩を直接書き換える。
        // バリデーションは一般ユーザーと同じAttendanceCorrectionRequest（FN039）。
        if ($user->admin_status) {
            DB::transaction(function () use ($request, $attendanceRecord) {
                // キーはDBの列名、値はフォームの入力名（new_○○）。名前が違うので取り違えに注意
                $attendanceRecord->update([
                    'clock_in' => $request->input('new_clock_in'),
                    'clock_out' => $request->input('new_clock_out'),
                    'comment' => $request->input('comment'),
                ]);

                // 休憩は、既存の行と入力の行を1つずつ対応づけるより、
                // 「全部消して、入力どおりに作り直す」ほうが追加・変更・削除をまとめて扱えて単純。
                // どちらもトランザクションの中なので、作り直しに失敗すれば削除も取り消される。
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

            // 直接修正なので、一般ユーザー側の勤怠一覧・詳細にもそのまま反映される（FN040）
            return redirect('/attendance/'.$attendanceRecord->id);
        }

        abort_if($attendanceRecord->user_id !== $user->id, 403);

        // 承認待ちの申請がある間は、新しい申請を受け付けない（FN030）。
        // Bladeは承認待ちのとき「修正」ボタンを出さないが、POSTを直接送られる場合に備えてここでも止める。
        $hasPendingApplication = $attendanceRecord->applications()
            ->where('approval_status', '承認待ち')
            ->exists();
        if ($hasPendingApplication) {
            return redirect('/attendance/'.$attendanceRecord->id);
        }

        // 申請本体と申請中の休憩は「両方保存できたときだけ」確定させたいので、トランザクションで囲む。
        // 途中で例外が起きると、それまでのINSERTもまとめて取り消される。
        DB::transaction(function () use ($request, $user, $attendanceRecord) {
            // リレーション経由でcreate()すると、attendance_record_idは自動で入る
            $application = $attendanceRecord->applications()->create([
                'user_id' => $user->id,
                // 日付は修正できない（Bladeのnew_dateはreadonlyで「9月1日」形式）ので、勤怠の日付を使う
                'new_date' => $attendanceRecord->date,
                'new_clock_in' => $request->input('new_clock_in'),
                'new_clock_out' => $request->input('new_clock_out'),
                'comment' => $request->input('comment'),
                // approval_statusは省略するとマイグレーションの既定値（承認待ち）になる
                'application_date' => now()->toDateString(),
            ]);

            // 休憩は new_break_in[0], new_break_in[1], ... の配列で届く。
            // 追加用の空欄行（開始が空）は保存しない。
            $breakOuts = $request->input('new_break_out', []);
            foreach ($request->input('new_break_in', []) as $index => $breakIn) {
                if (is_null($breakIn)) {
                    continue;
                }

                // $application->proposalBreaks（プロパティ）は「読み込んだ結果」。
                // $application->proposalBreaks()（メソッド）は「リレーションそのもの」で、create()などの操作ができる。
                $application->proposalBreaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOuts[$index] ?? null,
                ]);
            }
        });

        // 詳細画面に戻ると、承認待ちの申請があるので閲覧のみの表示になる
        return redirect('/attendance/'.$attendanceRecord->id);
    }

    /**
     * 勤怠登録画面（打刻画面）を表示する。FN018〜FN019。
     * PG03: /attendance
     */
    public function create(): View
    {
        $user = Auth::user();
        $formattedDate = now()->isoFormat('YYYY年M月D日(ddd)');
        $formattedTime = now()->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 打刻処理。resources/views/user/attendance-register.blade.php の
     * ボタン（name="action"）の値ごとに処理を振り分ける。
     * 4つのvalue="..."をBladeを見て埋めること。
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
     * 今日のattendance_recordsを1件取得する（無ければnull）。
     * User::getAttendanceStatusAttribute()と同じ絞り込み方。
     */
    private function todayRecord(User $user): ?AttendanceRecord
    {
        return $user->attendanceRecords()->whereDate('date', now())->first();
    }

    /**
     * 出勤処理。FN020: 「勤務外」のときのみ、1日1回だけ出勤できる。
     * 属性値はUser::getAttendanceStatusAttribute()が返す文字列と揃えること。
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
     * 休憩入処理。FN021: 「出勤中」のときのみ、何回でも押下できる。
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
     * 休憩戻処理。FN021: 「休憩中」のときのみ、何回でも押下できる。
     * まだbreak_outが入っていない（休憩中の）breaksレコードを更新する。
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
     * 退勤処理。FN022: 「出勤中」のときのみ、1日1回だけ押下できる。
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
