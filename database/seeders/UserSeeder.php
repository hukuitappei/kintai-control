<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 開発プロセスシートのダミーデータ要件・READMEのログイン情報と一致させる。
     * user1・user2: 一般ユーザー（password / メール認証済み。Factoryのデフォルトのまま）。
     * user3: 管理者（admin_status = true）。
     */
    public function run(): void
    {
        User::factory()->create(['name' => 'user1', 'email' => 'user1@example.com']);
        User::factory()->create(['name' => 'user2', 'email' => 'user2@example.com']);
        User::factory()->admin()->create(['name' => 'user3', 'email' => 'user3@example.com']);
    }
}
