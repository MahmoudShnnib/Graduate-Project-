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

        $faculty1 = User::create([
            'name' => 'د. عبدالله الورفلي',
            'email' => 'cs.faculty@example.ly',
            'password' => bcrypt('12345678'),
            'phone' => '0912345681',
            'role' => 'faculty',
            'national_id' => '119801011234',
            'birth_date' => '1980-01-01',
            'birth_place' => 'طرابلس',
            'nationality' => 'ليبية',
            'gender' => 'male',
            'academic_rank' => 'أستاذ مشارك',
            'qualification' => 'دكتوراه',
            'institution' => 'كلية التقنية الإلكترونية – طرابلس',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $faculty2 = User::create([
            'name' => 'د. سالم عمر المقرحي',
            'email' => 'salem.miqrahi@faculty.ly',
            'password' => bcrypt('12345678'),
            'phone' => '0912345682',
            'role' => 'faculty',
            'national_id' => '219750520012',
            'birth_date' => '1975-05-20',
            'birth_place' => 'بنغازي',
            'nationality' => 'ليبية',
            'gender' => 'male',
            'academic_rank' => 'أستاذ',
            'qualification' => 'دكتوراه',
            'institution' => 'كلية التقنية الإلكترونية – طرابلس',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $faculty3 = User::create([
            'name' => 'د. فاطمة علي الزروق',
            'email' => 'fatima.zarruq@faculty.ly',
            'password' => bcrypt('12345678'),
            'phone' => '0912345683',
            'role' => 'faculty',
            'national_id' => '419820914567',
            'birth_date' => '1982-09-14',
            'birth_place' => 'مصراتة',
            'nationality' => 'ليبية',
            'gender' => 'female',
            'academic_rank' => 'أستاذ مشارك',
            'qualification' => 'دكتوراه',
            'institution' => 'كلية التقنية الإلكترونية – طرابلس',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $faculty4 = User::create([
            'name' => 'د. خالد محمد السويحلي',
            'email' => 'khalid.suwihli@faculty.ly',
            'password' => bcrypt('12345678'),
            'phone' => '0912345684',
            'role' => 'faculty',
            'national_id' => '119901225890',
            'birth_date' => '1990-12-25',
            'birth_place' => 'سبها',
            'nationality' => 'ليبية',
            'gender' => 'male',
            'academic_rank' => 'أستاذ مساعد',
            'qualification' => 'دكتوراه',
            'institution' => 'كلية التقنية الإلكترونية – طرابلس',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $specialization = Specialization::first();

        $studentUser = User::create([
            'name' => 'ppsan',
            'email' => 'ppsan@pp.com',
            'password' => bcrypt('12345678'),
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
            $faculty1->assignRole('faculty');
            $faculty2->assignRole('faculty');
            $faculty3->assignRole('faculty');
            $faculty4->assignRole('faculty');
            $studentUser->assignRole('student');
        }
    }
}
