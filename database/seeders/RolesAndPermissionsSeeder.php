<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $secretary = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        $faculty = Role::firstOrCreate(['name' => 'faculty', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // super_admin gets everything
        $superAdmin->syncPermissions(Permission::all());

        // secretary: manage students, applications, enrollments, grade review, thesis, suspension, add-drop
        $secretary->syncPermissions(Permission::where('name', 'like', 'view_%')
            ->orWhereIn('name', [
                'create_student', 'update_student',
                'update_application',
                'create_student_enrollment', 'update_student_enrollment', 'delete_student_enrollment',
                'update_grade',
                'update_thesis_registration',
                'update_suspension_request',
                'update_add_drop_request',
            ])->get());

        // faculty: grade entry and course/student visibility
        $faculty->syncPermissions(Permission::whereIn('name', [
            'view_grade', 'view_any_grade', 'create_grade', 'update_grade',
            'view_student', 'view_any_student',
            'view_course_offering', 'view_any_course_offering',
        ])->get());
    }
}
