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

    /** @var array<int, string> */
    public array $selected = [];

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

    public function enrollSelected(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد مقرر واحد على الأقل')->warning()->send();

            return;
        }

        $count = 0;
        foreach ($this->selected as $courseId) {
            $offering = CourseOffering::firstOrCreate(
                ['course_id' => (int) $courseId, 'semester_id' => $this->semester->id],
                ['is_open' => true]
            );

            $created = StudentEnrollment::firstOrCreate(
                ['student_id' => $this->student->id, 'course_offering_id' => $offering->id],
                ['status' => EnrollmentStatus::Enrolled]
            );

            CourseSurvey::where('student_id', $this->student->id)
                ->where('semester_id', $this->semester->id)
                ->where('course_id', (int) $courseId)
                ->update(['is_processed' => true]);

            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        $this->selected = [];
        Notification::make()->title("تم تسجيل {$count} مقرر بنجاح")->success()->send();
    }

    public function unenrollSelected(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد مقرر واحد على الأقل')->warning()->send();

            return;
        }

        $count = 0;
        foreach ($this->selected as $courseId) {
            $offering = CourseOffering::where('course_id', (int) $courseId)
                ->where('semester_id', $this->semester->id)
                ->first();

            if ($offering) {
                $count += StudentEnrollment::where('student_id', $this->student->id)
                    ->where('course_offering_id', $offering->id)
                    ->delete();
            }
        }

        $this->selected = [];
        Notification::make()->title("تم إلغاء تسجيل {$count} مقرر")->warning()->send();
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

        CourseSurvey::where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('course_id', $courseId)
            ->update(['is_processed' => true]);

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
