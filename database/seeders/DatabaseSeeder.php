<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // AttendanceSeederはユーザー作成後に実行する
        $this->call([UserSeeder::class, AttendanceSeeder::class]);
    }
}
