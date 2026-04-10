<?php

namespace Database\Seeders;

use App\Actions\CreateApplication;
use App\Models\Specialization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApplicationSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $applicants = [
        [
            'name' => 'محمد أحمد علي الزاوي',
            'last_name' => 'الزاوي',
            'email' => 'mohammed.zawai@example.com',
            'phone' => '0912345678',
            'mother_name' => 'فاطمة المبروك',
            'birth_date' => '1998-03-15',
            'birth_place' => 'طرابلس',
            'gender' => 'male',
            'marital_status' => 'single',
            'nationality' => 'ليبية',
            'national_id' => '119980315001',
            'card_number' => '54321',
            'residence' => 'طرابلس',
            'permanent_address' => 'طرابلس - حي الأندلس - شارع الجمهورية',
            'profession' => 'مهندس',
            'workplace' => 'شركة الاتصالات الليبية',
            'study_type' => 'self_funded',
            'is_transfer' => false,
        ],
        [
            'name' => 'أحمد سالم عبدالله البرعصي',
            'last_name' => 'البرعصي',
            'email' => 'ahmed.barasi@example.com',
            'phone' => '0923456789',
            'mother_name' => 'عائشة محمد',
            'birth_date' => '1996-07-22',
            'birth_place' => 'بنغازي',
            'gender' => 'male',
            'marital_status' => 'married',
            'nationality' => 'ليبية',
            'national_id' => '119960722002',
            'card_number' => '67890',
            'residence' => 'بنغازي',
            'permanent_address' => 'بنغازي - الفويهات - شارع جمال عبدالناصر',
            'profession' => 'معيد',
            'workplace' => 'كلية الهندسة - جامعة بنغازي',
            'study_type' => 'faculty_assistant',
            'delegation_number' => '2026/123',
            'delegation_entity' => 'جامعة بنغازي',
            'is_transfer' => false,
        ],
        [
            'name' => 'خالد عمر مصطفى الطرابلسي',
            'last_name' => 'الطرابلسي',
            'email' => 'khaled.trabelsi@example.com',
            'phone' => '0934567890',
            'mother_name' => 'سعاد الهادي',
            'birth_date' => '1997-11-05',
            'birth_place' => 'مصراتة',
            'gender' => 'male',
            'marital_status' => 'single',
            'nationality' => 'ليبية',
            'national_id' => '119971105003',
            'card_number' => '11223',
            'residence' => 'مصراتة',
            'permanent_address' => 'مصراتة - المنطقة المركزية - شارع طرابلس',
            'study_type' => 'internally_delegated',
            'delegation_number' => '2026/456',
            'is_transfer' => true,
        ],
        [
            'name' => 'سارة يوسف إبراهيم المصراتي',
            'last_name' => 'المصراتي',
            'email' => 'sara.misrati@example.com',
            'phone' => '0945678901',
            'mother_name' => 'نجاة العربي',
            'birth_date' => '1999-01-20',
            'birth_place' => 'زليتن',
            'gender' => 'female',
            'marital_status' => 'single',
            'nationality' => 'ليبية',
            'national_id' => '219990120004',
            'card_number' => '33445',
            'residence' => 'طرابلس',
            'permanent_address' => 'زليتن - المنطقة الشرقية',
            'study_type' => 'self_funded',
            'is_transfer' => false,
        ],
        [
            'name' => 'عبدالرحمن مفتاح سليمان الفيتوري',
            'last_name' => 'الفيتوري',
            'email' => 'abdulrahman.fitouri@example.com',
            'phone' => '0956789012',
            'mother_name' => 'خديجة أبوبكر',
            'birth_date' => '1995-09-10',
            'birth_place' => 'سبها',
            'gender' => 'male',
            'marital_status' => 'married',
            'nationality' => 'ليبية',
            'national_id' => '119950910005',
            'card_number' => '55667',
            'residence' => 'سبها',
            'permanent_address' => 'سبها - حي المنشية - شارع الجيش',
            'profession' => 'فني اتصالات',
            'workplace' => 'شركة ليبيانا',
            'study_type' => 'public_delegation',
            'delegation_number' => '2026/789',
            'delegation_entity' => 'وزارة الاتصالات',
            'is_transfer' => false,
        ],
        [
            'name' => 'نورة عادل حسن الغرياني',
            'last_name' => 'الغرياني',
            'email' => 'noura.gheryani@example.com',
            'phone' => '0917890123',
            'mother_name' => 'مريم الصادق',
            'birth_date' => '2000-04-18',
            'birth_place' => 'غريان',
            'gender' => 'female',
            'marital_status' => 'single',
            'nationality' => 'ليبية',
            'national_id' => '220000418006',
            'card_number' => '77889',
            'residence' => 'طرابلس',
            'permanent_address' => 'غريان - وسط المدينة',
            'study_type' => 'self_funded',
            'is_transfer' => false,
        ],
        [
            'name' => 'يوسف صالح محمد الحاسي',
            'last_name' => 'الحاسي',
            'email' => 'yousef.hassi@example.com',
            'phone' => '0928901234',
            'mother_name' => 'زينب عمران',
            'birth_date' => '1997-06-30',
            'birth_place' => 'البيضاء',
            'gender' => 'male',
            'marital_status' => 'single',
            'nationality' => 'ليبية',
            'national_id' => '119970630007',
            'card_number' => '99001',
            'residence' => 'البيضاء',
            'permanent_address' => 'البيضاء - شارع عمر المختار',
            'profession' => 'مبرمج',
            'workplace' => 'شركة تقنية المعلومات',
            'study_type' => 'self_funded',
            'is_transfer' => false,
        ],
        [
            'name' => 'هالة عبدالسلام خليفة الوفالي',
            'last_name' => 'الوفالي',
            'email' => 'hala.wefali@example.com',
            'phone' => '0939012345',
            'mother_name' => 'آمنة البشير',
            'birth_date' => '1998-12-01',
            'birth_place' => 'الخمس',
            'gender' => 'female',
            'marital_status' => 'married',
            'nationality' => 'ليبية',
            'national_id' => '219981201008',
            'card_number' => '22334',
            'residence' => 'الخمس',
            'permanent_address' => 'الخمس - حي النصر',
            'profession' => 'مهندسة حاسوب',
            'workplace' => 'المصرف المركزي',
            'study_type' => 'self_funded',
            'is_transfer' => false,
        ],
    ];

    public function run(): void
    {
        $specializations = Specialization::pluck('id')->toArray();
        $createApplication = app(CreateApplication::class);

        foreach ($this->applicants as $i => $data) {
            $data['specialization_id'] = $specializations[$i % count($specializations)];
            $data['password'] = Hash::make('12345678');
            $createApplication->handle($data);
        }
    }
}
