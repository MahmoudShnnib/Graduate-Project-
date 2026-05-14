<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Models\Specialization;
use App\Models\Student;
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
            'role' => 'secretary',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $specialization = Specialization::first();

        $studentUser = User::create([
            'name' => 'ppsan',
            'email' => 'ppsan@pp.com',
            'password' => bcrypt('ppsan@69'),
            'phone' => '0912345680',
            'role' => 'student',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Student::create([
            'user_id' => $studentUser->id,
            'specialization_id' => $specialization?->id,
            'status' => StudentStatus::Active,
            'gpa' => 3.50,
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $admin->assignRole('super_admin');
            $secretary->assignRole('secretary');
            $studentUser->assignRole('student');
        }
    }
}
