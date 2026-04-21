<?php

namespace App\Exceptions;

use App\Models\Student;
use RuntimeException;

/**
 * Thrown when attempting to enroll a student who already has the maximum
 * number of active enrollments in the target semester.
 *
 * Callers should catch this specifically and surface an Arabic notification
 * to the operator (admin) — the cap is a domain invariant, not a bug.
 */
class EnrollmentCapReachedException extends RuntimeException
{
    public function __construct(
        public readonly Student $student,
        public readonly int $cap,
        public readonly int $currentCount,
    ) {
        parent::__construct(
            sprintf(
                'Student %d already has %d active enrollments in this semester (cap: %d).',
                $student->id,
                $currentCount,
                $cap,
            )
        );
    }
}
