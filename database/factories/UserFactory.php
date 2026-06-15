<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
        ])->afterCreating(fn (User $user) => $this->assignSpatieRole($user, $role));
    }

    /**
     * Keep the Spatie role in sync with the users.role column, mirroring
     * production's RolesAndPermissionsSeeder::repairUserRoles(). Without this,
     * Shield gates (hasRole/can) see a user with no role and deny access.
     */
    protected function assignSpatieRole(User $user, string $columnRole): void
    {
        $spatieRole = $columnRole === 'secretary' ? 'manager_assistant' : $columnRole;
        $role = Role::firstOrCreate(['name' => $spatieRole, 'guard_name' => 'web']);
        $user->assignRole($role);
    }

    public function faculty(): static
    {
        return $this->afterCreating(fn (User $user) => $this->assignSpatieRole($user, 'faculty'))
            ->state(fn (array $attributes) => [
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
