<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seeds Shield roles with correct permissions and syncs existing users'
     * Spatie roles to match their users.role column.
     *
     * Shield config: permissions.case = 'pascal', separator = ':'
     * → permission names look like 'ViewAny:CourseOffering', 'Create:Student'.
     *
     * Idempotent — safe to re-run.
     */
    public function run(): void
    {
        // Self-heal: if the permissions table is empty (post `migrate:fresh` or
        // accidental wipe), regenerate Shield's permissions before mapping roles.
        // Without this, every role would sync to an empty permission set.
        if (Permission::count() === 0) {
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--no-interaction' => true,
            ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $secretary = Role::firstOrCreate(['name' => 'manager_assistant', 'guard_name' => 'web']);
        $faculty = Role::firstOrCreate(['name' => 'faculty', 'guard_name' => 'web']);
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $this->syncRolePermissions($superAdmin, $secretary, $faculty, $student);
        $this->repairUserRoles();
    }

    /**
     * Assigns the appropriate subset of Shield permissions to each role.
     *
     * Admin-only models (Role, User, AuditLog) are never granted to roles
     * below super_admin — per the 2026-05-15 RBAC posture decision (C-3
     * audit fix). See _brain/decisions/2026-05-15-rbac-posture.md.
     */
    private function syncRolePermissions(Role $superAdmin, Role $secretary, Role $faculty, Role $student): void
    {
        // Models off-limits to every non-super-admin role.
        $adminOnlyModels = ['Role', 'User', 'AuditLog'];

        // Super admin: every permission. Redundant with Gate::before bypass,
        // but keeps Shield UI accurate.
        $superAdmin->syncPermissions(Permission::all());

        // Secretary: read everything (except admin-only models), manage
        // admissions/students/enrollments/grades/requests.
        $secretaryPermissions = Permission::query()
            ->where(function ($q) {
                $q->where('name', 'like', 'ViewAny:%')
                    ->orWhere('name', 'like', 'View:%');
            })
            ->orWhereIn('name', [
                'Create:Student', 'Update:Student', 'Delete:Student',
                'Update:Application',
                'Create:StudentEnrollment', 'Update:StudentEnrollment', 'Delete:StudentEnrollment',
                'Update:Grade',
                'Update:ThesisRegistration',
                'Update:SuspensionRequest',
                'Update:AddDropRequest',
                'Create:CourseOffering', 'Update:CourseOffering', 'Delete:CourseOffering',
                'Create:Semester', 'Update:Semester',
                'Create:Course', 'Update:Course',
                'Create:Specialization', 'Update:Specialization',
            ])
            ->get()
            // Strip every permission ending in `:Role`, `:User`, or `:AuditLog`.
            // Covers all CRUD verbs at once so future Shield regenerations
            // can't silently leak admin-only access into the secretary role.
            ->reject(fn (Permission $p): bool => collect($adminOnlyModels)
                ->contains(fn (string $model): bool => str_ends_with($p->name, ":{$model}")));

        $secretary->syncPermissions($secretaryPermissions);

        // Faculty: read students + course offerings, grade entry on own courses
        // (row-level ownership is enforced in the resource, not Shield).
        $faculty->syncPermissions(Permission::whereIn('name', [
            'ViewAny:Grade', 'View:Grade', 'Create:Grade', 'Update:Grade',
            'ViewAny:Student', 'View:Student',
            'ViewAny:CourseOffering', 'View:CourseOffering',
        ])->get());

        // Student: no Shield permissions needed. Student panel access is gated
        // via EnsureStudentRole middleware + role column check, not Shield policies.
        $student->syncPermissions([]);
    }

    /**
     * Repairs existing users whose Spatie role doesn't match their role column.
     *
     * Background: historically User rows were created without calling
     * $user->assignRole(...), so the HasPanelShield trait fell back to
     * auto-assigning 'panel_user' for many users. This syncs them to the
     * Spatie role matching their column value.
     */
    private function repairUserRoles(): void
    {
        $knownRoles = ['super_admin', 'manager_assistant', 'faculty', 'student'];

        User::query()
            ->whereIn('role', $knownRoles)
            ->get()
            ->each(function (User $user): void {
                // syncRoles replaces the full role set — safe and idempotent.
                $user->syncRoles([$user->role]);
            });
    }
}
