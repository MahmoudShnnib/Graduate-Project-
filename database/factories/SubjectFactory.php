<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->numerify('SUB-###'),
            'category' => fake()->randomElement(['general', 'cs', 'com', 'ctr']),
            'credits' => fake()->numberBetween(1, 4),
            'is_elective' => fake()->boolean(),
        ];
    }
}
