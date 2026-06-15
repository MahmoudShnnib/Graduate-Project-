<?php

namespace App\Observers;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Semester;
use App\Models\Student;
use App\Services\GradeCalculationService;
use App\Services\WarningService;
use Illuminate\Support\Facades\DB;

class StudentObserver
{
    /**
     * Statuses that "flush" the current semester: withdraw only the active
     * semester's enrollments and recompute the GPA (dropping those courses),
     * while leaving completed prior-semester work intact. Other non-active
     * statuses (Graduated/Transferred/Dismissed) keep the withdraw-all +
     * frozen-GPA behavior so a final GPA is preserved.
     */
    private const FLUSH_CURRENT_SEMESTER_STATUSES = [
        StudentStatus::OnHold,
        StudentStatus::Inactive,
    ];

    public function __construct(
        private DropStudentFromOffering $drop,
        private WarningService $warningService,
        private GradeCalculationService $gradeCalculationService,
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

        if (in_array($student->status, self::FLUSH_CURRENT_SEMESTER_STATUSES, true)) {
            $this->flushCurrentSemester($student);

            return;
        }

        $this->cascadeWithdraw($student);
    }

    public function deleting(Student $student): void
    {
        $this->cascadeWithdraw($student);
    }

    /**
     * Withdraw every active enrollment the student has, across all semesters.
     * Used for terminal states (Graduated/Transferred/Dismissed) and deletion;
     * the GPA stays frozen at its current value (no recalc).
     */
    private function cascadeWithdraw(Student $student): void
    {
        $offerings = $student->activeCourseOfferings()->get();

        // Wrap the multi-row withdraw so a mid-loop failure can't leave the
        // student half-withdrawn (some offerings dropped, others still active).
        DB::transaction(function () use ($student, $offerings): void {
            foreach ($offerings as $offering) {
                ($this->drop)($student, $offering, EnrollmentStatus::Withdrawn);
            }
        });
    }

    /**
     * Withdraw only the current (active-semester) enrollments and force a GPA
     * recalculation so those courses drop out of the GPA while completed prior
     * work is preserved. The withdrawn courses are flushed permanently — they
     * never count again, even after reactivation.
     */
    private function flushCurrentSemester(Student $student): void
    {
        $activeSemester = Semester::active();

        // Atomic: the current-semester withdrawals and the GPA recompute that
        // depends on them must commit together or not at all.
        DB::transaction(function () use ($student, $activeSemester): void {
            if ($activeSemester) {
                $offerings = $student->activeCourseOfferings()
                    ->where('course_offerings.semester_id', $activeSemester->id)
                    ->get();

                foreach ($offerings as $offering) {
                    ($this->drop)($student, $offering, EnrollmentStatus::Withdrawn);
                }
            }

            // Recompute on a FRESH instance. Saving $student here would re-fire this
            // observer with `status` still dirty (its original isn't synced until
            // the outer save finishes), so `wasChanged('status')` would stay true
            // and recurse infinitely. The fresh instance has `status` synced, so
            // only `gpa` is dirty on save.
            $fresh = $student->fresh();

            if ($fresh) {
                $this->gradeCalculationService->recalculateStudentGPA($fresh, force: true);
                $student->setAttribute('gpa', $fresh->gpa);
            }
        });
    }
}
