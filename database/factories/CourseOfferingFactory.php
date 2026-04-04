<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseOffering>
 */
class CourseOfferingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'semester_id' => Semester::factory(),
            'faculty_id' => null,
            'is_open' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['is_open' => false]);
    }
}
