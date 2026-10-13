<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

/**
 * 公開API の勤怠の更新・削除の認可。本人または管理者のみ許可する。FN067、AP06。
 */
class AttendanceRecordPolicy
{
    /**
     * 各メソッドより先に呼ばれる。管理者はすべて許可し、それ以外は null を返して各メソッドの判定に任せる。
     *
     * @param  User  $user  ログインユーザー
     * @param  string  $ability  判定する操作（update・delete）
     * @return bool|null 管理者なら true、それ以外は null
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->admin_status ? true : null;
    }

    /**
     * 勤怠を更新できるか。
     *
     * @param  User  $user  ログインユーザー
     * @param  AttendanceRecord  $attendanceRecord  更新する勤怠
     * @return bool 本人の勤怠なら true
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠を削除できるか。
     *
     * @param  User  $user  ログインユーザー
     * @param  AttendanceRecord  $attendanceRecord  削除する勤怠
     * @return bool 本人の勤怠なら true
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }
}
