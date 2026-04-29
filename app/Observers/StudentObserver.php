<?php

namespace App\Observers;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Services\WarningService;

class StudentObserver
{
    public function __construct(
        private DropStudentFromOffering $drop,
        private WarningService $warningService,
    ) {}

    public function updated(Student $student): void
    {
        if (! $student->wasChanged('status')) {
            return;
        }

        $original = $student->getOriginal('status');
        $previousStatus = $original instanceof StudentStatus
            ? $original
            : StudentStatus::tryFrom((string) $original);

        if ($student->status === StudentStatus::Inactive) {
            $this->warningService->processInactiveWarning($student);
        }

        if ($previousStatus === StudentStatus::Inactive && $student->status !== StudentStatus::Inactive) {
            $this->warningService->clearInactiveWarnings($student);
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
