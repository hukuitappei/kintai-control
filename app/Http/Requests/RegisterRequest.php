<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 会員登録（一般ユーザー）。FN002〜FN003。
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    /**
     * FN003-4: 確認用パスワードが空の場合も「パスワードと一致しません」にする。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password_confirmation.required' => 'パスワードと一致しません',
        ];
    }
}
