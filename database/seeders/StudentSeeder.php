<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $students = [
        [
            'enrollment_no' => '251101',
            'name' => 'محمد أحمد علي الزاوي',
            'last_name' => 'الزاوي',
            'email' => 'mohammed.zawai@example.com',
            'password' => '$2y$12$YTBdmxjlUnAgnViiOLWmZ.15mIi3ffb4YW4KANW8dj82X6GE2yFwW',
            'phone' => '0912345678',
            'specialization_code' => 1,
            'status' => StudentStatus::Active,
        ],
        [
            'enrollment_no' => '252101',
            'name' => 'أحمد سالم عبدالله البرعصي',
            'last_name' => 'البرعصي',
            'email' => 'ahmed.barasi@example.com',
            'password' => '$2y$12$D.vEDqds5s21Em3p4SfPseC/8dgCpQFGNKQzlS2R1H7QrIC9apjiq',
            'phone' => '0923456789',
            'specialization_code' => 2,
            'status' => StudentStatus::Active,
        ],
        [
            'enrollment_no' => '251102',
            'name' => 'يوسف صالح محمد الحاسي',
            'last_name' => 'الحاسي',
            'email' => 'yousef.hassi@example.com',
            'password' => '$2y$12$op8lZhQ.3skpYJ6tb5SgWOOYxbX3xcnKdjlLYbkLUUrciV/p81dP2',
            'phone' => '0928901234',
            'specialization_code' => 1,
            'status' => StudentStatus::Active,
        ],
    ];

    public function run(): void
    {
        foreach ($this->students as $data) {
            $specialization = Specialization::where('code', $data['specialization_code'])->firstOrFail();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'phone' => $data['phone'],
                    'role' => 'student',
                    'specialization_id' => $specialization->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            Student::firstOrCreate(
                ['enrollment_no' => $data['enrollment_no']],
                [
                    'user_id' => $user->id,
                    'specialization_id' => $specialization->id,
                    'last_name' => $data['last_name'],
                    'status' => $data['status'],
                    'gpa' => 0.00,
                ]
            );

            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $user->assignRole('student');
            }
        }
    }
}
