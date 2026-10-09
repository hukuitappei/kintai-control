<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    use HasFactory;

    // Break はPHPの予約語のため、モデル名をBreakTimeにしてテーブル名を指定する
    protected $table = 'breaks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];

    /**
     * この休憩が属する勤怠。
     *
     * @return BelongsTo attendance_recordsテーブルへの多対1のリレーション
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
