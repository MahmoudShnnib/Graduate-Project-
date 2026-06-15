<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use Illuminate\Database\UniqueConstraintViolationException;

class EnrollmentNumberService
{
    /**
     * How many times to recompute-and-retry when a concurrent approval claims
     * the same sequential number. The collision window is a single MAX()+save,
     * so a handful of retries comfortably covers realistic concurrency.
     */
    private const MAX_ATTEMPTS = 5;

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

        return $this->assignNextSequential($student, $prefix, $student->specialization_id);
    }

    /**
     * Re-issue a new enrollment number when a student switches specialization.
     * Preserves the original year (YY) and session flag from the existing number
     * so only the spec code segment changes.
     */
    public function regenerateForSpecializationChange(Student $student, Specialization $newSpecialization): string
    {
        $currentNo = $student->enrollment_no;
        $yy = substr($currentNo, 0, 2);
        $sessionFlag = substr($currentNo, -3, 1);
        $newSpecCode = $newSpecialization->code;
        $prefix = "{$yy}{$newSpecCode}{$sessionFlag}";

        return $this->assignNextSequential($student, $prefix);
    }

    /**
     * Compute the next sequential enrollment number for $prefix and persist it
     * on $student. The MAX()+1 read and the save are not atomic, so two
     * concurrent approvals can compute the same number; the UNIQUE index on
     * enrollment_no rejects the loser, which we catch and retry (recomputing the
     * MAX, which now sees the winning row). A pessimistic lock is not an option
     * here — Postgres forbids FOR UPDATE alongside the MAX() aggregate.
     */
    private function assignNextSequential(Student $student, string $prefix, ?int $specializationId = null): string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $query = Student::withTrashed()
                ->whereNotNull('enrollment_no')
                ->where('enrollment_no', 'like', "{$prefix}%");

            if ($specializationId !== null) {
                $query->where('specialization_id', $specializationId);
            }

            $maxEnrollmentNo = $query->max('enrollment_no');
            $lastSeq = $maxEnrollmentNo ? (int) substr((string) $maxEnrollmentNo, strlen($prefix)) : 0;
            $seq = str_pad((string) ($lastSeq + 1), 2, '0', STR_PAD_LEFT);
            $enrollmentNo = "{$prefix}{$seq}";

            $student->enrollment_no = $enrollmentNo;

            try {
                $student->save();

                return $enrollmentNo;
            } catch (UniqueConstraintViolationException) {
                // A concurrent approval took this number; loop to recompute.
                continue;
            }
        }

        throw new \RuntimeException("تعذّر توليد رقم جامعي فريد للبادئة {$prefix} بعد ".self::MAX_ATTEMPTS.' محاولات.');
    }
}
