<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * 会員登録したユーザーを作成する。入力は RegisterController で RegisterRequest により検証済み。
     *
     * @param  array<string, string>  $input  検証済みの入力（name・email・password）
     * @return User 作成したユーザー
     */
    public function create(array $input): User
    {
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
