<?php

namespace App\Filament\Pages;

use App\Enums\EnrollmentStatus;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class StudentSurveyView extends Page
{
    protected string $view = 'filament.pages.student-survey-view';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static bool $shouldRegisterNavigation = false;

    public ?Student $student = null;

    public ?Semester $semester = null;

    public function mount(Student $student, Semester $semester): void
    {
        $this->student = $student->load(['user:id,name', 'specialization:id,name']);
        $this->semester = $semester;
    }

    public function getTitle(): string
    {
        if (! $this->student || ! $this->semester) {
            return 'استطلاع الطالب';
        }

        return 'استطلاع '.$this->student->user?->name.' — '.$this->semester->name;
    }

    public function getSurveyEntries(): Collection
    {
        return CourseSurvey::where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->with('course')
            ->orderBy('priority')
            ->get()
            ->map(function (CourseSurvey $survey) {
                $offering = CourseOffering::where('course_id', $survey->course_id)
                    ->where('semester_id', $this->semester->id)
                    ->first();

                $enrollment = null;
                if ($offering) {
                    $enrollment = StudentEnrollment::where('student_id', $this->student->id)
                        ->where('course_offering_id', $offering->id)
                        ->first();
                }

                return (object) [
                    'survey' => $survey,
                    'course' => $survey->course,
                    'offering' => $offering,
                    'enrollment' => $enrollment,
                ];
            });
    }

    public function enrollStudent(int $courseId): void
    {
        $offering = CourseOffering::firstOrCreate(
            [
                'course_id' => $courseId,
                'semester_id' => $this->semester->id,
            ],
            ['is_open' => true]
        );

        $created = StudentEnrollment::firstOrCreate(
            [
                'student_id' => $this->student->id,
                'course_offering_id' => $offering->id,
            ],
            ['status' => EnrollmentStatus::Enrolled]
        );

        if ($created->wasRecentlyCreated) {
            Notification::make()->title('تم تسجيل الطالب في المقرر بنجاح')->success()->send();
        } else {
            Notification::make()->title('الطالب مسجل بالفعل في هذا المقرر')->warning()->send();
        }
    }

    public function unenrollStudent(int $courseId): void
    {
        $offering = CourseOffering::where('course_id', $courseId)
            ->where('semester_id', $this->semester->id)
            ->first();

        if (! $offering) {
            return;
        }

        $deleted = StudentEnrollment::where('student_id', $this->student->id)
            ->where('course_offering_id', $offering->id)
            ->delete();

        if ($deleted) {
            Notification::make()->title('تم إلغاء تسجيل الطالب')->warning()->send();
        }
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/student-survey/{student}/{semester}';
    }
}
