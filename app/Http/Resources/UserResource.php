<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 公開API の勤怠に含めるユーザー。メールアドレス等は出さない。
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @param  Request  $request  リクエスト
     * @return array<string, mixed> ユーザーのIDと名前
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
