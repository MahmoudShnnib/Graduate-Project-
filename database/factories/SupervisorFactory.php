<?php

namespace Database\Factories;

use App\Models\Supervisor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supervisor> */
class SupervisorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'academic_rank' => fake()->randomElement(['أستاذ', 'أستاذ مشارك', 'أستاذ مساعد', 'محاضر']),
            'qualification' => fake()->randomElement(['دكتوراه', 'ماجستير']),
            'institution' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '09'.fake()->numerify('########'),
        ];
    }
}
