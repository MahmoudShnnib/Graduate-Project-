<?php

namespace Database\Factories;

use App\Enums\GradeStatus;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    public function definition(): array
    {
        $coursework = fake()->randomFloat(1, 20, 55);
        $final = fake()->randomFloat(1, 15, 38);
        $total = $coursework + $final;
        $conversion = \App\Models\Grade::convertToLetter($total);

        return [
            'enrollment_id' => StudentEnrollment::factory(),
            'coursework_grade' => $coursework,
            'final_grade' => $final,
            'total' => $total,
            'letter' => $conversion['letter'],
            'points' => $conversion['points'],
            'status' => GradeStatus::Draft,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GradeStatus::Approved,
        ]);
    }

    public function passing(): static
    {
        return $this->state(function (array $attributes) {
            $coursework = 40.0;
            $final = 30.0;
            $total = 70.0;
            $conversion = \App\Models\Grade::convertToLetter($total);

            return [
                'coursework_grade' => $coursework,
                'final_grade' => $final,
                'total' => $total,
                'letter' => $conversion['letter'],
                'points' => $conversion['points'],
                'status' => GradeStatus::Approved,
            ];
        });
    }

    public function failing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'coursework_grade' => 20.0,
                'final_grade' => 15.0,
                'total' => 35.0,
                'letter' => 'F',
                'points' => 0.0,
                'status' => GradeStatus::Approved,
            ];
        });
    }
}
