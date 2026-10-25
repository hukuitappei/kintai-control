<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 一般・管理者のログインのバリデーションを自前のFormRequestで行う（FN008・FN015）
        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

        // 認証メール再送後、誘導画面の session('message') に表示する文言を渡す（FN012）
        $this->app->instance(EmailVerificationNotificationSentResponse::class, new class implements EmailVerificationNotificationSentResponse
        {
            public function toResponse($request): RedirectResponse
            {
                return back()->with('message', '認証メールを再送しました。');
            }
        });

        // ログイン後の遷移先。管理者ログイン画面から入った場合は印を付けて管理者の勤怠一覧へ
        $this->app->instance(LoginResponse::class, new class implements LoginResponse
        {
            public function toResponse($request): RedirectResponse
            {
                if ($request->is('admin/*')) {
                    // 共用URLを管理者用画面にするための印。ログアウト時の invalidate() で消える
                    $request->session()->put('admin_login', true);

                    return redirect('/admin/attendance/list');
                }

                return redirect()->intended(Fortify::redirects('login'));
            }
        });

        // ログアウト後の遷移先。それぞれのログイン画面へ戻す
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request): RedirectResponse
            {
                return $request->is('admin/*')
                    ? redirect('/admin/login')
                    : redirect('/login');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(fn () => view('user.register'));
        Fortify::loginView(fn (Request $request): View => $request->is('admin/*') ? view('admin.admin-login') : view('user.user-login'));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        // 管理者ログイン画面からは管理者だけを通し、一般ユーザーは「ログイン情報が登録されていません」にする（FN016）
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->input('email'))->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            if ($request->is('admin/*') && ! $user->admin_status) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
