<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID3: ログイン認証機能（管理者）
 */
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 手順1「ユーザーを登録する」（管理者）
        User::factory()->admin()->create(['email' => 'admin@example.com']);
    }

    public function test_メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_登録内容と一致しない場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_一般ユーザーは管理者としてログインできない(): void
    {
        User::factory()->create(['email' => 'staff@example.com']);

        $response = $this->post('/admin/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_管理者は管理者ログインで日次勤怠一覧へ遷移する(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/attendance/list');
        $this->assertAuthenticated();
        // 共用URLを管理者用画面にするための印（C6）
        $response->assertSessionHas('admin_login', true);
    }
}
