<?php

namespace Database\Factories;

use App\Enums\ThesisStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\ThesisRegistration> */
class ThesisRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'title' => fake()->sentence(6),
            'title_en' => fake()->sentence(6),
            'status' => ThesisStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ThesisStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ThesisStatus::Rejected]);
    }
}
