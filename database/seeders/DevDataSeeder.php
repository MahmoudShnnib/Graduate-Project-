<?php

namespace Database\Seeders;

use App\Enums\AddDropType;
use App\Enums\DiscussionStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Enums\ThesisStatus;
use App\Models\AddDropRequest;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\DiscussionRequest;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\ThesisRegistration;
use App\Models\User;
use App\Services\GradeCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dedicated dev/test data seeder. NEVER wire this into DatabaseSeeder::run().
 * Run explicitly: php artisan db:seed --class=DevDataSeeder
 *
 * See _brain/project-context/dev-test-data.md for the rationale.
 */
class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearanceReadyStudent();
        $this->gpaUnenrollScenario();
        $this->suspensionFlushScenario();
    }

    /**
     * A student with one completed PRIOR-semester course (A = 4.0) and two
     * CURRENT active-semester courses (A = 4.0, C = 2.0). Initial GPA = 3.33.
     * Suspending (قيده موقوف) flushes the two current courses and recalcs to
     * 4.00 — proving current courses leave the GPA while prior work stays.
     * Idempotent: re-running resets the current enrollments to enrolled.
     */
    private function suspensionFlushScenario(): void
    {
        $activeSemester = Semester::query()->where('is_active', true)->first();

        if (! $activeSemester) {
            return;
        }

        $priorSemester = Semester::firstOrCreate(
            ['name' => 'فصل سابق (تجريبي)'],
            [
                'year' => 2025,
                'season' => \App\Enums\Season::Spring,
                'is_active' => false,
                'start_date' => now()->subMonths(10),
                'end_date' => now()->subMonths(6),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'suspend.test@dev.ly'],
            [
                'name' => 'طالب اختبار الإيقاف (تجريبي)',
                'password' => Hash::make('password'),
                'phone' => '0915550003',
                'role' => 'student',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (class_exists(\Spatie\Permission\Models\Role::class) && ! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        $specializationId = Specialization::query()->value('id');

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'specialization_id' => $specializationId,
                'status' => StudentStatus::Active,
                'gpa' => 0,
            ]
        );

        // Reactivate on re-run so the scenario can be replayed.
        if ($student->status !== StudentStatus::Active) {
            $student->update(['status' => StudentStatus::Active]);
        }

        $blueprint = [
            ['code' => 'SUSP-P', 'name' => 'مادة سابقة (مكتملة)', 'semester' => $priorSemester, 'coursework' => 36, 'final' => 54],   // 90 → A → 4.0
            ['code' => 'SUSP-C1', 'name' => 'مادة حالية أ', 'semester' => $activeSemester, 'coursework' => 36, 'final' => 54],          // 90 → A → 4.0
            ['code' => 'SUSP-C2', 'name' => 'مادة حالية ب', 'semester' => $activeSemester, 'coursework' => 30, 'final' => 30],          // 60 → C → 2.0
        ];

        foreach ($blueprint as $row) {
            $course = Course::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'credits' => 3, 'is_active' => true]
            );
            $course->specializations()->syncWithoutDetaching([$specializationId => ['type' => 'general']]);

            $offering = CourseOffering::firstOrCreate(
                ['course_id' => $course->id, 'semester_id' => $row['semester']->id],
                ['is_open' => true]
            );

            $enrollment = StudentEnrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_offering_id' => $offering->id],
                ['status' => EnrollmentStatus::Enrolled]
            );
            $enrollment->forceFill(['status' => EnrollmentStatus::Enrolled])->save();

            Grade::updateOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'coursework_grade' => $row['coursework'],
                    'final_grade' => $row['final'],
                    'status' => GradeStatus::Approved,
                    'midterm_published_at' => now(),
                    'published_at' => now(),
                ]
            );
        }

        app(GradeCalculationService::class)->recalculateStudentGPA($student->fresh(), force: true);
    }

    /**
     * A student actively enrolled in three OPEN, graded courses in the active
     * semester, set up so the GPA reacts visibly to removals:
     *   - GPA-A: A (4.0) → removed via the admin enroll page
     *   - GPA-B: A (4.0) → removed via approving a pending DROP add/drop request
     *   - GPA-C: D (1.0) → anchor, never touched
     * Initial GPA 3.00 → after enroll-page unenroll 2.50 → after add/drop drop 1.00.
     *
     * Idempotent: re-running resets enrollments to enrolled and the add/drop
     * request back to pending so the scenario can be replayed.
     */
    private function gpaUnenrollScenario(): void
    {
        $semester = Semester::query()->where('is_active', true)->first();

        if (! $semester) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'gpa.test@dev.ly'],
            [
                'name' => 'طالب اختبار المعدل (تجريبي)',
                'password' => Hash::make('password'),
                'phone' => '0915550002',
                'role' => 'student',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (class_exists(\Spatie\Permission\Models\Role::class) && ! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        $specializationId = Specialization::query()->value('id');

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'specialization_id' => $specializationId,
                'status' => StudentStatus::Active,
                'gpa' => 0,
            ]
        );

        $blueprint = [
            ['code' => 'GPA-A', 'name' => 'مادة اختبار المعدل أ', 'coursework' => 36, 'final' => 54],
            ['code' => 'GPA-B', 'name' => 'مادة اختبار المعدل ب', 'coursework' => 36, 'final' => 54],
            ['code' => 'GPA-C', 'name' => 'مادة اختبار المعدل ج', 'coursework' => 20, 'final' => 30],
        ];

        $offerings = [];

        foreach ($blueprint as $row) {
            $course = Course::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'credits' => 3, 'is_active' => true]
            );
            $course->specializations()->syncWithoutDetaching([$specializationId => ['type' => 'general']]);

            $offering = CourseOffering::firstOrCreate(
                ['course_id' => $course->id, 'semester_id' => $semester->id],
                ['is_open' => true]
            );
            $offering->forceFill(['is_open' => true])->save();

            $enrollment = StudentEnrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_offering_id' => $offering->id],
                ['status' => EnrollmentStatus::Enrolled]
            );
            $enrollment->forceFill(['status' => EnrollmentStatus::Enrolled])->save();

            Grade::updateOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'coursework_grade' => $row['coursework'],
                    'final_grade' => $row['final'],
                    'status' => GradeStatus::Approved,
                    'midterm_published_at' => now(),
                    'published_at' => now(),
                ]
            );

            $offerings[$row['code']] = $offering;
        }

        AddDropRequest::updateOrCreate(
            [
                'student_id' => $student->id,
                'course_offering_id' => $offerings['GPA-B']->id,
                'type' => AddDropType::Drop,
            ],
            ['status' => 'pending', 'decided_by' => null, 'decided_at' => null]
        );

        app(GradeCalculationService::class)->recalculateStudentGPA($student);
    }

    /**
     * A student parked exactly at "can request clearance":
     * active student → approved thesis → completed discussion request,
     * which is the only gate the /portal/clearance-page enforces.
     */
    private function clearanceReadyStudent(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'clearance.student@dev.ly'],
            [
                'name' => 'طالب الاستماحة (تجريبي)',
                'password' => Hash::make('password'),
                'phone' => '0915550001',
                'role' => 'student',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (class_exists(\Spatie\Permission\Models\Role::class) && ! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'specialization_id' => Specialization::query()->value('id'),
                'status' => StudentStatus::Active,
                'gpa' => 3.60,
            ]
        );

        $thesis = ThesisRegistration::firstOrCreate(
            ['student_id' => $student->id],
            [
                'title' => 'نظام إدارة القبول والتسجيل للدراسات العليا',
                'title_en' => 'A Management System for Graduate Admissions and Enrollment',
                'status' => ThesisStatus::Approved,
                'decided_at' => now()->subMonths(6),
            ]
        );

        DiscussionRequest::firstOrCreate(
            ['student_id' => $student->id, 'thesis_registration_id' => $thesis->id],
            [
                'status' => DiscussionStatus::Completed,
                'discussion_date' => now()->subMonth()->toDateString(),
                'discussed_at' => now()->subMonth(),
                'decided_at' => now()->subMonth(),
                'completed_at' => now()->subWeeks(2),
            ]
        );
    }
}
