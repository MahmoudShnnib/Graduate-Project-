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
            $this->resetInactiveStudentsToActive();
        }
    }

    public function updated(Semester $semester): void
    {
        if ($semester->wasChanged('is_active') && $semester->is_active) {
            $this->resetInactiveStudentsToActive();
        }

        if ($semester->wasChanged('survey_open')) {
            $wasOpen = (bool) $semester->getOriginal('survey_open');
            $isNowOpen = (bool) $semester->survey_open;

            if ($wasOpen && ! $isNowOpen) {
                $this->processNonSubmittingStudents($semester);
            }
        }
    }

    /**
     * When a new semester starts, reset all Inactive students to Active so they
     * get a fresh chance at the survey. Uses updateQuietly to bypass
     * StudentObserver — their consecutive_inactive warnings must persist and
     * accumulate across semesters. Only an admin manually changing the status
     * clears warnings.
     */
    private function resetInactiveStudentsToActive(): void
    {
        Student::where('status', StudentStatus::Inactive)
            ->each(fn (Student $student) => $student->updateQuietly(['status' => StudentStatus::Active]));
    }

    /**
     * When the survey closes (survey_open: true → false), any Active student
     * who submitted no survey entries for this semester is marked as Inactive.
     * Uses a normal update so StudentObserver fires: it creates the
     * consecutive_inactive warning and cascades withdrawal of active enrollments.
     */
    private function processNonSubmittingStudents(Semester $semester): void
    {
        Student::where('status', StudentStatus::Active)
            ->whereDoesntHave('courseSurveys', fn ($q) => $q->where('semester_id', $semester->id))
            ->each(fn (Student $student) => $student->update(['status' => StudentStatus::Inactive]));
    }
}
