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

    /**
     * このユーザーの勤怠。
     *
     * @return HasMany attendance_recordsテーブルへの1対多のリレーション
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * このユーザーが出した修正申請。
     *
     * @return HasMany applicationsテーブルへの1対多のリレーション
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 今日の勤怠ステータス（勤務外/出勤中/休憩中/退勤済）。FN019。
     *
     * @return string 勤務外・出勤中・休憩中・退勤済のいずれか
     */
    public function getAttendanceStatusAttribute(): string
    {
        $today = $this->attendanceRecords()->whereDate('date', now())->first();

        if (is_null($today)) {
            return '勤務外';
        }

        if (! is_null($today->clock_out)) {
            return '退勤済';
        }

        $hasOpenBreak = $today->breaks()->whereNull('break_out')->exists();

        if ($hasOpenBreak) {
            return '休憩中';
        }

        return '出勤中';
    }
}
