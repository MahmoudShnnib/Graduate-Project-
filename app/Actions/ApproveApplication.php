<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentNumberService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApproveApplication
{
    public function __construct(
        private EnrollmentNumberService $enrollmentNumberService,
        private NotificationService $notificationService,
    ) {}

    public function handle(Application $application): Application
    {
        if ($application->status === ApplicationStatus::Accepted) {
            throw new \Exception('تمت الموافقة على هذا الطلب بالفعل');
        }

        $plainPassword = Str::random(10);

        DB::transaction(function () use ($application, $plainPassword) {
            $application->update([
                'status' => ApplicationStatus::Accepted,
            ]);

            // Create User account from application data
            $user = User::create([
                'name' => $application->name,
                'email' => $application->email,
                'phone' => $application->phone,
                'password' => Hash::make($plainPassword),
                'role' => 'student',
                'specialization_id' => $application->specialization_id,
                'is_active' => true,
            ]);

            $application->update(['user_id' => $user->id]);

            $student = Student::create([
                'user_id' => $user->id,
                'specialization_id' => $application->specialization_id,
                'last_name' => $application->last_name,
                'mother_name' => $application->mother_name,
                'birth_date' => $application->birth_date,
                'birth_place' => $application->birth_place,
                'gender' => $application->gender,
                'marital_status' => $application->marital_status,
                'nationality' => $application->nationality,
                'national_id' => $application->national_id,
                'card_number' => $application->card_number,
                'residence' => $application->residence,
                'permanent_address' => $application->permanent_address,
                'profession' => $application->profession,
                'workplace' => $application->workplace,
                'study_type' => $application->study_type,
                'delegation_number' => $application->delegation_number,
                'delegation_entity' => $application->delegation_entity,
                'is_transfer' => $application->is_transfer,
            ]);

            $activeSemester = Semester::where('is_active', true)->first();

            if ($activeSemester) {
                $this->enrollmentNumberService->generate($student, $activeSemester, (bool) $application->is_transfer);
            }
        });

        $application->refresh();
        $student = $application->user?->student;

        if ($student) {
            $this->notificationService->notifyWelcomeStudent($student, $plainPassword);
        }

        return $application;
    }
}
