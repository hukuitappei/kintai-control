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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 休憩時間の合計（"H:i:s"）。Bladeが Carbon::parse() で整形するため時刻文字列で返す。
     */
    public function getTotalBreakTimeAttribute(): string
    {
        return self::minutesToTime($this->breakMinutes());
    }

    /**
     * 勤務時間の合計（"H:i:s"）。退勤前はnull。
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
     */
    private function breakMinutes(): int
    {
        $minutes = 0;

        foreach ($this->breaks as $break) {
            // 休憩中の休憩は含めない
            if (is_null($break->break_out)) {
                continue;
            }
            $minutes += Carbon::parse($break->break_in)->diffInMinutes(Carbon::parse($break->break_out));
        }

        return $minutes;
    }

    /**
     * 分を "H:i:s" にする（例: 90 → "01:30:00"）。
     */
    private static function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
