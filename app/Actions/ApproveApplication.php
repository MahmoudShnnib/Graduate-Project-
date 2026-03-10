<?php

namespace App\Actions;

use App\Models\Application;
use App\Models\Student;

class ApproveApplication
{
    public function handle(Application $application): Application
    {
        if (Student::where('application_id', $application->id)->exists()) {
            throw new \Exception('تمت الموافقة على هذا الطلب بالفعل');
        }

        $application->update([
            'status' => 'accepted',
        ]);

        $semesterType = (int) $application->semester_type;

        Student::create([
            'application_id' => $application->id,
            'student_number' => Student::generateStudentNumber(
                (int) $application->specialization_id,
                $semesterType,
            ),
            'specialization_id' => $application->specialization_id,
            'admission_year' => now()->year,
            'semester_type' => $semesterType,
        ]);

        return $application;
    }
}
