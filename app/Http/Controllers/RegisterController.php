<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;

class RegisterController extends Controller
{
    /**
     * 会員登録（一般ユーザー）。FN001〜FN003。
     * Fortify の RegisteredUserController は引数が Request のため FormRequest で検証できない。
     * POST /register をこのメソッドで受け、RegisterRequest で検証してから Fortify と同じ流れで登録・ログインする。
     *
     * @param  RegisterRequest  $request  会員登録フォームの入力
     * @param  CreatesNewUsers  $creator  ユーザーを作成する Fortify のアクション（CreateNewUser）
     * @param  StatefulGuard  $guard  ログインに使うガード
     * @return RegisterResponse 登録後の遷移（メール未認証のため誘導画面へ）
     */
    public function store(RegisterRequest $request, CreatesNewUsers $creator, StatefulGuard $guard): RegisterResponse
    {
        $user = $creator->create($request->validated());

        // 認証メールは Registered イベントで送られる（FN011）
        event(new Registered($user));

        $guard->login($user);
        $request->session()->regenerate();

        return app(RegisterResponse::class);
    }
}
