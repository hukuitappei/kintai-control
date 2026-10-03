<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsMonthlyAttendance;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use BuildsMonthlyAttendance;

    /**
     * 日次勤怠一覧（管理者）。FN034〜FN036。
     * PG08: /admin/attendance/list?date=2026-09-27（dateが無ければ今日）
     * Blade: admin/admin-attendance-list.blade.php（docs/blade-contract.md 2章）
     */
    public function index(Request $request): View
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        // 「!」の意味は一般ユーザーの勤怠一覧（AttendanceController::index()）と同じ
        $date = $request->filled('date')
            ? Carbon::createFromFormat('!Y-m-d', $request->input('date'))
            : today();

        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 一般ユーザー全員（管理者は打刻しないので除く）
        $users = User::where('admin_status', false)->orderBy('id')->get();

        // その日の全員分の勤怠を、休憩ごと取得する（total_break_time / total_time アクセサがbreaksを使うため）。
        // Bladeが $attendanceRecords->where('user_id', $user->id)->first() で1人分ずつ取り出す。
        $attendanceRecords = AttendanceRecord::with('breaks')
            ->whereDate('date', $date)
            ->get();

        return view('admin.admin-attendance-list', compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords'));
    }

    /**
     * スタッフ別月次勤怠一覧（管理者）。FN043〜FN044、FN046。
     * PG11: /admin/attendance/staff/{id}?date=2026-09（dateが無ければ今月）
     * Blade: admin/staff-attendance-list.blade.php（$user と、一般ユーザーの勤怠一覧と同じ変数）
     * CSV出力（Bladeの /export ボタン）はPhase11。
     */
    public function staff(Request $request, int $id): View
    {
        // 一般ユーザーだけを対象にする（管理者のidや存在しないidは404）
        $staff = User::where('admin_status', false)->findOrFail($id);

        // 配列の + は「左に無いキーだけ右から足す」。ここでは $user と月次の4つの変数をまとめて渡す
        return view('admin.staff-attendance-list', ['user' => $staff] + $this->monthlyAttendanceData($staff, $request));
    }
}
