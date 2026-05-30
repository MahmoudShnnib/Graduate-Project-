<?php

namespace Database\Seeders;

use App\Enums\DiscussionStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Enums\ThesisStatus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\DiscussionRequest;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\ThesisRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AcademicDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@college.edu')->firstOrFail();
        $semester = Semester::where('is_active', true)->firstOrFail();

        $faculty1 = User::where('email', 'cs.faculty@example.ly')->firstOrFail();
        $faculty2 = User::where('email', 'salem.miqrahi@faculty.ly')->firstOrFail();
        $faculty3 = User::where('email', 'fatima.zarruq@faculty.ly')->firstOrFail();
        $faculty4 = User::where('email', 'khalid.suwihli@faculty.ly')->firstOrFail();

        // ── Course Offerings ─────────────────────────────────────────────
        // All 4 general courses are shared across every specialization.
        $offering1 = CourseOffering::create([
            'course_id' => Course::where('code', 'EE601')->firstOrFail()->id,
            'semester_id' => $semester->id,
            'faculty_id' => $faculty1->id,
            'is_open' => false,
            'coursework_max' => 40,
            'final_max' => 60,
        ]);

        $offering2 = CourseOffering::create([
            'course_id' => Course::where('code', 'EE602')->firstOrFail()->id,
            'semester_id' => $semester->id,
            'faculty_id' => $faculty2->id,
            'is_open' => false,
            'coursework_max' => 40,
            'final_max' => 60,
        ]);

        $offering3 = CourseOffering::create([
            'course_id' => Course::where('code', 'EE603')->firstOrFail()->id,
            'semester_id' => $semester->id,
            'faculty_id' => $faculty3->id,
            'is_open' => false,
            'coursework_max' => 40,
            'final_max' => 60,
        ]);

        $offering4 = CourseOffering::create([
            'course_id' => Course::where('code', 'EE604')->firstOrFail()->id,
            'semester_id' => $semester->id,
            'faculty_id' => $faculty4->id,
            'is_open' => false,
            'coursework_max' => 40,
            'final_max' => 60,
        ]);

        $offerings = collect([$offering1, $offering2, $offering3, $offering4]);

        // ── Students by scenario ─────────────────────────────────────────
        $passedStudents = Student::whereIn('enrollment_no', ['251103', '252102', '253101', '251104'])->get();
        $failedStudents = Student::whereIn('enrollment_no', ['252103', '253102', '251105'])->get();
        $thesisStudents = Student::whereIn('enrollment_no', ['251106', '252104', '253103'])->get();
        $graduatedStudents = Student::whereIn('enrollment_no', ['251107', '252105', '253104'])->get();

        // Passed: CW 32/40 + final 50/60 = 82 total → B+ (3.5 pts)
        $this->enrollAndGrade($passedStudents, $offerings, 32, 50, $admin);

        // Failed: CW 10/40 + final 20/60 = 30 total → F (0.0 pts)
        $this->enrollAndGrade($failedStudents, $offerings, 10, 20, $admin);

        // Thesis-eligible: CW 36/40 + final 54/60 = 90 total → A (4.0 pts)
        $this->enrollAndGrade($thesisStudents, $offerings, 36, 54, $admin);

        // Graduated: same strong grades
        $this->enrollAndGrade($graduatedStudents, $offerings, 36, 54, $admin);

        // ── Thesis Registrations ─────────────────────────────────────────
        $thesisTitles = [
            // thesis-eligible students
            '251106' => [
                'ar' => 'تحسين كفاءة الطيف في أنظمة الاتصالات الخلوية متعددة الإدخال والإخراج',
                'en' => 'Spectral Efficiency Enhancement in MIMO Cellular Communication Systems',
                'supervisor' => $faculty1,
            ],
            '252104' => [
                'ar' => 'تصميم نظام كشف التسلل في بيئات الحوسبة السحابية باستخدام التعلم الآلي',
                'en' => 'Intrusion Detection System for Cloud Environments Using Machine Learning',
                'supervisor' => $faculty4,
            ],
            '253103' => [
                'ar' => 'تصميم وتنفيذ نظام تحكم تكيفي لذراع روبوتية صناعية',
                'en' => 'Design and Implementation of an Adaptive Control System for an Industrial Robotic Arm',
                'supervisor' => $faculty3,
            ],
            // graduated students
            '251107' => [
                'ar' => 'دراسة وتحليل أداء شبكات الاتصالات اللاسلكية في بيئات متعددة المسارات',
                'en' => 'Performance Analysis of Wireless Networks in Multipath Propagation Environments',
                'supervisor' => $faculty2,
            ],
            '252105' => [
                'ar' => 'بناء نظام التعرف على الوجه باستخدام الشبكات العصبية العميقة',
                'en' => 'Face Recognition System Using Deep Neural Networks',
                'supervisor' => $faculty4,
            ],
            '253104' => [
                'ar' => 'نمذجة ومحاكاة أنظمة التحكم الآلي في عمليات التصنيع الصناعي',
                'en' => 'Modeling and Simulation of Automated Control Systems in Industrial Manufacturing',
                'supervisor' => $faculty3,
            ],
        ];

        foreach ($thesisStudents->merge($graduatedStudents) as $student) {
            $info = $thesisTitles[$student->enrollment_no];

            ThesisRegistration::create([
                'student_id' => $student->id,
                'title' => $info['ar'],
                'title_en' => $info['en'],
                'status' => ThesisStatus::Approved->value,
                'is_external_supervisor' => false,
                'faculty_supervisor_id' => $info['supervisor']->id,
                'decided_by' => $admin->id,
                'decided_at' => now()->subMonths(3),
            ]);
        }

        // ── Discussion Requests (graduated students only) ─────────────────
        foreach ($graduatedStudents as $student) {
            $thesis = ThesisRegistration::where('student_id', $student->id)->firstOrFail();

            DiscussionRequest::create([
                'student_id' => $student->id,
                'thesis_registration_id' => $thesis->id,
                'status' => DiscussionStatus::Completed->value,
                'discussion_date' => now()->subMonths(2)->toDateString(),
                'decided_by' => $admin->id,
                'decided_at' => now()->subMonths(2),
                'discussed_at' => now()->subMonths(2),
                'completed_at' => now()->subMonths(1),
            ]);
        }
    }

    /**
     * Enroll every student in every offering and create an approved grade.
     * total/letter/points are computed manually because WithoutModelEvents
     * in DatabaseSeeder suppresses the Grade::saving() auto-compute hook.
     *
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, CourseOffering>  $offerings
     */
    private function enrollAndGrade(
        Collection $students,
        Collection $offerings,
        int $cw,
        int $final,
        User $admin,
    ): void {
        $total = $cw + $final;

        [$letter, $points] = match (true) {
            $total >= 85 => ['A', 4.0],
            $total >= 75 => ['B+', 3.5],
            $total >= 70 => ['B', 3.0],
            $total >= 65 => ['C+', 2.5],
            $total >= 60 => ['C', 2.0],
            $total >= 55 => ['D+', 1.5],
            $total >= 50 => ['D', 1.0],
            default => ['F', 0.0],
        };

        $isPassing = $total >= 50;

        foreach ($students as $student) {
            foreach ($offerings as $offering) {
                $enrollment = StudentEnrollment::create([
                    'student_id' => $student->id,
                    'course_offering_id' => $offering->id,
                    'status' => EnrollmentStatus::Enrolled->value,
                ]);

                Grade::create([
                    'enrollment_id' => $enrollment->id,
                    'coursework_grade' => $cw,
                    'final_grade' => $final,
                    'total' => $total,
                    'letter' => $isPassing ? $letter : 'F',
                    'points' => $isPassing ? $points : 0.0,
                    'status' => GradeStatus::Approved->value,
                    'approved_by' => $admin->id,
                    'approved_at' => now()->subMonths(1),
                    'published_at' => now()->subMonths(1),
                    'midterm_published_at' => now()->subMonths(2),
                ]);
            }
        }
    }
}
