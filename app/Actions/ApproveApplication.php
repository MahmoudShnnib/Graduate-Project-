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
            ]);

            $activeSemester = Semester::where('is_active', true)->first();

            if ($activeSemester) {
                $this->enrollmentNumberService->generate($student, $activeSemester);
            }
        });

        return $application;
    }
}
