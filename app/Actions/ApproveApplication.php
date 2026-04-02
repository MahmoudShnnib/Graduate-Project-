<?php

namespace App\Actions;

use App\Models\Application;
use App\Models\Student;

class ApproveApplication
{
    public function handle(Application $application): Application
    {

        if (
            Student::where('application_id', $application->id)->exists()
            && $application->status != 'accepted'
        ) {
            $application->update([
                'status' => 'accepted',
            ]);
            throw new \Exception('تمت الموافقة على هذا الطلب بالفعل');
        }

        Student::create([
            'application_id' => $application->id,
            'student_number' => Student::generateStudentNumber(
                (int) $application->specialization_id,
                (int) $application->semester_type,
            ),
            'specialization_id' => $application->specialization_id,
            'admission_year' => now()->year,
            'semester_type' => $application->semester_type,
        ]);

        return $application;
    }
}
