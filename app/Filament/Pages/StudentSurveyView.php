<?php

namespace App\Filament\Pages;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Actions\Enrollment\EnrollStudentInOffering;
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
                        ->active()
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
        $enroll = app(EnrollStudentInOffering::class);
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد مقرر واحد على الأقل')->warning()->send();

            return;
        }

        $count = 0;
        $missing = 0;

        foreach ($this->selected as $courseId) {
            $offering = CourseOffering::query()
                ->where('course_id', (int) $courseId)
                ->where('semester_id', $this->semester->id)
                ->first();

            if (! $offering) {
                $missing++;

                continue;
            }

            $enrollment = $enroll($this->student, $offering);

            if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
                $count++;
            }
        }

        $this->selected = [];

        if ($missing > 0) {
            Notification::make()
                ->title("تم تسجيل {$count} مقرر. ({$missing} مقرر غير مفتوح هذا الفصل)")
                ->warning()
                ->send();

            return;
        }

        Notification::make()->title("تم تسجيل {$count} مقرر بنجاح")->success()->send();
    }

    public function unenrollSelected(): void
    {
        $drop = app(DropStudentFromOffering::class);
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد مقرر واحد على الأقل')->warning()->send();

            return;
        }

        $count = 0;

        foreach ($this->selected as $courseId) {
            $offering = CourseOffering::query()
                ->where('course_id', (int) $courseId)
                ->where('semester_id', $this->semester->id)
                ->first();

            if ($offering && $drop($this->student, $offering)) {
                $count++;
            }
        }

        $this->selected = [];
        Notification::make()->title("تم إلغاء تسجيل {$count} مقرر")->warning()->send();
    }

    public function enrollStudent(int $courseId): void
    {
        $enroll = app(EnrollStudentInOffering::class);
        $offering = CourseOffering::query()
            ->where('course_id', $courseId)
            ->where('semester_id', $this->semester->id)
            ->first();

        if (! $offering) {
            Notification::make()->title('المقرر غير مفتوح في هذا الفصل')->danger()->send();

            return;
        }

        $enrollment = $enroll($this->student, $offering);

        if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
            Notification::make()->title('تم تسجيل الطالب في المقرر بنجاح')->success()->send();
        } else {
            Notification::make()->title('الطالب مسجل بالفعل في هذا المقرر')->warning()->send();
        }
    }

    public function unenrollStudent(int $courseId): void
    {
        $drop = app(DropStudentFromOffering::class);
        $offering = CourseOffering::query()
            ->where('course_id', $courseId)
            ->where('semester_id', $this->semester->id)
            ->first();

        if (! $offering) {
            return;
        }

        if ($drop($this->student, $offering)) {
            Notification::make()->title('تم إلغاء تسجيل الطالب')->warning()->send();
        }
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/student-survey/{student}/{semester}';
    }
}
