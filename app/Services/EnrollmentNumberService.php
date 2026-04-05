<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\Student;

class EnrollmentNumberService
{
    public function generate(Student $student, Semester $semester, bool $isTransfer = false): string
    {
        $yy = substr((string) $semester->year, -2);
        $specCode = $student->specialization->code;

        $s = match (true) {
            ! $isTransfer && $semester->season->value === 'autumn' => 1,
            ! $isTransfer && $semester->season->value === 'spring' => 2,
            $isTransfer && $semester->season->value === 'autumn' => 3,
            $isTransfer && $semester->season->value === 'spring' => 4,
            default => 1,
        };

        $prefix = "{$yy}{$specCode}{$s}";

        $count = Student::where('specialization_id', $student->specialization_id)
            ->whereNotNull('enrollment_no')
            ->where('enrollment_no', 'like', "{$prefix}%")
            ->count();

        $seq = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        $enrollmentNo = "{$prefix}{$seq}";

        $student->enrollment_no = $enrollmentNo;
        $student->save();

        return $enrollmentNo;
    }
}
