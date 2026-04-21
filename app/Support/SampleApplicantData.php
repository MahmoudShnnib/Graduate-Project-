<?php

namespace App\Support;

use App\Models\Specialization;
use Illuminate\Support\Str;

/**
 * Single source of truth for Arabic/English name pools + synthetic applicant
 * data used by the "توليد بيانات تجريبية" (generate test data) buttons.
 *
 * Two public generators:
 * - {@see self::forAdminStudent()} — keys match the CreateStudent Filament
 *   form field names (user_name, user_email, …).
 * - {@see self::forPublicApplication()} — keys match the /apply public
 *   registration form field names (name, email, …).
 *
 * Both share the same underlying pools so seed data stays consistent
 * across the two entry points.
 */
class SampleApplicantData
{
    /** @var list<string> */
    private const MALE_FIRST_NAMES_AR = [
        'محمد', 'أحمد', 'علي', 'يوسف', 'إبراهيم', 'عمر', 'خالد',
        'حسن', 'مصطفى', 'سالم', 'عبدالله', 'كريم',
    ];

    /** @var list<string> */
    private const FEMALE_FIRST_NAMES_AR = [
        'فاطمة', 'عائشة', 'مريم', 'زينب', 'هند', 'سارة', 'نور',
        'ليلى', 'خديجة', 'أمينة', 'منى', 'رانيا',
    ];

    /** @var list<string> */
    private const MIDDLE_NAMES_AR = [
        'أحمد', 'محمد', 'علي', 'مصطفى', 'إبراهيم', 'سعيد',
        'عبدالرحمن', 'يوسف', 'خالد', 'سالم',
    ];

    /** @var list<string> */
    private const SURNAMES_AR = [
        'الحسن', 'القذافي', 'الترهوني', 'المصراتي', 'الزنتاني',
        'الورفلي', 'البرعصي', 'الفيتوري', 'الشريف', 'العبيدي',
        'المغربي', 'الجربي',
    ];

    /** @var list<string> */
    private const CITIES_AR = [
        'طرابلس', 'بنغازي', 'مصراتة', 'الزاوية', 'سبها', 'البيضاء',
        'درنة', 'سرت', 'طبرق', 'زليتن',
    ];

    /** @var list<string> */
    private const STREETS_AR = [
        'شارع الجمهورية', 'شارع عمر المختار', 'شارع الاستقلال',
        'شارع النصر', 'شارع الفاتح', 'شارع الزاوية',
    ];

    /** @var list<string> */
    private const MALE_FIRST_NAMES_EN = [
        'Mohammed', 'Ahmed', 'Ali', 'Yousef', 'Ibrahim', 'Omar',
        'Khaled', 'Hassan', 'Mustafa', 'Salem', 'Abdullah', 'Kareem',
    ];

    /** @var list<string> */
    private const FEMALE_FIRST_NAMES_EN = [
        'Fatima', 'Aisha', 'Maryam', 'Zeinab', 'Hind', 'Sara', 'Nour',
        'Layla', 'Khadija', 'Amina', 'Mona', 'Rania',
    ];

    /** @var list<string> */
    private const MIDDLE_NAMES_EN = [
        'Ahmed', 'Mohammed', 'Ali', 'Mustafa', 'Ibrahim', 'Saeed',
        'AbdelRahman', 'Yousef', 'Khaled', 'Salem',
    ];

    /** @var list<string> */
    private const SURNAMES_EN = [
        'Al-Hassan', 'Al-Qaddafi', 'Al-Terhouni', 'Al-Misrati',
        'Al-Zentani', 'Al-Warfali', "Al-Bar'asi", 'Al-Fituri',
        'Al-Sharif', 'Al-Obeidi', 'Al-Maghribi', 'Al-Jarbi',
    ];

