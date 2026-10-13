<?php

namespace App\Http\Resources;

use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 公開API の勤怠に含める休憩。
 *
 * @mixin BreakTime
 */
class AttendanceBreakResource extends JsonResource
{
    /**
     * @param  Request  $request  リクエスト
     * @return array<string, mixed> 休憩のIDと開始・終了時刻
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'break_in' => $this->break_in,
            'break_out' => $this->break_out,
        ];
    }
}
