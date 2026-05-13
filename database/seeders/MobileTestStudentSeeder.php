<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\AppNotification;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentWarning;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the test student described in mobile/TEST_CREDENTIALS.md plus
 * everything the mobile app needs to exercise every screen.
 *
 * Idempotent — safe to re-run.
 */
class MobileTestStudentSeeder extends Seeder
{
    public function run(): void
    {
        $cs = Specialization::where('code', 2)->firstOrFail();
        if ($cs->name !== 'علوم الحاسوب') {
            $cs->update(['name' => 'علوم الحاسوب']);
        }

        $semesters = $this->ensureSemesters();
        [$activeSemester, $previousSemester, $oldestSemester] = [
            $semesters['active'],
            $semesters['previous'],
            $semesters['oldest'],
        ];

        $student = $this->createOrUpdateStudent(
            cs: $cs,
            enrollmentNo: '26CS101',
            email: 'test.student@example.ly',
            name: 'محمود علي الترهوني',
            nameEn: 'Mahmoud Ali Alterhouni',
            phone: '0910000000',
            status: StudentStatus::Active,
        );

        $this->createOrUpdateStudent(
            cs: $cs,
            enrollmentNo: '26CS102',
            email: 'on.hold.student@example.ly',
            name: 'سامي محمد الزروق',
            nameEn: 'Sami Mohammed Alzarroug',
            phone: '0910000001',
            status: StudentStatus::OnHold,
        );

        $facultyUser = User::firstOrCreate(
            ['email' => 'cs.faculty@example.ly'],
            [
                'name' => 'د. عبدالله الورفلي',
                'name_en' => 'Dr. Abdullah Alwarfalli',
                'password' => Hash::make('password'),
                'role' => 'faculty',
                'specialization_id' => $cs->id,
                'is_active' => true,
                'phone' => '0910000010',
                'email_verified_at' => now(),
            ]
        );

        $courseCodes = ['EE601', 'EE602', 'EE603', 'EE604', 'CP611', 'CP612', 'CP614', 'CP619'];
        $courses = Course::whereIn('code', $courseCodes)->get()->keyBy('code');

        $this->seedSemesterEnrollments(
            student: $student,
            semester: $previousSemester,
            faculty: $facultyUser,
            data: [
                ['code' => 'EE601', 'coursework' => 55, 'final' => 32, 'fully_published' => true],  // 87 → A
                ['code' => 'EE602', 'coursework' => 48, 'final' => 28, 'fully_published' => true],  // 76 → B+
                ['code' => 'EE603', 'coursework' => 42, 'final' => 25, 'fully_published' => true],  // 67 → C+
                ['code' => 'CP611', 'coursework' => 30, 'final' => 12, 'fully_published' => true],  // 42 → F
            ],
            coursesByCode: $courses,
        );

        $this->seedSemesterEnrollments(
            student: $student,
            semester: $activeSemester,
            faculty: $facultyUser,
            data: [
                ['code' => 'EE604', 'coursework' => 50, 'final' => null, 'midterm_only' => true],
                ['code' => 'CP612', 'coursework' => 45, 'final' => null, 'midterm_only' => true],
                ['code' => 'CP614', 'coursework' => null, 'final' => null, 'unpublished' => true],
                ['code' => 'CP619', 'coursework' => null, 'final' => null, 'unpublished' => true, 'status' => EnrollmentStatus::Dropped],
            ],
            coursesByCode: $courses,
        );

        $student->refresh();
        $student->update([
            'gpa' => $this->recomputeGpa($student),
            'warnings_count' => 1,
        ]);

        $cp611 = $courses->get('CP611');
        StudentWarning::firstOrCreate(
            [
                'student_id' => $student->id,
                'type' => WarningType::CourseFailure,
                'course_id' => $cp611?->id,
                'semester_id' => $previousSemester->id,
            ],
            [
                'is_active' => true,
                'has_exceptional_chance' => true,
            ]
        );

        AppNotification::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'تم نشر درجاتك للفصل السابق'],
            [
                'type' => 'info',
                'body' => 'يمكنك الآن مراجعة درجاتك المعتمدة من شاشة "الدرجات".',
                'created_at' => now()->subDays(3),
            ]
        );

        AppNotification::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'تم فتح نافذة استطلاع المقررات'],
            [
                'type' => 'warning',
                'body' => 'يجب تقديم استطلاع المقررات قبل إغلاق النافذة لتفادي تحويل الحساب إلى منقطع.',
                'created_at' => now()->subDay(),
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 26CS103 — rich showcase student. High GPA, full 3-semester
        // transcript, currently studying, one historical F → active warning.
        // Use this account to verify every screen end-to-end.
        // ─────────────────────────────────────────────────────────────────
        $top = $this->createOrUpdateStudent(
            cs: $cs,
            enrollmentNo: '26CS103',
            email: 'top.student@example.ly',
            name: 'نور الدين البكوش',
            nameEn: 'Noureddin Albakkoush',
            phone: '0910000003',
            status: StudentStatus::Active,
        );

        $topCourseCodes = ['EE601', 'EE602', 'EE603', 'EE604', 'CP611', 'CP612', 'CP614', 'CP619', 'CP615', 'CP616', 'CP617', 'CM614'];
        $topCourses = Course::whereIn('code', $topCourseCodes)->get()->keyBy('code');

        // Oldest semester — solid start, but one F (the warning trigger).
        $this->seedSemesterEnrollments(
            student: $top,
            semester: $oldestSemester,
            faculty: $facultyUser,
            data: [
                ['code' => 'EE601', 'coursework' => 58, 'final' => 38, 'fully_published' => true], // 96 → A+
                ['code' => 'EE602', 'coursework' => 56, 'final' => 36, 'fully_published' => true], // 92 → A
                ['code' => 'EE603', 'coursework' => 52, 'final' => 34, 'fully_published' => true], // 86 → A-
                ['code' => 'EE604', 'coursework' => 28, 'final' => 14, 'fully_published' => true], // 42 → F
            ],
            coursesByCode: $topCourses,
        );

        // Previous semester — strong recovery, all passed.
        $this->seedSemesterEnrollments(
            student: $top,
            semester: $previousSemester,
            faculty: $facultyUser,
            data: [
                ['code' => 'CP611', 'coursework' => 55, 'final' => 35, 'fully_published' => true], // 90 → A
                ['code' => 'CP612', 'coursework' => 52, 'final' => 33, 'fully_published' => true], // 85 → A-
                ['code' => 'CP614', 'coursework' => 50, 'final' => 32, 'fully_published' => true], // 82 → B+
                ['code' => 'CP619', 'coursework' => 57, 'final' => 38, 'fully_published' => true], // 95 → A+
            ],
            coursesByCode: $topCourses,
        );

        // Active semester — currently studying. Mix of midterm-only and unpublished.
        $this->seedSemesterEnrollments(
            student: $top,
            semester: $activeSemester,
            faculty: $facultyUser,
            data: [
                ['code' => 'CP615', 'coursework' => 48, 'final' => null, 'midterm_only' => true],
                ['code' => 'CP616', 'coursework' => 52, 'final' => null, 'midterm_only' => true],
                ['code' => 'CP617', 'coursework' => null, 'final' => null, 'unpublished' => true],
                ['code' => 'CM614', 'coursework' => null, 'final' => null, 'unpublished' => true],
            ],
            coursesByCode: $topCourses,
        );

        $top->refresh();
        $top->update([
            'gpa' => $this->recomputeGpa($top),
            'warnings_count' => 1,
        ]);

        // Active warning from the historical F.
        $ee604 = $topCourses->get('EE604');
        StudentWarning::firstOrCreate(
            [
                'student_id' => $top->id,
                'type' => WarningType::CourseFailure,
                'course_id' => $ee604?->id,
                'semester_id' => $oldestSemester->id,
            ],
            [
                'is_active' => true,
                'has_exceptional_chance' => true,
            ]
        );

        AppNotification::firstOrCreate(
            ['student_id' => $top->id, 'title' => 'تهنئة على معدلك المرتفع'],
            [
                'type' => 'info',
                'body' => 'حافظ على هذا المستوى — استعراض درجاتك متاح من شاشة "الدرجات".',
                'created_at' => now()->subDays(2),
            ]
        );

        AppNotification::firstOrCreate(
            ['student_id' => $top->id, 'title' => 'تذكير: استطلاع المقررات للفصل القادم'],
            [
                'type' => 'warning',
                'body' => 'لا تنسَ تقديم استطلاع المقررات قبل إغلاق النافذة.',
                'created_at' => now()->subHours(8),
            ]
        );
    }

    /** @return array{active: Semester, previous: Semester, oldest: Semester} */
    private function ensureSemesters(): array
    {
        Semester::query()->update(['is_active' => false, 'survey_open' => false]);

        $oldest = Semester::firstOrCreate(
            ['name' => 'الفصل الدراسي الأول 2025'],
            [
                'year' => 2025,
                'season' => 'autumn',
                'start_date' => '2024-09-01',
                'end_date' => '2025-01-15',
                'add_drop_start' => '2024-09-01',
                'add_drop_end' => '2024-09-15',
                'is_active' => false,
                'survey_open' => false,
            ]
        );
        $oldest->update(['is_active' => false, 'survey_open' => false]);

        $previous = Semester::firstOrCreate(
            ['name' => 'الفصل الدراسي الثاني 2025'],
            [
                'year' => 2025,
                'season' => 'spring',
                'start_date' => '2025-02-01',
                'end_date' => '2025-06-15',
                'add_drop_start' => '2025-02-01',
                'add_drop_end' => '2025-02-15',
                'is_active' => false,
                'survey_open' => false,
            ]
        );
        $previous->update(['is_active' => false, 'survey_open' => false]);

        $active = Semester::firstOrCreate(
            ['name' => 'الفصل الدراسي الأول 2026'],
            [
                'year' => 2026,
                'season' => 'autumn',
                'start_date' => now()->subWeeks(3)->toDateString(),
                'end_date' => now()->addMonths(4)->toDateString(),
                'add_drop_start' => now()->subWeeks(2)->toDateString(),
                'add_drop_end' => now()->addWeek()->toDateString(),
                'is_active' => true,
                'survey_open' => true,
            ]
        );
        $active->update([
            'is_active' => true,
            'survey_open' => true,
            'add_drop_start' => now()->subWeeks(2)->toDateString(),
            'add_drop_end' => now()->addWeek()->toDateString(),
        ]);

        return [
            'active' => $active->refresh(),
            'previous' => $previous->refresh(),
            'oldest' => $oldest->refresh(),
        ];
    }

    private function createOrUpdateStudent(
        Specialization $cs,
        string $enrollmentNo,
        string $email,
        string $name,
        string $nameEn,
        string $phone,
        StudentStatus $status,
    ): Student {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'name_en' => $nameEn,
                'password' => Hash::make('password'),
                'phone' => $phone,
                'role' => 'student',
                'specialization_id' => $cs->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $user->update([
            'name' => $name,
            'name_en' => $nameEn,
            'password' => Hash::make('password'),
            'phone' => $phone,
            'role' => 'student',
            'specialization_id' => $cs->id,
            'is_active' => true,
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class) && ! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        $student = Student::firstOrCreate(
            ['enrollment_no' => $enrollmentNo],
            [
                'user_id' => $user->id,
                'specialization_id' => $cs->id,
                'last_name' => 'الترهوني',
                'national_id' => '119880'.substr($enrollmentNo, -4).'5',
                'gender' => 'male',
                'status' => $status,
                'gpa' => 0.00,
                'is_transfer' => false,
            ]
        );
        $student->update([
            'user_id' => $user->id,
            'specialization_id' => $cs->id,
            'status' => $status,
        ]);

        return $student;
    }

    /** @param array<int, array<string, mixed>> $data */
    private function seedSemesterEnrollments(
        Student $student,
        Semester $semester,
        User $faculty,
        array $data,
        \Illuminate\Support\Collection $coursesByCode,
    ): void {
        foreach ($data as $row) {
            $course = $coursesByCode->get($row['code']);
            if (! $course) {
                continue;
            }

            $offering = CourseOffering::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'semester_id' => $semester->id,
                ],
                [
                    'faculty_id' => $faculty->id,
                    'is_open' => true,
                    'coursework_max' => 60,
                    'final_max' => 40,
                ]
            );

            $enrollmentStatus = $row['status'] ?? EnrollmentStatus::Enrolled;
            $enrollment = StudentEnrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'course_offering_id' => $offering->id,
                ],
                ['status' => $enrollmentStatus]
            );
            $enrollment->update(['status' => $enrollmentStatus]);

            if ($enrollmentStatus !== EnrollmentStatus::Enrolled) {
                continue;
            }

            $coursework = $row['coursework'] ?? null;
            $final = $row['final'] ?? null;
            $total = ($coursework !== null && $final !== null) ? ($coursework + $final) : null;
            $conv = $total !== null ? Grade::convertToLetter((float) $total) : ['letter' => null, 'points' => null];

            $gradeData = [
                'enrollment_id' => $enrollment->id,
                'coursework_grade' => $coursework,
                'final_grade' => $final,
                'total' => $total,
                'letter' => $conv['letter'],
                'points' => $conv['points'],
            ];

            if (! empty($row['fully_published'])) {
                $gradeData['status'] = GradeStatus::Approved;
                $gradeData['midterm_published_at'] = now()->subWeeks(8);
                $gradeData['published_at'] = now()->subWeeks(2);
                $gradeData['approved_by'] = $faculty->id;
                $gradeData['approved_at'] = now()->subWeeks(2);
            } elseif (! empty($row['midterm_only'])) {
                $gradeData['status'] = GradeStatus::Draft;
                $gradeData['midterm_published_at'] = now()->subDays(5);
                $gradeData['published_at'] = null;
            } else {
                $gradeData['status'] = GradeStatus::Draft;
            }

            $grade = Grade::where('enrollment_id', $enrollment->id)->first();
            if ($grade) {
                $grade->fill($gradeData)->save();
            } else {
                Grade::create($gradeData);
            }
        }
    }

    private function recomputeGpa(Student $student): float
    {
        $points = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->whereNotNull('published_at'))
            ->with('grade')
            ->get()
            ->pluck('grade.points')
            ->filter(fn ($p) => $p !== null)
            ->map(fn ($p) => (float) $p);

        if ($points->isEmpty()) {
            return 0.0;
        }

        return round($points->avg(), 2);
    }
}