    /**
     * Produces a synthetic applicant keyed for the admin CreateStudent form.
     *
     * @return array<string, mixed>
     */
    public static function forAdminStudent(): array
    {
        $base = self::generateBase();
        $randomSuffix = Str::lower(Str::random(8));

        return [
            'user_name' => $base['full_name_ar'],
            'user_name_en' => $base['full_name_en'],
            'user_email' => "student.{$randomSuffix}@example.com",
            'user_phone' => '09'.fake()->numerify('########'),
            'user_password' => '12345678',
            'specialization_id' => Specialization::query()->inRandomOrder()->value('id'),
            'mother_name' => $base['mother_name'],
            'birth_date' => $base['birth_date'],
            'birth_place' => $base['birth_city'],
            'gender' => $base['gender'],
            'marital_status' => $base['marital_status'],
            'nationality' => 'ليبية',
            'national_id' => $base['national_id'],
            'card_number' => $base['card_number'],
            'residence' => $base['residence_city'],
            'permanent_address' => $base['permanent_address'],
            'profession' => 'طالب',
            'workplace' => 'لا يوجد',
            'study_type' => 'self_funded',
            'is_transfer' => false,
            'status' => \App\Enums\StudentStatus::Active,
            'warnings_count' => 0,
        ];
    }

    /**
     * Produces a synthetic applicant keyed for the public /apply form.
     * Uses the public-form field names (name, email, phone — no user_ prefix).
     *
     * @return array<string, mixed>
     */
    public static function forPublicApplication(): array
    {
        $base = self::generateBase();
        $randomSuffix = Str::lower(Str::random(8));

        return [
            'name' => $base['full_name_ar'],
            'name_en' => $base['full_name_en'],
            'mother_name' => $base['mother_name'],
            'birth_date' => $base['birth_date'],
            'birth_place' => $base['birth_city'],
            'gender' => $base['gender'],
            'marital_status' => $base['marital_status'],
            'nationality' => 'ليبية',
            'national_id' => $base['national_id'],
            'card_number' => $base['card_number'],
            'residence' => $base['residence_city'],
            'permanent_address' => $base['permanent_address'],
            'profession' => 'طالب',
            'workplace' => 'لا يوجد',
            'is_transfer' => '0',
            'email' => "applicant.{$randomSuffix}@example.com",
            'phone' => '09'.fake()->numerify('########'),
            'specialization_id' => Specialization::query()->inRandomOrder()->value('id'),
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'study_type' => 'self_funded',
            // Admin-only fields (not in public form) intentionally omitted:
            //   status, warnings_count
            // File inputs cannot be programmatically filled (browser security),
            // so bachelor_certificate + remedial_statement are excluded here.
        ];
    }

    /**
     * The shared core: name, birth, address, ID, status — keyed in a
     * form-agnostic way so each generator can remap to its target schema.
     *
     * @return array<string, mixed>
     */
    private static function generateBase(): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        $firstNameAr = fake()->randomElement($gender === 'male' ? self::MALE_FIRST_NAMES_AR : self::FEMALE_FIRST_NAMES_AR);
        $fatherAr = fake()->randomElement(self::MIDDLE_NAMES_AR);
        $grandfatherAr = fake()->randomElement(self::MIDDLE_NAMES_AR);
        $surnameAr = fake()->randomElement(self::SURNAMES_AR);

        $firstNameEn = fake()->randomElement($gender === 'male' ? self::MALE_FIRST_NAMES_EN : self::FEMALE_FIRST_NAMES_EN);
        $fatherEn = fake()->randomElement(self::MIDDLE_NAMES_EN);
        $grandfatherEn = fake()->randomElement(self::MIDDLE_NAMES_EN);
        $surnameEn = fake()->randomElement(self::SURNAMES_EN);

        $birthCity = fake()->randomElement(self::CITIES_AR);
        $residenceCity = fake()->randomElement(self::CITIES_AR);
        $street = fake()->randomElement(self::STREETS_AR);

        $motherFirstName = fake()->randomElement(self::FEMALE_FIRST_NAMES_AR);
        $motherMiddle = fake()->randomElement(self::MIDDLE_NAMES_AR);

        return [
            'gender' => $gender,
            'full_name_ar' => "{$firstNameAr} {$fatherAr} {$grandfatherAr} {$surnameAr}",
            'full_name_en' => "{$firstNameEn} {$fatherEn} {$grandfatherEn} {$surnameEn}",
            'mother_name' => "{$motherFirstName} {$motherMiddle}",
            'birth_date' => fake()->dateTimeBetween('-30 years', '-22 years')->format('Y-m-d'),
            'birth_city' => $birthCity,
            'residence_city' => $residenceCity,
            'permanent_address' => "{$street}، {$residenceCity}",
            'national_id' => fake()->numerify('############'),
            'card_number' => fake()->numerify('#########'),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
        ];
    }
}
