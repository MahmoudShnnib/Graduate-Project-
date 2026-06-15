<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Valid Libyan mobile format (09XXXXXXXX). fake()->phoneNumber() can
            // emit extensions/letters that fail the student form's ->tel() regex,
            // which made phone-validating tests flaky by Faker-state position.
            'phone' => '09'.fake()->numerify('########'),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    public function faculty(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'faculty',
            'national_id' => fake()->unique()->numerify('############'),
            'birth_date' => fake()->dateTimeBetween('-65 years', '-30 years')->format('Y-m-d'),
            'birth_place' => fake()->randomElement(['طرابلس', 'بنغازي', 'مصراتة', 'سبها', 'الزاوية', 'صبراتة', 'درنة']),
            'nationality' => 'ليبية',
            'gender' => fake()->randomElement(['male', 'female']),
            'academic_rank' => fake()->randomElement(['أستاذ', 'أستاذ مشارك', 'أستاذ مساعد', 'محاضر']),
            'qualification' => fake()->randomElement(['دكتوراه', 'ماجستير']),
            'institution' => 'كلية التقنية الإلكترونية – طرابلس',
        ]);
    }
}
