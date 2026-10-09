<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalBreak extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'application_id',
        'break_in',
        'break_out',
    ];

    /**
     * この申請休憩が属する修正申請。
     *
     * @return BelongsTo applicationsテーブルへの多対1のリレーション
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
