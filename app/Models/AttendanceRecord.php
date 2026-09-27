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
     * このattendance_recordsは、どのusersに属するか（外部キー: user_id）。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このattendance_recordsが持つbreaks（1対多。外部キー: breaks.attendance_record_id）。
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * このattendance_recordsに対する修正申請applications（1対多。外部キー: applications.attendance_record_id）。
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 休憩時間の合計（"H:i:s"形式の文字列）。
     * Blade（勤怠一覧）が Carbon::parse() に渡して format('G:i') するため、時刻文字列で返す。
     * 管理者の日次一覧（Phase7-1）も $attendance->total_break_time として読む。
     * docs/table-design.md: 合計時間は列に持たずアクセサで計算する方針。
     */
    public function getTotalBreakTimeAttribute(): string
    {
        return self::minutesToTime($this->breakMinutes());
    }

    /**
     * 勤務時間の合計（出勤〜退勤から休憩合計を引いたもの。"H:i:s"形式の文字列）。
     * まだ退勤していない日は計算できないのでnullを返す（一覧では空欄になる）。
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

        // $this->breaks（プロパティ）は、with('breaks')で読み込み済みならSQLを追加で発行しない
        foreach ($this->breaks as $break) {
            // 休憩中（まだ戻っていない）休憩は合計に含めない
            if (is_null($break->break_out)) {
                continue;
            }
            $minutes += Carbon::parse($break->break_in)->diffInMinutes(Carbon::parse($break->break_out));
        }

        return $minutes;
    }

    /**
     * 分（整数）を "H:i:s" 形式の文字列にする。例: 90 → "01:30:00"
     */
    private static function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
