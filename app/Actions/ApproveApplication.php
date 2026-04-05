<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Student;
use App\Services\EnrollmentNumberService;
use Illuminate\Support\Facades\DB;

class ApproveApplication
{
    public function __construct(
        private EnrollmentNumberService $enrollmentNumberService,
    ) {}

    public function handle(Application $application): Application
    {
        if (Student::where('user_id', $application->user_id)->exists()) {
            throw new \Exception('تمت الموافقة على هذا الطلب بالفعل');
        }

        DB::transaction(function () use ($application) {
            $application->update([
                'status' => ApplicationStatus::Accepted,
            ]);

            $user = $application->user;

            $student = Student::create([
                'user_id' => $user->id,
                'specialization_id' => $user->specialization_id,
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

        return $application;
    }
}
