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
            'national_id' => fake()->unique()->numerify('############'),
            'birth_date' => fake()->dateTimeBetween('-65 years', '-30 years')->format('Y-m-d'),
            'birth_place' => fake()->randomElement(['طرابلس', 'بنغازي', 'مصراتة', 'سبها', 'تونس', 'القاهرة', 'عمّان']),
            'nationality' => fake()->randomElement(['ليبيا', 'تونس', 'مصر', 'الأردن']),
            'gender' => fake()->randomElement(['male', 'female']),
            'academic_rank' => fake()->randomElement(['أستاذ', 'أستاذ مشارك', 'أستاذ مساعد', 'محاضر']),
            'qualification' => fake()->randomElement(['دكتوراه', 'ماجستير']),
            'institution' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '09'.fake()->numerify('########'),
        ];
    }
}
