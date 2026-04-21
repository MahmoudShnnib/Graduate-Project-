<?php

namespace App\Observers;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Student;

class StudentObserver
{
    public function __construct(
        private DropStudentFromOffering $drop,
    ) {}

    public function updated(Student $student): void
    {
        if (! $student->wasChanged('status')) {
            return;
        }

        if ($student->status === StudentStatus::Active) {
            return;
        }

        $this->cascadeWithdraw($student);
    }

    public function deleting(Student $student): void
    {
        $this->cascadeWithdraw($student);
    }

    private function cascadeWithdraw(Student $student): void
    {
        $offerings = $student->activeCourseOfferings()->get();

        foreach ($offerings as $offering) {
            ($this->drop)($student, $offering, EnrollmentStatus::Withdrawn);
        }
    }
}
