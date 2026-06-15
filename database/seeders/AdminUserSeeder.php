<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'رئيس مكتب الدراسات العليا',
            'email' => 'admin@college.edu',
            'password' => bcrypt('admin123'),
            'phone' => '0912345678',
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $secretary = User::create([
            'name' => 'السكرتير',
            'email' => 'secretary@college.edu',
            'password' => bcrypt('secretary123'),
            'phone' => '0912345679',
            'role' => 'manager_assistant',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $admin->assignRole('super_admin');
            $secretary->assignRole('manager_assistant');
        }
    }
}
