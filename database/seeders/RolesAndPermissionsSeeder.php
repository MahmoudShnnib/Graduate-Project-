<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'super_admin' => 'رئيس مكتب الدراسات العليا',
            'secretary' => 'السكرتير',
            'faculty' => 'عضو هيئة التدريس',
            'student' => 'الطالب',
        ];

        foreach ($roles as $slug => $displayName) {
            Role::firstOrCreate(
                ['name' => $slug, 'guard_name' => 'web'],
            );
        }
    }
}
