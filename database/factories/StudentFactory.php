<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'specialization_id' => Specialization::inRandomOrder()->first()?->id ?? Specialization::factory(),
            'status' => StudentStatus::Active,
            'gpa' => fake()->randomFloat(2, 0, 4),
            'warnings_count' => 0,
            'suspension_count' => 0,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Suspended,
        ]);
    }

    public function graduated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Graduated,
        ]);
    }
}
