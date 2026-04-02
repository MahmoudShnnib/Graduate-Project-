<?php

namespace App\Actions;

use App\Models\Application;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ApproveApplication
{
    /**
     * @throws \Throwable
     */
    public function handle(Application $application): Application
    {
        //lockup query
        if (Student::where('application_id', $application->id)->exists()) {
            throw new \Exception('تمت الموافقة على هذا الطلب بالفعل');
        }

        DB::transaction(function() use ($application) {
            $application->update([
                'status' => 'accepted',
            ]);

            $semesterType = (int) $application->semester_type;

            //create
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
        });



        return $application;
    }
}
