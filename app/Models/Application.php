<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'new_date',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'approval_status',
        'application_date',
    ];

    /**
     * 承認画面のBladeが new_date->format() を呼ぶため、日付としてキャストする。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'new_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 提供Bladeが $application->AttendanceRecord と呼ぶため、先頭大文字のメソッド名にしている。
     */
    public function AttendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function proposalBreaks(): HasMany
    {
        return $this->hasMany(ProposalBreak::class);
    }
}
