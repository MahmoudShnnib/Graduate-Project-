<?php

namespace Database\Factories;

use App\Enums\CourseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => fake()->unique()->bothify('CS-###'),
            'credits' => fake()->numberBetween(1, 4),
            'type' => CourseType::General,
            'is_active' => true,
        ];
    }

    public function specialized(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CourseType::Specialized,
        ]);
    }

    public function elective(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CourseType::Elective,
        ]);
    }
}
