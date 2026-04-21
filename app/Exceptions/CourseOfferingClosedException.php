<?php

namespace App\Exceptions;

use App\Models\CourseOffering;
use RuntimeException;

/**
 * Thrown when attempting to enroll a student into a course offering whose
 * is_open flag is false.
 *
 * Domain invariant: a closed offering does not accept new enrollments, and
 * any existing enrollments are withdrawn at the moment of closure (see
 * {@see \App\Observers\CourseOfferingObserver}). This exception is the
 * write-path half of that invariant — the cascade handles the transition.
 */
class CourseOfferingClosedException extends RuntimeException
{
    public function __construct(
        public readonly CourseOffering $offering,
    ) {
        parent::__construct(
            sprintf(
                'Course offering %d is closed — cannot enroll students.',
                $offering->id,
            )
        );
    }
}
