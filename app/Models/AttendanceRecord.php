<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
    ];

    /**
     * この勤怠を記録したユーザー。
     *
     * @return BelongsTo usersテーブルへの多対1のリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠の休憩。
     *
     * @return HasMany breaksテーブルへの1対多のリレーション
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * この勤怠に対する修正申請。
     *
     * @return HasMany applicationsテーブルへの1対多のリレーション
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 休憩時間の合計（"H:i:s"）。Bladeが Carbon::parse() で整形するため時刻文字列で返す。
     *
     * @return string 休憩時間の合計（例: "01:00:00"）
     */
    public function getTotalBreakTimeAttribute(): string
    {
        return self::minutesToTime($this->breakMinutes());
    }

    /**
     * 勤務時間の合計（"H:i:s"）。退勤前はnull。
     *
     * @return string|null 勤務時間の合計（例: "08:00:00"）。退勤前はnull
     */
    public function getTotalTimeAttribute(): ?string
    {
        if (is_null($this->clock_out)) {
            return null;
        }

        $workMinutes = Carbon::parse($this->clock_in)->diffInMinutes(Carbon::parse($this->clock_out));

        return self::minutesToTime($workMinutes - $this->breakMinutes());
    }

    /**
     * 休憩時間の合計（分）。
     *
     * @return int 休憩時間の合計（分）
     */
    private function breakMinutes(): int
    {
        // 休憩中の休憩は含めない
        return $this->breaks
            ->whereNotNull('break_out')
            ->sum(fn ($break) => Carbon::parse($break->break_in)->diffInMinutes(Carbon::parse($break->break_out)));
    }

    /**
     * 分を "H:i:s" にする（例: 90 → "01:30:00"）。
     *
     * @param  int  $minutes  分
     * @return string "H:i:s" 形式の時刻文字列
     */
    private static function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
