<?php

namespace App\Http\Resources;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 公開API の勤怠。一覧と詳細で共用し、関連は eager load したときだけ含める。FN064。
 *
 * @mixin AttendanceRecord
 */
class AttendanceRecordResource extends JsonResource
{
    /**
     * @param  Request  $request  リクエスト
     * @return array<string, mixed> レスポンスの data に入る勤怠
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'total_time' => $this->total_time,
            'total_break_time' => $this->total_break_time,
            'comment' => $this->comment,
            'breaks' => AttendanceBreakResource::collection($this->whenLoaded('breaks')),
            'applications' => ApplicationResource::collection($this->whenLoaded('applications')),
        ];
    }
}
