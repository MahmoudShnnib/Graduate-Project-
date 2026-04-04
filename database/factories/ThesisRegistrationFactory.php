<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThesisRegistration>
 */
class ThesisRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'title' => fake()->sentence(6),
            'supervisor_name' => fake()->name(),
            'supervisor_degree' => 'دكتوراه',
            'supervisor_employer' => fake()->company(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'rejected']);
    }
}
