<?php

namespace App\Observers;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Models\Grade;
use App\Services\GradeCalculationService;

class GradeObserver
{
    public function __construct(
        private GradeCalculationService $gradeCalculationService,
    ) {}

    public function updated(Grade $grade): void
    {
        if (! $grade->wasChanged('status') || $grade->status !== GradeStatus::Approved) {
            return;
        }

        $enrollment = $grade->enrollment;

        if (! $enrollment || $enrollment->status !== EnrollmentStatus::Enrolled) {
            return;
        }

        $student = $enrollment->student;

        if (! $student) {
            return;
        }

        $this->gradeCalculationService->recalculateStudentGPA($student);
    }
}
