<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    use HasFactory;

    // 'Break' はPHPの予約語のため、モデル名はBreakTimeにしている（docs/table-design.md 3章）。
    // テーブル名がモデル名から自動推測できないので、明示する。
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
     * このbreaksは、どのattendance_recordsに属するか（外部キー: attendance_record_id）。
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
