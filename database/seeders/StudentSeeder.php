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
     *
     * Scenarios:
     *   passed   — enrolled + approved passing grades (total ≥ 75, B+)
     *   failed   — enrolled + approved failing grades (total < 50, F)
     *   thesis   — passed all courses + approved thesis registration, no discussion yet
     *   graduate — passed all courses + thesis approved + discussion completed
     */
    private array $students = [
        // ── Passed (4) ───────────────────────────────────────────────────
        [
            'enrollment_no' => '251103',
            'name' => 'سارة محمود القذافي',
            'last_name' => 'القذافي',
            'email' => 'sara.qaddafi@example.ly',
            'phone' => '0911100001',
            'specialization_code' => 1,
            'scenario' => 'passed',
        ],
        [
            'enrollment_no' => '252102',
            'name' => 'عمر خالد المقرحي',
            'last_name' => 'المقرحي',
            'email' => 'omar.miqrahi@example.ly',
            'phone' => '0911100002',
            'specialization_code' => 2,
            'scenario' => 'passed',
        ],
        [
            'enrollment_no' => '253101',
            'name' => 'هالة سالم الزنتاني',
            'last_name' => 'الزنتاني',
            'email' => 'hala.zentani@example.ly',
            'phone' => '0911100003',
            'specialization_code' => 3,
            'scenario' => 'passed',
        ],
        [
            'enrollment_no' => '251104',
            'name' => 'نادر يوسف الشويهدي',
            'last_name' => 'الشويهدي',
            'email' => 'nader.shuwaihdi@example.ly',
            'phone' => '0911100004',
            'specialization_code' => 1,
            'scenario' => 'passed',
        ],

        // ── Failed (3) ────────────────────────────────────────────────────
        [
            'enrollment_no' => '252103',
            'name' => 'محمد فرج الطرابلسي',
            'last_name' => 'الطرابلسي',
            'email' => 'mohammed.tarabolsi@example.ly',
            'phone' => '0911100005',
            'specialization_code' => 2,
            'scenario' => 'failed',
        ],
        [
            'enrollment_no' => '253102',
            'name' => 'مريم علي الشريف',
            'last_name' => 'الشريف',
            'email' => 'mariam.shareef@example.ly',
            'phone' => '0911100006',
            'specialization_code' => 3,
            'scenario' => 'failed',
        ],
        [
            'enrollment_no' => '251105',
            'name' => 'يونس صالح البركي',
            'last_name' => 'البركي',
            'email' => 'younes.barki@example.ly',
            'phone' => '0911100007',
            'specialization_code' => 1,
            'scenario' => 'failed',
        ],

        // ── Thesis eligible (3) ───────────────────────────────────────────
        [
            'enrollment_no' => '251106',
            'name' => 'ريم سالم الغرياني',
            'last_name' => 'الغرياني',
            'email' => 'reem.gharyani@example.ly',
            'phone' => '0911100008',
            'specialization_code' => 1,
            'scenario' => 'thesis',
        ],
        [
            'enrollment_no' => '252104',
            'name' => 'كمال عمر الورشفاني',
            'last_name' => 'الورشفاني',
            'email' => 'kamal.warshfani@example.ly',
            'phone' => '0911100009',
            'specialization_code' => 2,
            'scenario' => 'thesis',
        ],
        [
            'enrollment_no' => '253103',
            'name' => 'أسماء حسين الزاوي',
            'last_name' => 'الزاوي',
            'email' => 'asmaa.zawawi@example.ly',
            'phone' => '0911100010',
            'specialization_code' => 3,
            'scenario' => 'thesis',
        ],

        // ── Graduated (3) ─────────────────────────────────────────────────
        [
            'enrollment_no' => '251107',
            'name' => 'كريم علي الفيتوري',
            'last_name' => 'الفيتوري',
            'email' => 'kareem.faituri@example.ly',
            'phone' => '0911100011',
            'specialization_code' => 1,
            'scenario' => 'graduate',
        ],
        [
            'enrollment_no' => '252105',
            'name' => 'لمياء محمد الحاسي',
            'last_name' => 'الحاسي',
            'email' => 'lamya.hassi@example.ly',
            'phone' => '0911100012',
            'specialization_code' => 2,
            'scenario' => 'graduate',
        ],
        [
            'enrollment_no' => '253104',
            'name' => 'طارق أحمد المزوغي',
            'last_name' => 'المزوغي',
            'email' => 'tarek.mazughi@example.ly',
            'phone' => '0911100013',
            'specialization_code' => 3,
            'scenario' => 'graduate',
        ],
    ];

    public function run(): void
    {
        $gpaByScenario = [
            'passed' => 3.50,
            'failed' => 0.00,
            'thesis' => 4.00,
            'graduate' => 4.00,
        ];

        foreach ($this->students as $data) {
            $specialization = Specialization::where('code', $data['specialization_code'])->firstOrFail();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('12345678'),
                    'phone' => $data['phone'],
                    'role' => 'student',
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
                    'status' => StudentStatus::Active,
                    'gpa' => $gpaByScenario[$data['scenario']],
                ]
            );

            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $user->assignRole('student');
            }
        }
    }
}
