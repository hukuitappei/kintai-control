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
        // ログインのemailには「メール形式」チェックを付けない
        // （FN016に該当文言がなく、FN009と同じ理由。docs/unspecified-decisions.md）。
        return [
            'email' => ['required'],
            'password' => ['required'],
        ];
    }

    /**
     * 管理者としての認証を試みる。認証できてもadmin_statusがfalseなら拒否する
     * （docs/blade-contract.md 6章「クロスログイン」の決定に対応）。
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
