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

        $maxEnrollmentNo = Student::withTrashed()
            ->where('specialization_id', $student->specialization_id)
            ->whereNotNull('enrollment_no')
            ->where('enrollment_no', 'like', "{$prefix}%")
            ->max('enrollment_no');

        $lastSeq = $maxEnrollmentNo ? (int) substr((string) $maxEnrollmentNo, strlen($prefix)) : 0;

        $seq = str_pad($lastSeq + 1, 2, '0', STR_PAD_LEFT);

        $enrollmentNo = "{$prefix}{$seq}";

        $student->enrollment_no = $enrollmentNo;
        $student->save();

        return $enrollmentNo;
    }
}
