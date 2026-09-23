<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }


    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 今日の勤怠状況（勤務外/出勤中/休憩中/退勤済）。
     * Blade: user/attendance-register.blade.php が $user->attendance_status を文字列比較で使う。
     * docs/table-design.md: 列に持たず、今日のattendance_recordsとbreaksから計算する方針。
     */
    public function getAttendanceStatusAttribute(): string
    {
        $today = $this->attendanceRecords()->whereDate('date', now())->first();

        // 今日の勤怠レコードがまだ無い場合 → firstはレコードが無いとnullを返す
        if (is_null($today)) {
            return '勤務外';
        }

        // 退勤時刻（clock_out）が入っている場合 → NULLでなければ退勤済
        if (!is_null($today->clock_out)) {
            return '退勤済';
        }

        // 休憩が始まっていて、まだ終わっていない（break_outがnull）ものがあるか
        $hasOpenBreak = $today->breaks()->whereNull('break_out')->exists();

        if ($hasOpenBreak) {
            return '休憩中';
        }

        return '出勤中';
    }
}
