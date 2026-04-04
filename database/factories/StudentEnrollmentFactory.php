<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\CourseOffering;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_offering_id' => CourseOffering::factory(),
            'status' => EnrollmentStatus::Enrolled,
        ];
    }

    public function dropped(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EnrollmentStatus::Dropped]);
    }
}
