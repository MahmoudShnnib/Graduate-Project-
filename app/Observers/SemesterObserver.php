<?php

namespace App\Observers;

use App\Enums\StudentStatus;
use App\Models\Semester;
use App\Models\Student;
use App\Services\WarningService;

class SemesterObserver
{
    public function __construct(
        private WarningService $warningService,
    ) {}

    public function created(Semester $semester): void
    {
        if ($semester->is_active) {
            $this->processInactiveStudents($semester);
        }
    }

    public function updated(Semester $semester): void
    {
        if (! $semester->wasChanged('is_active') || ! $semester->is_active) {
            return;
        }

        $this->processInactiveStudents($semester);
    }

    private function processInactiveStudents(Semester $semester): void
    {
        Student::where('status', StudentStatus::Inactive)
            ->each(fn (Student $student) => $this->warningService->processInactiveWarning($student, $semester));
    }
}
