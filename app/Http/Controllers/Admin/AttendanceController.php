<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsMonthlyAttendance;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    use BuildsMonthlyAttendance;

    /**
     * 日次勤怠一覧（管理者）。FN034〜FN036。
     *
     * @param  Request  $request  表示する日付（Y-m-d）を持つリクエスト
     * @return View 勤怠一覧画面（管理者）のビュー
     */
    public function index(Request $request): View
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $date = $request->filled('date')
            ? Carbon::createFromFormat('!Y-m-d', $request->input('date'))
            : today();

        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 管理者を含む全ユーザー（FN034。コーチ回答で確認済み）
        $users = User::orderBy('id')->get();

        // Bladeがユーザーごとに where('user_id', ...) で取り出す
        $attendanceRecords = AttendanceRecord::with('breaks')
            ->whereDate('date', $date)
            ->get();

        return view('admin.admin-attendance-list', compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords'));
    }

    /**
     * スタッフ別月次勤怠一覧（管理者）。FN043〜FN044、FN046。
     *
     * @param  Request  $request  表示する月（Y-m）を持つリクエスト
     * @param  int  $id  スタッフ（一般ユーザー）のID
     * @return View スタッフ別勤怠一覧画面（管理者）のビュー
     */
    public function staff(Request $request, int $id): View
    {
        $staff = User::where('admin_status', false)->findOrFail($id);

        return view('admin.staff-attendance-list', ['user' => $staff] + $this->monthlyAttendanceData($staff, $request));
    }

    /**
     * スタッフ別月次勤怠一覧のCSV出力（管理者）。FN045。
     * 画面と同じ月・同じ列（日付/出勤/退勤/休憩/合計）を出力する。
     *
     * @param  Request  $request  スタッフのID（user_id）と対象の年月（year_month）を持つリクエスト
     * @return StreamedResponse CSVファイルのダウンロード
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $staff = User::where('admin_status', false)->findOrFail($request->input('user_id'));

        // 月次一覧の表示データを使い回す（BuildsMonthlyAttendance は date パラメータで月を受け取る）
        $request->merge(['date' => $request->input('year_month')]);
        $records = $this->monthlyAttendanceData($staff, $request)['formattedAttendanceRecords'];

        // 日本語のファイル名はブラウザによって文字化けするため英数字にする
        $fileName = 'attendance_'.$staff->id.'_'.$request->input('year_month').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // Excel で開いたときに文字化けしないよう、UTF-8 の BOM を先頭に付ける
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            collect($records)->each(fn ($record) => fputcsv($handle, [
                $record['date'],
                $record['clock_in'],
                $record['clock_out'],
                // 画面（Blade）と同じく G:i で出力する。勤怠の無い日は空欄
                $record['total_break_time'] ? Carbon::parse($record['total_break_time'])->format('G:i') : '',
                $record['total_time'] ? Carbon::parse($record['total_time'])->format('G:i') : '',
            ]));

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
