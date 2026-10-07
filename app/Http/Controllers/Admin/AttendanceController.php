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
     */
    public function staff(Request $request, int $id): View
    {
        $staff = User::where('admin_status', false)->findOrFail($id);

        return view('admin.staff-attendance-list', ['user' => $staff] + $this->monthlyAttendanceData($staff, $request));
    }
}
