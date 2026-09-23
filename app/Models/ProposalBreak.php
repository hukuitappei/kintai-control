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
     * このproposal_breaksは、どのapplicationsに属するか（外部キー: application_id）。
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
