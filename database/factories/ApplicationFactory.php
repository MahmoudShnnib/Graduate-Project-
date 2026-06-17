<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Specialization;
use App\Services\TrackingCodeService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        $arabicFirstNames = ['محمد', 'أحمد', 'خالد', 'عمر', 'يوسف', 'علي', 'سارة', 'نورة', 'هالة', 'فاطمة', 'مريم', 'زينب'];
        $arabicLastNames = ['الزاوي', 'البرعصي', 'الطرابلسي', 'الفيتوري', 'الغرياني', 'الحاسي', 'الوفالي', 'المصراتي', 'القذافي', 'الشريف'];
        $arabicCities = ['طرابلس', 'بنغازي', 'مصراتة', 'سبها', 'الزاوية', 'زليتن', 'البيضاء', 'غريان', 'الخمس', 'صرمان'];
        $studyTypes = ['self_funded', 'internally_delegated', 'faculty_assistant', 'public_delegation'];

        $firstName = fake()->randomElement($arabicFirstNames);
        $fatherName = fake()->randomElement($arabicFirstNames);
        $grandName = fake()->randomElement($arabicFirstNames);
        $lastName = fake()->randomElement($arabicLastNames);
        $motherFirstName = fake()->randomElement($arabicFirstNames);
        $motherLastName = fake()->randomElement($arabicLastNames);
        $city = fake()->randomElement($arabicCities);
        $gender = fake()->randomElement(['male', 'female']);
        $studyType = fake()->randomElement($studyTypes);

        $trackingCodeService = app(TrackingCodeService::class);

        return [
            'user_id' => null,
            'name' => "{$firstName} {$fatherName} {$grandName} {$lastName}",
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('12345678'),
            'phone' => '09'.fake()->numerify('########'),
            'specialization_id' => Specialization::query()->inRandomOrder()->value('id'),
            'tracking_code' => $trackingCodeService->generate(),
            'status' => ApplicationStatus::Pending,
            'mother_name' => "{$motherFirstName} {$motherLastName}",
            'birth_date' => fake()->dateTimeBetween('-50 years', '-22 years')->format('Y-m-d'),
            'birth_place' => $city,
            'gender' => $gender,
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'nationality' => 'ليبيا',
            'national_id' => ($gender === 'male' ? '1' : '2').fake()->numerify('###########'),
            'card_number' => fake()->optional(0.7)->numerify('#####'),
            'residence' => $city,
            'permanent_address' => "{$city} - ".fake()->randomElement(['حي الأندلس', 'حي النصر', 'المنطقة المركزية', 'شارع عمر المختار', 'وسط المدينة']),
            'profession' => fake()->optional(0.6)->randomElement(['مهندس', 'معلم', 'طبيب', 'محاسب', 'مبرمج', 'فني', 'موظف']),
            'workplace' => fake()->optional(0.5)->randomElement(['جامعة طرابلس', 'شركة الاتصالات', 'وزارة التعليم', 'المصرف المركزي', 'شركة ليبيانا']),
            'study_type' => $studyType,
            'delegation_number' => in_array($studyType, ['internally_delegated', 'public_delegation', 'faculty_assistant']) ? '2026/'.fake()->numerify('###') : null,
            'delegation_entity' => in_array($studyType, ['public_delegation', 'faculty_assistant']) ? fake()->randomElement(['جامعة بنغازي', 'وزارة الاتصالات', 'وزارة التعليم', 'شركة ليبيانا']) : null,
            'is_transfer' => fake()->boolean(20),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Accepted,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
