<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentNumberService;
use App\Services\GradeCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

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

        // Sync Spatie role to match role column. Without this the HasPanelShield
        // trait auto-assigns 'panel_user' and Shield policy checks fail.
        $user->syncRoles(['student']);

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

        $activeSemester = Semester::active();

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
