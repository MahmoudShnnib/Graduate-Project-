<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class EnrollStudentInOffering
{
    public function __invoke(Student $student, CourseOffering $offering): StudentEnrollment
    {
        return DB::transaction(function () use ($student, $offering): StudentEnrollment {
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

            return $enrollment;
        });
    }
}
