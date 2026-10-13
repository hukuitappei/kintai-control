<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * テストケース一覧 ID16: メール認証機能
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録後に認証メールが送信される(): void
    {
        // 実際には送らず、送ろうとした通知を記録する
        Notification::fake();

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_メール未認証のユーザーは勤怠登録画面ではなくメール認証誘導画面へ遷移する(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertRedirect('/email/verify');
    }

    public function test_メール認証誘導画面の認証はこちらからボタンがメール認証サイトへのリンクになっている(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertOk();
        // メール認証サイト（Mailpit）は外部の画面のため、ボタンのリンク先を確認する
        $response->assertSee('<a class="verify__link" href="http://localhost:8025"', false);
        $response->assertSee('認証はこちらから');
    }

    public function test_メール認証を完了すると勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->unverified()->create();

        // 認証メールに載るものと同じ、署名付きの認証URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/attendance?verified=1');

        $this->actingAs($user->fresh())->get('/attendance')->assertViewIs('user.attendance-register');
    }
}
