<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\Student;

class EnrollmentNumberService
{
    public function generate(Student $student, Semester $semester): string
    {
        $yy = substr((string) $semester->year, -2);
        $specCode = $student->specialization->code;

        $semesterCode = match ($semester->season->value) {
            'autumn' => 1,
            'spring' => 2,
            'summer' => 3,
        };

        $count = Student::where('specialization_id', $student->specialization_id)
            ->whereNotNull('enrollment_no')
            ->where('enrollment_no', 'like', "{$yy}-{$specCode}-{$semesterCode}-%")
            ->count();

        $seq = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        $enrollmentNo = "{$yy}-{$specCode}-{$semesterCode}-{$seq}";

        $student->enrollment_no = $enrollmentNo;
        $student->save();

        return $enrollmentNo;
    }
}
