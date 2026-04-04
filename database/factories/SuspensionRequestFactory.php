<?php

namespace Database\Factories;

use App\Enums\SuspensionStatus;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SuspensionRequest>
 */
class SuspensionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'semester_id' => Semester::factory(),
            'reason' => fake()->paragraph(),
            'status' => SuspensionStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => SuspensionStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => SuspensionStatus::Rejected]);
    }
}
