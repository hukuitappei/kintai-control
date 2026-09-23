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
        // AttendanceSeederはUser::all()でユーザーを取得するので、呼び出す順番に注意。
        $this->call([UserSeeder::class, AttendanceSeeder::class]);
    }
}
