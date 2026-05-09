<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Exceptions\CourseOfferingClosedException;
use App\Exceptions\CourseOutsideSpecializationException;
use App\Exceptions\EnrollmentCapReachedException;
use App\Exceptions\StudentNotActiveException;
use App\Exceptions\StudentSuspendedInSemesterException;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class EnrollStudentInOffering
{
    /**
     * Maximum number of active enrollments a student can hold per semester.
     * Hard cap — enforced here so every enrollment path (staff manual,
     * survey-batch, add/drop approval) respects it without duplication.
     */
    public const int MAX_ENROLLMENTS_PER_SEMESTER = 4;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @throws StudentNotActiveException when the student's global status is
     *                                   not Active (Inactive / Withdrawn / Graduated).
     * @throws CourseOfferingClosedException when the offering's is_open flag is false.
     * @throws StudentSuspendedInSemesterException when the student has an
     *                                             approved suspension request for the target semester.
     * @throws CourseOutsideSpecializationException when the offering's course
     *                                              is not part of the student's specialization.
     * @throws EnrollmentCapReachedException when the student already has
     *                                       {@see self::MAX_ENROLLMENTS_PER_SEMESTER} active enrollments
     *                                       in the target semester and this call would add a new one.
     */
    public function __invoke(Student $student, CourseOffering $offering): StudentEnrollment
    {
        return DB::transaction(function () use ($student, $offering): StudentEnrollment {
            // Idempotency: re-running enroll on an already-active enrollment
            // should not count toward any guard. Only run invariants when the
            // student would GAIN a new active row.
            $isAlreadyActive = StudentEnrollment::where('student_id', $student->id)
                ->where('course_offering_id', $offering->id)
                ->active()
                ->exists();

            if (! $isAlreadyActive) {
                // Global status check: only Active students can be enrolled.
                // First check because it's the cheapest and independent of
                // the offering.
                if ($student->status !== StudentStatus::Active) {
                    throw new StudentNotActiveException($student);
                }

                // Closed-offering check (cheapest — property on the already-loaded
                // offering). A closed offering rejects new enrollments; existing
                // enrollments are handled by CourseOfferingObserver's cascade
                // when is_open transitions true → false.
                if (! $offering->is_open) {
                    throw new CourseOfferingClosedException($offering);
                }

                // Suspension check: student cannot be enrolled in a semester
                // where they have an approved suspension request.
                $semester = Semester::find($offering->semester_id);

                if ($semester && $student->isSuspendedInSemester($semester)) {
                    throw new StudentSuspendedInSemesterException(
                        student: $student,
                        semester: $semester,
                    );
                }

                // Specialization check: the offering's course must belong to
                // the student's specialization via course_specialization pivot.
                $isInSpecialization = $offering->course
                    ->specializations()
                    ->where('specializations.id', $student->specialization_id)
                    ->exists();

                if (! $isInSpecialization) {
                    throw new CourseOutsideSpecializationException(
                        student: $student,
                        offering: $offering,
                    );
                }

                $activeCountInSemester = StudentEnrollment::where('student_id', $student->id)
                    ->active()
                    ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $offering->semester_id))
                    ->count();

                if ($activeCountInSemester >= self::MAX_ENROLLMENTS_PER_SEMESTER) {
                    throw new EnrollmentCapReachedException(
                        student: $student,
                        cap: self::MAX_ENROLLMENTS_PER_SEMESTER,
                        currentCount: $activeCountInSemester,
                    );
                }
            }

            $enrollment = StudentEnrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_offering_id' => $offering->id,
                ],
                ['status' => EnrollmentStatus::Enrolled],
            );

            CourseSurvey::query()
                ->where('student_id', $student->id)
                ->where('semester_id', $offering->semester_id)
                ->where('course_id', $offering->course_id)
                ->update(['is_processed' => true]);

            $this->audit->log('student_enrolled', $enrollment, newValues: ['course_offering_id' => $offering->id]);

            return $enrollment;
        });
    }
}
