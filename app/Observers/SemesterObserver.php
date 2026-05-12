<?php

namespace App\Observers;

use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
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
            $this->archivePeersOf($semester);
            $this->resetInactiveStudentsToActive();
            $this->releaseOnHoldStudentsForSemester($semester);
        }
    }

    public function updated(Semester $semester): void
    {
        if ($semester->wasChanged('is_active') && $semester->is_active) {
            $this->archivePeersOf($semester);
            $this->resetInactiveStudentsToActive();
            $this->releaseOnHoldStudentsForSemester($semester);
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
     * Invariant: at any moment exactly one semester is "active"
     * (`is_active=true AND archived_at IS NULL`). When this semester just
     * became active, archive every other non-archived semester so the
     * invariant holds. The full archive cascade runs (survey close +
     * offering close) on each, preserving historical enrollments + grades.
     */
    private function archivePeersOf(Semester $semester): void
    {
        Semester::query()
            ->whereNull('archived_at')
            ->where('id', '!=', $semester->id)
            ->get()
            ->each(fn (Semester $peer) => $peer->archiveCascade());
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
     * Suspensions are per-semester. When a new semester becomes active, any
     * OnHold student whose approved suspension does NOT cover that semester
     * should be released back to Active — their suspension was for a previous
     * semester only. Students whose approved suspension covers the new active
     * semester stay OnHold. Uses updateQuietly to match the Inactive-reset
     * pattern (no observer cascades, no bulk audit churn).
     */
    private function releaseOnHoldStudentsForSemester(Semester $activeSemester): void
    {
        Student::where('status', StudentStatus::OnHold)
            ->whereDoesntHave('suspensionRequests', function ($q) use ($activeSemester) {
                $q->where('semester_id', $activeSemester->id)
                    ->where('status', SuspensionStatus::Approved);
            })
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
