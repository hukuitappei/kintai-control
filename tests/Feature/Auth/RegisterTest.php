<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID1: 認証機能（一般ユーザー）
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全項目が正しく入力された会員登録データ。各テストで1項目だけ上書きして使う。
     *
     * @return array<string, string>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_名前が未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validData(['name' => '']));

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validData(['email' => '']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワードが8文字未満の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validData([
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_パスワードが一致しない場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validData(['password_confirmation' => 'different123']));

        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);
    }

    public function test_パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validData(['password' => '']));

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_フォームに内容が入力されていた場合データが正常に保存される(): void
    {
        $response = $this->post('/register', $this->validData());

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
    }
}
