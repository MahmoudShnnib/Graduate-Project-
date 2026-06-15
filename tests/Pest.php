<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => \App\Models\Semester::flushActiveCache())
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => \App\Models\Semester::flushActiveCache())
    ->in('Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Create a student user with a linked Student record.
 *
 * @return array{user: \App\Models\User, student: \App\Models\Student}
 */
function studentWithRecord(array $studentAttributes = []): array
{
    $user = \App\Models\User::factory()->withRole('student')->create();
    $specialization = \App\Models\Specialization::factory()->create();
    $student = \App\Models\Student::factory()->create(array_merge([
        'user_id' => $user->id,
        'specialization_id' => $specialization->id,
    ], $studentAttributes));

    return compact('user', 'student');
}

/**
 * Create an admin user with the super_admin Spatie role assigned.
 */
function adminUser(): \App\Models\User
{
    $user = \App\Models\User::factory()->withRole('super_admin')->create();
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole($role);

    return $user;
}

/**
 * Seed the real Shield roles + permissions (idempotent) so role-permission
 * gating can be exercised in tests. Cheap after the first call per test.
 */
function seedShieldRolesAndPermissions(): void
{
    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();
}

/**
 * Create a faculty user with the Shield 'faculty' role (only the
 * ViewAny/View:CourseOffering permissions) assigned.
 */
function facultyUser(): \App\Models\User
{
    seedShieldRolesAndPermissions();
    $user = \App\Models\User::factory()->withRole('faculty')->create();
    $user->syncRoles(['faculty']);

    return $user;
}

/**
 * Create a secretary user with the Shield 'manager_assistant' role (the full
 * admin permission set minus Role/User/AuditLog) assigned.
 */
function secretaryUser(): \App\Models\User
{
    seedShieldRolesAndPermissions();
    $user = \App\Models\User::factory()->withRole('secretary')->create();
    $user->syncRoles(['manager_assistant']);

    return $user;
}
