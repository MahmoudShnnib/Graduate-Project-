<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyStudentSeeder extends Seeder
{
    /**
     * @var array<int, array<string, string>>
     */
    private array $students = [
        [
            'name' => 'طالب أول اختبار',
            'email' => 'pp@1.com',
            'phone' => '0911111111',
        ],
        [
            'name' => 'طالب ثاني اختبار',
            'email' => 'pp@2.com',
            'phone' => '0922222222',
        ],
        [
            'name' => 'طالب ثالث اختبار',
            'email' => 'pp@3.com',
            'phone' => '0933333333',
        ],
    ];

    public function run(): void
    {
        $specializations = Specialization::orderBy('code')->get();

        foreach ($this->students as $index => $data) {
            $specialization = $specializations[$index % $specializations->count()];

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('12345678'),
                'phone' => $data['phone'],
                'role' => 'student',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            Student::create([
                'user_id' => $user->id,
                'specialization_id' => $specialization->id,
                'status' => StudentStatus::Active,
                'gpa' => 0.00,
            ]);

            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $user->assignRole('student');
            }
        }
    }
}
