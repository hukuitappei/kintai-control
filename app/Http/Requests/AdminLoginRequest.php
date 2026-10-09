<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
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
        // FN016にメール形式の文言が無いため、形式チェックは付けない
        return [
            'email' => ['required'],
            'password' => ['required'],
        ];
    }

    /**
     * 管理者として認証する。一般ユーザーは「ログイン情報が登録されていません」で拒否する。
     *
     *
     * @throws ValidationException 認証に失敗した場合、または一般ユーザーの場合
     */
    public function authenticate(): void
    {
        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! Auth::user()->admin_status) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}
