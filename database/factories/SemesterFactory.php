<?php

namespace Database\Factories;

use App\Enums\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Semester>
 */
class SemesterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'خريف '.fake()->year(),
            'year' => fake()->year(),
            'season' => Season::Autumn,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonths(4)->endOfMonth(),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function withAddDrop(): static
    {
        return $this->state(fn (array $attributes) => [
            'add_drop_start' => now()->subDays(7),
            'add_drop_end' => now()->addDays(7),
        ]);
    }
}
