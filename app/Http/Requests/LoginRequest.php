<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * ログイン（一般ユーザー）。FN008〜FN009。
 * FortifyServiceProvider で Fortify の LoginRequest をこのクラスに差し替える。
 */
class LoginRequest extends FortifyLoginRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // FN009にメール形式の文言が無いため、形式チェックは付けない
        return [
            'email' => ['required'],
            'password' => ['required'],
        ];
    }
}
