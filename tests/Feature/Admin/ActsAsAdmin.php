<?php

namespace Tests\Feature\Admin;

use App\Models\User;

trait ActsAsAdmin
{
    /**
     * 管理者ログイン画面（/admin/login）から入った管理者としてログインする（C6）。
     */
    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create(['name' => '管理 者']);

        return $this->actingAs($admin)->withSession(['admin_login' => true]);
    }
}
