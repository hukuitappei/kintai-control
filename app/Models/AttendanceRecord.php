<?php

namespace App\Models;

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
}
