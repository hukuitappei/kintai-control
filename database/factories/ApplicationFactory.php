<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'attendance_record_id' => AttendanceRecord::factory(),
            'new_date' => fake()->date(),
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => fake()->sentence(),
            'approval_status' => '承認待ち',
            'application_date' => fake()->date(),
        ];
    }

    /**
     * 承認済みの状態。
     * 使い方: Application::factory()->approved()->create([...])
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => '承認済み',
        ]);
    }
}
