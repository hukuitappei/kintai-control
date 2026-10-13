<?php

namespace App\Http\Resources;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 公開API の勤怠詳細に含める修正申請。
 *
 * @mixin Application
 */
class ApplicationResource extends JsonResource
{
    /**
     * @param  Request  $request  リクエスト
     * @return array<string, mixed> 修正申請の内容と承認状況
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'new_date' => $this->new_date?->toDateString(),
            'new_clock_in' => $this->new_clock_in,
            'new_clock_out' => $this->new_clock_out,
            'comment' => $this->comment,
            'approval_status' => $this->approval_status,
            'application_date' => $this->application_date,
        ];
    }
}
