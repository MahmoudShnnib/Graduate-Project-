<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentNumberService;
use App\Services\GradeCalculationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fillSampleData')
                ->label('توليد بيانات تجريبية')
                ->icon(Heroicon::Sparkles)
                ->color('warning')
                ->action(function (): void {
                    $this->form->fill($this->getSampleStudentData());
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSampleStudentData(): array
    {
        $maleFirstNames = ['محمد', 'أحمد', 'علي', 'يوسف', 'إبراهيم', 'عمر', 'خالد', 'حسن', 'مصطفى', 'سالم', 'عبدالله', 'كريم'];
        $femaleFirstNames = ['فاطمة', 'عائشة', 'مريم', 'زينب', 'هند', 'سارة', 'نور', 'ليلى', 'خديجة', 'أمينة', 'منى', 'رانيا'];
        $middleNames = ['أحمد', 'محمد', 'علي', 'مصطفى', 'إبراهيم', 'سعيد', 'عبدالرحمن', 'يوسف', 'خالد', 'سالم'];
        $surnames = ['الحسن', 'القذافي', 'الترهوني', 'المصراتي', 'الزنتاني', 'الورفلي', 'البرعصي', 'الفيتوري', 'الشريف', 'العبيدي', 'المغربي', 'الجربي'];
        $cities = ['طرابلس', 'بنغازي', 'مصراتة', 'الزاوية', 'سبها', 'البيضاء', 'درنة', 'سرت', 'طبرق', 'زليتن'];
        $streets = ['شارع الجمهورية', 'شارع عمر المختار', 'شارع الاستقلال', 'شارع النصر', 'شارع الفاتح', 'شارع الزاوية'];

        $gender = fake()->randomElement(['male', 'female']);
        $firstName = fake()->randomElement($gender === 'male' ? $maleFirstNames : $femaleFirstNames);
        $father = fake()->randomElement($middleNames);
        $grandfather = fake()->randomElement($middleNames);
        $surname = fake()->randomElement($surnames);
        $birthCity = fake()->randomElement($cities);
        $residenceCity = fake()->randomElement($cities);
        $randomSuffix = Str::lower(Str::random(8));

        $maleEnFirstNames = ['Mohammed', 'Ahmed', 'Ali', 'Yousef', 'Ibrahim', 'Omar', 'Khaled', 'Hassan', 'Mustafa', 'Salem', 'Abdullah', 'Kareem'];
        $femaleEnFirstNames = ['Fatima', 'Aisha', 'Maryam', 'Zeinab', 'Hind', 'Sara', 'Nour', 'Layla', 'Khadija', 'Amina', 'Mona', 'Rania'];
        $enMiddleNames = ['Ahmed', 'Mohammed', 'Ali', 'Mustafa', 'Ibrahim', 'Saeed', 'AbdelRahman', 'Yousef', 'Khaled', 'Salem'];
        $enSurnames = ['Al-Hassan', 'Al-Qaddafi', 'Al-Terhouni', 'Al-Misrati', 'Al-Zentani', 'Al-Warfali', 'Al-Bar\'asi', 'Al-Fituri', 'Al-Sharif', 'Al-Obeidi', 'Al-Maghribi', 'Al-Jarbi'];
        $firstNameEn = fake()->randomElement($gender === 'male' ? $maleEnFirstNames : $femaleEnFirstNames);
        $fatherEn = fake()->randomElement($enMiddleNames);
        $grandfatherEn = fake()->randomElement($enMiddleNames);
        $surnameEn = fake()->randomElement($enSurnames);

        return [
            'user_name' => "{$firstName} {$father} {$grandfather} {$surname}",
            'user_name_en' => "{$firstNameEn} {$fatherEn} {$grandfatherEn} {$surnameEn}",
            'user_email' => "student.{$randomSuffix}@example.com",
            'user_phone' => '09'.fake()->numerify('########'),
            'user_password' => '12345678',
            'specialization_id' => Specialization::query()->inRandomOrder()->value('id'),
            'mother_name' => fake()->randomElement($femaleFirstNames).' '.fake()->randomElement($middleNames),
            'birth_date' => fake()->dateTimeBetween('-30 years', '-18 years')->format('Y-m-d'),
            'birth_place' => $birthCity,
            'gender' => $gender,
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'nationality' => 'ليبية',
            'national_id' => fake()->numerify('############'),
            'card_number' => fake()->numerify('#########'),
            'residence' => $residenceCity,
            'permanent_address' => fake()->randomElement($streets).'، '.$residenceCity,
            'profession' => 'طالب',
            'workplace' => 'لا يوجد',
            'study_type' => 'self_funded',
            'is_transfer' => false,
            'status' => \App\Enums\StudentStatus::Active,
            'warnings_count' => 0,
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $nameParts = preg_split('/\s+/', trim($data['user_name']));
        $lastName = end($nameParts) ?: null;

        $user = User::create([
            'name' => $data['user_name'],
            'name_en' => $data['user_name_en'] ?? null,
            'email' => $data['user_email'],
            'phone' => $data['user_phone'] ?? null,
            'password' => Hash::make($data['user_password']),
            'role' => 'student',
            'specialization_id' => $data['specialization_id'],
            'is_active' => true,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'specialization_id' => $data['specialization_id'],
            'status' => $data['status'],
            'warnings_count' => $data['warnings_count'] ?? 0,
            'last_name' => $lastName,
            'mother_name' => $data['mother_name'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'gender' => $data['gender'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'card_number' => $data['card_number'] ?? null,
            'residence' => $data['residence'] ?? null,
            'permanent_address' => $data['permanent_address'] ?? null,
            'profession' => $data['profession'] ?? null,
            'workplace' => $data['workplace'] ?? null,
            'study_type' => $data['study_type'] ?? null,
            'delegation_number' => $data['delegation_number'] ?? null,
            'delegation_entity' => $data['delegation_entity'] ?? null,
            'is_transfer' => $data['is_transfer'] ?? false,
            'degree_image' => $data['degree_image'] ?? null,
        ]);

        $activeSemester = Semester::where('is_active', true)->first();

        if ($activeSemester) {
            app(EnrollmentNumberService::class)->generate(
                $student,
                $activeSemester,
                (bool) ($data['is_transfer'] ?? false)
            );
        }

        $transferCourses = $data['transfer_courses'] ?? [];

        if (! empty($transferCourses) && $data['is_transfer']) {
            foreach ($transferCourses as $courseData) {
                $student->transferCourses()->create([
                    'course_id' => $courseData['course_id'],
                    'coursework_grade' => $courseData['coursework_grade'],
                    'final_grade' => $courseData['final_grade'],
                ]);
            }

            app(GradeCalculationService::class)->recalculateStudentGPA($student);
        }

        return $student;
    }
}
