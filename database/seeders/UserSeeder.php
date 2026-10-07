<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * user1・user2（一般）、user3（管理者）。パスワードはいずれも password、メール認証済み。
     */
    public function run(): void
    {
        User::factory()->create(['name' => 'user1', 'email' => 'user1@example.com']);
        User::factory()->create(['name' => 'user2', 'email' => 'user2@example.com']);
        User::factory()->admin()->create(['name' => 'user3', 'email' => 'user3@example.com']);
    }
}
