<?php

namespace App\Exceptions;

use App\Models\Student;
use RuntimeException;

final class StudentNotActiveException extends RuntimeException
{
    public function __construct(public readonly Student $student)
    {
        parent::__construct(sprintf(
            'Student %d is not active (status=%s).',
            $student->id,
            $student->status?->value ?? 'null',
        ));
    }
}
